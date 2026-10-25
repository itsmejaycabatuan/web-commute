<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleScanSession;
use App\Services\VehicleScan\RegistrationDocumentReader;
use App\Services\VehicleScan\TesseractClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Add a vehicle by scanning its papers with a phone: the desktop starts a session, the phone sends photos to a
 * link, the server reads them (faked here: the reader is covered in VehicleScanReaderTest) and the desktop gets the
 * values to check. The photos stay private, die with the session, and the vehicle is only saved once the manager
 * says they checked it.
 */
class VehicleScanFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected string $fakeTesseract;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Storage::fake(VehicleScanSession::DISK);

        // A stand-in program that answers `--version`, so the feature counts as installed without needing Tesseract.
        $this->fakeTesseract = tempnam(sys_get_temp_dir(), 'tess');
        file_put_contents($this->fakeTesseract, "#!/bin/sh\nexit 0\n");
        chmod($this->fakeTesseract, 0755);
        config(['vehicle_scan.enabled' => true, 'vehicle_scan.tesseract_binary' => $this->fakeTesseract, 'vehicle_scan.ttl_minutes' => 15]);
        TesseractClient::forgetAvailability();
    }

    protected function tearDown(): void
    {
        @unlink($this->fakeTesseract);
        TesseractClient::forgetAvailability();
        parent::tearDown();
    }

    protected function manager(): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole('maintenance_manager');
    }

    protected function read(array $fields = null, array $warnings = []): void
    {
        $fields ??= [
            'plate_number' => ['value' => 'ABC 1234', 'status' => 'ok', 'confidence' => 92, 'source' => 'CR'],
            'vin' => ['value' => 'JTDKB20U793012345', 'status' => 'check', 'confidence' => 80, 'source' => 'CR'],
            'brand' => ['value' => 'Toyota', 'status' => 'ok', 'confidence' => 95, 'source' => 'CR'],
            'model' => ['value' => 'VIOS', 'status' => 'ok', 'confidence' => 95, 'source' => 'CR'],
            'year' => ['value' => '2019', 'status' => 'ok', 'confidence' => 96, 'source' => 'CR'],
            'fuel_type' => ['value' => 'Gasoline', 'status' => 'check', 'confidence' => 70, 'source' => 'CR', 'alternatives' => ['Diesel']],
        ];

        $this->mock(RegistrationDocumentReader::class, fn ($m) => $m->shouldReceive('read')->andReturn([
            'fields' => $fields, 'documents' => [['type' => 'CR', 'label' => 'photo 1']], 'warnings' => $warnings,
        ]));
    }

    protected function start(User $user): array
    {
        return $this->actingAs($user)->postJson(route('vehicle-scan.store'))->assertCreated()->json();
    }

    protected function send(string $token, int $files = 1)
    {
        return $this->post(route('vehicle-scan.upload', $token), ['photos' => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 800, 600), range(1, $files))], ['Accept' => 'application/json']);
    }

    protected function token(array $started): string
    {
        return basename($started['phone_url']);
    }

    // ── starting a scan ──────────────────────────────────────────────────

    public function test_the_manager_starts_a_scan_and_gets_a_link_for_the_phone(): void
    {
        $started = $this->start($this->manager());

        $this->assertSame('pending', $started['status']);
        $this->assertStringContainsString('/scan/', $started['phone_url']);
        $this->assertSame(40, strlen($this->token($started)));
        $this->assertEqualsWithDelta(900, $started['expires_in'], 5);
    }

    public function test_only_the_maintenance_manager_and_admin_can_start_one(): void
    {
        foreach (['commuter', 'driver', 'driver_manager'] as $role) {
            $user = User::factory()->create(['email_verified_at' => now()])->assignRole($role);
            $this->actingAs($user)->postJson(route('vehicle-scan.store'))->assertForbidden();
        }

        $this->assertContains($this->postJson(route('vehicle-scan.store'))->status(), [401, 403], 'a guest cannot start a scan');

        $admin = User::factory()->create(['email_verified_at' => now()])->assignRole('admin');
        $this->actingAs($admin)->postJson(route('vehicle-scan.store'))->assertCreated();
    }

    public function test_starting_another_scan_closes_the_first(): void
    {
        $manager = $this->manager();
        $first = $this->start($manager);
        $this->start($manager);

        $this->assertSame('cancelled', VehicleScanSession::find($first['id'])->status);
    }

    public function test_the_button_is_hidden_and_starting_refused_when_the_reader_is_not_installed(): void
    {
        config(['vehicle_scan.tesseract_binary' => '/nonexistent/tesseract']);
        TesseractClient::forgetAvailability();
        $manager = $this->manager();

        $this->actingAs($manager)->postJson(route('vehicle-scan.store'))->assertStatus(503);
        $this->actingAs($manager)->get(route('vehicles.index'))->assertOk()->assertViewHas('scanAvailable', false);

        config(['vehicle_scan.tesseract_binary' => $this->fakeTesseract]);
        TesseractClient::forgetAvailability();
        $this->actingAs($manager)->get(route('vehicles.index'))->assertViewHas('scanAvailable', true);
    }

    public function test_polling_does_not_use_up_the_allowance_to_start_scans(): void
    {
        $manager = $this->manager();
        $started = $this->start($manager);

        foreach (range(1, 40) as $i) {
            $this->actingAs($manager)->getJson(route('vehicle-scan.show', $started['id']))->assertOk();
        }

        $this->actingAs($manager)->postJson(route('vehicle-scan.store'))->assertCreated();
    }

    // ── the phone ────────────────────────────────────────────────────────

    public function test_the_phone_page_opens_with_the_token_alone_and_a_dead_link_says_so(): void
    {
        $started = $this->start($this->manager());

        $this->get(route('vehicle-scan.phone', $this->token($started)))->assertOk()->assertSee('Photograph the papers')->assertSee('Certificate of Registration');

        $this->get(route('vehicle-scan.phone', str_repeat('x', 40)))->assertStatus(410)->assertSee('expired');
        $this->get(route('vehicle-scan.phone', 'short'))->assertStatus(410);

        VehicleScanSession::find($started['id'])->update(['expires_at' => now()->subMinute()]);
        $this->get(route('vehicle-scan.phone', $this->token($started)))->assertStatus(410);
    }

    public function test_photos_sent_from_the_phone_are_read_and_the_desktop_receives_the_values(): void
    {
        $this->read(warnings: ['Check the plate.']);
        $manager = $this->manager();
        $started = $this->start($manager);

        $this->send($this->token($started), 2)->assertOk()->assertJson(['status' => 'ready']);

        $status = $this->actingAs($manager)->getJson(route('vehicle-scan.show', $started['id']))->assertOk()->json();

        $this->assertSame('ready', $status['status']);
        $this->assertSame('ABC 1234', $status['fields']['plate_number']['value']);
        $this->assertSame(['Diesel'], $status['fields']['fuel_type']['alternatives']);
        $this->assertSame(['Check the plate.'], $status['warnings']);
        $this->assertCount(2, $status['pages']);
    }

    public function test_the_phone_is_never_told_what_was_read(): void
    {
        $this->read();
        $started = $this->start($this->manager());

        $response = $this->send($this->token($started))->assertOk();

        $this->assertStringNotContainsString('ABC 1234', $response->getContent());
        $this->assertStringNotContainsString('Toyota', $response->getContent());
    }

    public function test_a_finished_scan_gives_the_manager_a_fresh_window_to_check_it(): void
    {
        $this->read();
        $manager = $this->manager();
        $started = $this->start($manager);
        VehicleScanSession::find($started['id'])->update(['expires_at' => now()->addSeconds(30)]);

        $this->send($this->token($started))->assertOk();

        $this->assertEqualsWithDelta(900, VehicleScanSession::find($started['id'])->expires_at->diffInSeconds(now()), 5);
    }

    public function test_the_upload_is_checked(): void
    {
        $started = $this->start($this->manager());
        $token = $this->token($started);

        $this->post(route('vehicle-scan.upload', $token), [], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('message', 'Choose a photo of the paper first.');
        $this->send($token, 3)->assertStatus(422);
        $this->post(route('vehicle-scan.upload', $token), ['photos' => [UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf')]], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post(route('vehicle-scan.upload', $token), ['photos' => [UploadedFile::fake()->image('huge.jpg')->size(20000)]], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertSame('pending', VehicleScanSession::find($started['id'])->status, 'nothing was stored or read');
    }

    public function test_a_link_that_has_expired_or_belongs_to_nobody_takes_no_photos(): void
    {
        $started = $this->start($this->manager());
        VehicleScanSession::find($started['id'])->update(['expires_at' => now()->subMinute()]);

        $this->send($this->token($started))->assertStatus(410);
        $this->send(str_repeat('z', 40))->assertStatus(410);
        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles());
    }

    public function test_a_retake_replaces_the_first_photos(): void
    {
        $this->read();
        $started = $this->start($this->manager());

        $this->send($this->token($started), 2)->assertOk();
        $this->assertCount(2, Storage::disk(VehicleScanSession::DISK)->allFiles());

        $this->send($this->token($started), 1)->assertOk();
        $this->assertCount(1, Storage::disk(VehicleScanSession::DISK)->allFiles());
    }

    public function test_a_photo_that_cannot_be_read_fails_the_scan_and_is_deleted(): void
    {
        $this->mock(RegistrationDocumentReader::class, fn ($m) => $m->shouldReceive('read')->andReturn([
            'fields' => [], 'documents' => [], 'warnings' => ['Photo 1 does not look like an LTO Official Receipt or Certificate of Registration, or it is too blurry to read.'],
        ]));
        $manager = $this->manager();
        $started = $this->start($manager);

        $this->send($this->token($started))->assertOk()->assertJson(['status' => 'failed']);

        $status = $this->actingAs($manager)->getJson(route('vehicle-scan.show', $started['id']))->json();
        $this->assertSame('failed', $status['status']);
        $this->assertStringContainsString('does not look like', $status['error']);
        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles(), 'a photo that was not usable is not kept');

        // ...and the phone can simply try again on the same link.
        $this->read();
        $this->send($this->token($started))->assertOk()->assertJson(['status' => 'ready']);
    }

    public function test_a_crash_while_reading_fails_the_scan_cleanly(): void
    {
        $this->mock(RegistrationDocumentReader::class, fn ($m) => $m->shouldReceive('read')->andThrow(new \RuntimeException('tesseract exploded')));
        $manager = $this->manager();
        $started = $this->start($manager);

        $this->send($this->token($started))->assertOk()->assertJson(['status' => 'failed']);

        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles());
        $this->assertStringNotContainsString('exploded', $this->actingAs($manager)->getJson(route('vehicle-scan.show', $started['id']))->getContent(), 'internal errors are not shown');
    }

    // ── privacy ──────────────────────────────────────────────────────────

    public function test_nobody_else_can_see_a_scan_or_its_photos(): void
    {
        $this->read();
        $owner = $this->manager();
        $started = $this->start($owner);
        $this->send($this->token($started))->assertOk();
        $other = $this->manager();

        $this->actingAs($other)->getJson(route('vehicle-scan.show', $started['id']))->assertNotFound();
        $this->actingAs($other)->get(route('vehicle-scan.page', [$started['id'], 0]))->assertNotFound();
        $this->actingAs($other)->deleteJson(route('vehicle-scan.destroy', $started['id']))->assertNotFound();

        $photo = $this->actingAs($owner)->get(route('vehicle-scan.page', [$started['id'], 0]))->assertOk();
        $this->assertStringContainsString('no-store', $photo->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $photo->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $photo->headers->get('Cache-Control'));
        $this->actingAs($owner)->get(route('vehicle-scan.page', [$started['id'], 5]))->assertNotFound();
    }

    public function test_photos_are_kept_on_the_private_disk_only(): void
    {
        $this->read();
        $started = $this->start($this->manager());
        $this->send($this->token($started))->assertOk();

        $files = Storage::disk(VehicleScanSession::DISK)->allFiles();

        $this->assertCount(1, $files);
        $this->assertStringStartsWith('vehicle-scans/'.$started['id'].'/', $files[0]);
        $this->assertFalse(Storage::disk('public')->exists($files[0]));
    }

    public function test_cancelling_deletes_the_photos_and_the_result(): void
    {
        $this->read();
        $manager = $this->manager();
        $started = $this->start($manager);
        $this->send($this->token($started))->assertOk();

        $this->actingAs($manager)->deleteJson(route('vehicle-scan.destroy', $started['id']))->assertOk()->assertJson(['status' => 'cancelled']);

        $session = VehicleScanSession::find($started['id']);
        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles());
        $this->assertNull($session->result);
        $this->assertNull($session->files);

        $this->send($this->token($started))->assertStatus(410);
    }

    public function test_an_expired_scan_reports_expired_and_drops_its_photos(): void
    {
        $this->read();
        $manager = $this->manager();
        $started = $this->start($manager);
        $this->send($this->token($started))->assertOk();
        VehicleScanSession::find($started['id'])->update(['expires_at' => now()->subSecond()]);

        $status = $this->actingAs($manager)->getJson(route('vehicle-scan.show', $started['id']))->json();

        $this->assertSame('expired', $status['status']);
        $this->assertNull($status['fields']);
        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles());
    }

    // ── saving the vehicle ───────────────────────────────────────────────

    protected function readyScan(User $manager): array
    {
        $this->read();
        $started = $this->start($manager);
        $this->send($this->token($started))->assertOk();

        return $started;
    }

    protected function vehicle(array $over = []): array
    {
        return array_merge([
            'plate_number' => 'ABC 1234', 'vin' => 'JTDKB20U793012345', 'brand' => 'Toyota', 'model' => 'VIOS', 'year' => 2019,
            'fuel_type' => 'Gasoline', 'status' => 'active', 'acquisition_date' => '2024-01-15',
        ], $over);
    }

    public function test_the_vehicle_is_not_saved_until_the_manager_confirms_they_checked_it(): void
    {
        $manager = $this->manager();
        $scan = $this->readyScan($manager);

        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id']]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_confirmed');
        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => false]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_confirmed');

        $this->assertNull(Vehicle::where('plate_number', 'ABC 1234')->first());
        $this->assertSame('ready', VehicleScanSession::find($scan['id'])->status, 'the scan is still there to be checked');
    }

    public function test_a_confirmed_scan_saves_the_vehicle_deletes_the_photos_and_is_audited(): void
    {
        $manager = $this->manager();
        $scan = $this->readyScan($manager);

        // The manager corrected the model before saving.
        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => true, 'model' => 'VIOS XLE']))
            ->assertCreated();

        $vehicle = Vehicle::where('plate_number', 'ABC 1234')->firstOrFail();
        $session = VehicleScanSession::find($scan['id']);

        $this->assertSame('consumed', $session->status);
        $this->assertSame($vehicle->id, $session->vehicle_id);
        $this->assertNull($session->result);
        $this->assertSame([], Storage::disk(VehicleScanSession::DISK)->allFiles(), 'photos are deleted once the vehicle is saved');

        $audit = Activity::where('event', 'vehicle_scan_used')->where('subject_id', $vehicle->id)->latest('id')->firstOrFail();
        $this->assertSame(['model'], $audit->properties['fields_corrected_by_manager']);
        $this->assertEqualsCanonicalizing(['vin', 'fuel_type'], $audit->properties['needed_a_second_look']);
        $this->assertStringNotContainsString('JTDKB20U793012345', json_encode($audit->properties), 'the audit trail records which fields, not the values');
    }

    public function test_a_scan_cannot_be_used_twice_or_by_someone_else_or_when_unfinished(): void
    {
        $manager = $this->manager();
        $scan = $this->readyScan($manager);

        $this->actingAs($this->manager())->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => true]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_session_id');

        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => true]))->assertCreated();
        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['plate_number' => 'XYZ 9999', 'scan_session_id' => $scan['id'], 'scan_confirmed' => true]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_session_id');

        $pending = $this->start($manager);
        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['plate_number' => 'QQQ 1111', 'scan_session_id' => $pending['id'], 'scan_confirmed' => true]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_session_id');
    }

    public function test_an_expired_scan_cannot_be_used_to_save(): void
    {
        $manager = $this->manager();
        $scan = $this->readyScan($manager);
        VehicleScanSession::find($scan['id'])->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => true]))
            ->assertStatus(422)->assertJsonValidationErrors('scan_session_id');
    }

    public function test_adding_a_vehicle_by_hand_is_unchanged(): void
    {
        $this->actingAs($this->manager())->postJson(route('vehicles.store'), $this->vehicle(['plate_number' => 'HND 0001']))->assertCreated();

        $this->assertNotNull(Vehicle::where('plate_number', 'HND 0001')->first());
        $this->assertFalse(Activity::where('event', 'vehicle_scan_used')->where('description', 'like', '%HND 0001%')->exists());
    }

    public function test_a_saved_scan_cannot_be_cancelled_into_a_cancelled_one(): void
    {
        $manager = $this->manager();
        $scan = $this->readyScan($manager);
        $this->actingAs($manager)->postJson(route('vehicles.store'), $this->vehicle(['scan_session_id' => $scan['id'], 'scan_confirmed' => true]))->assertCreated();

        // The form closing after a successful save must not rewrite history.
        $this->actingAs($manager)->deleteJson(route('vehicle-scan.destroy', $scan['id']))->assertOk()->assertJson(['status' => 'consumed']);
    }

    // ── the page ─────────────────────────────────────────────────────────

    public function test_the_add_vehicle_form_offers_the_phone_scan_and_gates_saving_on_the_confirmation(): void
    {
        $html = $this->actingAs($this->manager())->get(route('vehicles.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Scan with phone', $html);
        $this->assertStringContainsString('I have checked these details against the document', $html);
        $this->assertStringContainsString('scanApplied && !editForm.scan_confirmed', $html);
    }

    public function test_updating_a_scan_session_never_moves_its_expiry(): void
    {
        $manager = $this->manager();
        $started = $this->start($manager);
        $session = VehicleScanSession::find($started['id']);
        $session->update(['expires_at' => now()->addMinutes(7)]);
        $expiry = $session->fresh()->expires_at->timestamp;

        sleep(1);
        $session->update(['status' => VehicleScanSession::PROCESSING, 'uploaded_at' => now()]);
        $session->update(['error' => 'x']);

        $this->assertSame($expiry, $session->fresh()->expires_at->timestamp, 'MySQL must not refresh the column by itself');
    }

    // ── cleaning up ──────────────────────────────────────────────────────

    public function test_the_purge_job_deletes_expired_photos_orphans_and_old_records(): void
    {
        $this->read();
        $manager = $this->manager();
        $expired = $this->start($manager);
        $this->send($this->token($expired))->assertOk();
        VehicleScanSession::find($expired['id'])->update(['expires_at' => now()->subMinute()]);

        $disk = Storage::disk(VehicleScanSession::DISK);
        $disk->put('vehicle-scans/99999/page-0.jpg', 'x');                       // no row at all
        touch($disk->path('vehicle-scans/99999'), time() - 3600);                  // older than the 30-minute grace
        $old = VehicleScanSession::create(['token' => str_repeat('o', 40), 'user_id' => $manager->id, 'status' => 'cancelled', 'expires_at' => now()->subDays(3)]);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->artisan('vehicle-scans:purge')->assertSuccessful();

        $this->assertSame([], $disk->allFiles());
        $this->assertNull(VehicleScanSession::find($old->id));
        $this->assertNotNull(VehicleScanSession::find($expired['id']), 'a recent record stays as a trace');
    }

    public function test_the_purge_job_leaves_a_live_scan_alone(): void
    {
        $this->read();
        $started = $this->start($this->manager());
        $this->send($this->token($started))->assertOk();

        $this->artisan('vehicle-scans:purge')->assertSuccessful();

        $this->assertCount(1, Storage::disk(VehicleScanSession::DISK)->allFiles());
    }

    public function test_the_purge_job_is_scheduled(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->pluck('command')->implode("\n");

        $this->assertStringContainsString('vehicle-scans:purge', $events);
    }
}
