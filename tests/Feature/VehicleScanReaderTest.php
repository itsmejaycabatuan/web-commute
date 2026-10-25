<?php

namespace Tests\Feature;

use App\Services\VehicleScan\DocumentExtractor;
use App\Services\VehicleScan\FieldNormalizer;
use App\Services\VehicleScan\RegistrationDocumentReader;
use App\Services\VehicleScan\TesseractClient;
use Tests\TestCase;

/**
 * Reading an LTO CR / OR: the field cleaning, the label-and-cell extractor (on real Tesseract output saved in
 * tests/Fixtures/ocr/tsv, so it needs no Tesseract), and the whole reader when Tesseract is installed.
 * Every document in the fixtures is a synthetic specimen with invented data.
 */
class VehicleScanReaderTest extends TestCase
{
    private function words(string $name): array
    {
        return app(TesseractClient::class)->parse(file_get_contents(base_path("tests/Fixtures/ocr/tsv/{$name}.tsv")));
    }

    private function extract(string $name): array
    {
        $x = app(DocumentExtractor::class);
        $words = $this->words($name);

        return array_map(fn ($v) => $v['raw'], $x->extract($words, $x->detectType($words)));
    }

    // ── field cleaning ───────────────────────────────────────────────────

    public function test_plates_are_cleaned_by_the_shape_they_must_have(): void
    {
        $n = app(FieldNormalizer::class);

        $this->assertSame('VKS 3610', $n->plate('VKS3610')['value']);
        $this->assertSame('Z357YP', $n->plate('z357yp')['value']);
        $this->assertSame('957AXP', $n->plate('957 AXP')['value']);

        // O for 0 and S for 5 inside the digits: repaired, and reported as repaired.
        $fixed = $n->plate('VKS 36IO');
        $this->assertSame('VKS 3610', $fixed['value']);
        $this->assertTrue($fixed['corrected']);
        $this->assertFalse($n->plate('VKS 3610')['corrected']);

        $this->assertFalse($n->plate('HELLO')['valid']);
        $this->assertFalse($n->plate('')['valid']);
        $this->assertFalse($n->plate('560096254252115')['valid'], 'a file number is not a plate');
    }

    public function test_a_17_character_chassis_never_contains_i_o_or_q(): void
    {
        $n = app(FieldNormalizer::class);

        $fixed = $n->chassis('IRLDESB64W4BG6JN8');
        $this->assertSame('1RLDESB64W4BG6JN8', $fixed['value']);
        $this->assertTrue($fixed['corrected']);
        $this->assertSame('JT0KB20U793012345', $n->chassis('JTOKB20U793012345')['value']);
        $this->assertSame('8Y1BR36JX8UDAJXCG', $n->chassis('8Y1BR36JX8UDAJXCG')['value']);

        // An older, shorter chassis number is accepted as printed.
        $short = $n->chassis('RFBSH30BA34000162');
        $this->assertTrue($short['valid']);
        $this->assertFalse($n->chassis('')['valid']);
    }

    public function test_the_year_must_be_four_digits_in_a_sane_range(): void
    {
        $n = app(FieldNormalizer::class);

        $this->assertSame('2020', $n->year('2020')['value']);
        $this->assertSame('2020', $n->year('2O2O')['value']);
        $this->assertNull($n->year('20290')['value'], 'five digits is a misread, not a year');
        $this->assertNull($n->year('1850')['value']);
        $this->assertNull($n->year((string) (date('Y') + 5))['value']);
        $this->assertNull($n->year('abc')['value']);
    }

    public function test_fuel_is_mapped_to_the_forms_options(): void
    {
        $n = app(FieldNormalizer::class);

        $this->assertSame('Gasoline', $n->fuel('GAS')['value'], 'LTO writes Gas for gasoline');
        $this->assertSame('Gasoline', $n->fuel('Gasoline')['value']);
        $this->assertSame('Diesel', $n->fuel('DIESEL')['value']);
        $this->assertSame('Diesel', $n->fuel('DIESEI')['value'], 'one character off is still diesel');
        $this->assertSame('LPG', $n->fuel('LPG')['value']);
        $this->assertNull($n->fuel('sparkles')['value']);
    }

    public function test_makes_are_matched_to_known_names(): void
    {
        $n = app(FieldNormalizer::class);

        $this->assertSame('Toyota', $n->make('TOYOTA')['value']);
        $this->assertSame('Mitsubishi', $n->make('Mitsubishl')['value']);
        $this->assertTrue($n->make('Mitsubishl')['corrected']);
        $this->assertSame('Isuzu', $n->make('isuzu')['value']);

        $unknown = $n->make('ZZ Motors');
        $this->assertSame('Zz Motors', $unknown['value'], 'an unfamiliar make is kept as read');
    }

    // ── the label-and-cell extractor, on real Tesseract output ───────────

    public function test_it_tells_a_cr_from_an_or_and_from_anything_else(): void
    {
        $x = app(DocumentExtractor::class);

        $this->assertSame('CR', $x->detectType($this->words('cr_01_clean')));
        $this->assertSame('CR', $x->detectType($this->words('cr_02_photo')));
        $this->assertSame('OR', $x->detectType($this->words('or_01_clean')));
        $this->assertSame('OR', $x->detectType($this->words('or_02_photo')));

        $grocery = [['text' => 'Grocery', 'conf' => 95.0, 'left' => 10, 'top' => 10, 'width' => 80, 'height' => 20], ['text' => 'list', 'conf' => 95.0, 'left' => 100, 'top' => 10, 'width' => 40, 'height' => 20]];
        $this->assertNull($x->detectType($grocery));
        $this->assertNull($x->detectType([]));
    }

    public function test_it_reads_the_cells_of_a_clean_cr(): void
    {
        $got = $this->extract('cr_01_clean');

        $this->assertSame('Z357YP', $got['plate']);
        $this->assertSame('Kymco', $got['make']);
        $this->assertSame('LX150', $got['series']);
        $this->assertSame('2002', $got['year']);
        $this->assertSame('GAS', $got['fuel']);
        $this->assertSame('8Y1BR36JX8UDAJXCG', $got['chassis']);
        $this->assertSame('5734-25389166153', $got['mv_file']);
        $this->assertSame('MOTORCYCLE', $got['body_type']);
    }

    public function test_it_reads_a_tilted_phone_photo_of_a_cr(): void
    {
        $got = $this->extract('cr_02_photo');

        $this->assertSame('UYP 5463', $got['plate']);
        $this->assertSame('MULTICAB', $got['series']);
        $this->assertSame('R3S7L07A7SRB2DKNW', $got['chassis']);
        $this->assertSame('2002', $got['year']);
        $this->assertSame('VAN', $got['body_type']);
    }

    public function test_a_photocopy_with_labels_missing_is_still_read_from_the_grid(): void
    {
        $got = $this->extract('cr_06_photocopy');

        $this->assertSame('BHM 1988', $got['plate']);
        $this->assertSame('MULTICAB', $got['series'], 'MAKE and FUEL were not read, SERIES must still be');
        $this->assertSame('2019', $got['year']);
        // The scanner read a 1 as an I: the cleaning step puts it right.
        $this->assertSame('1RLDESB64W4BG6JN8', app(FieldNormalizer::class)->chassis($got['chassis'])['value']);
    }

    public function test_a_dropped_label_is_rebuilt_from_the_grid(): void
    {
        $x = app(DocumentExtractor::class);
        $words = array_values(array_filter($this->words('cr_01_clean'), fn ($w) => ! in_array(strtoupper($w['text']), ['FUEL', 'MAKE'], true)));

        $got = $x->extract($words, 'CR');

        $this->assertSame('GAS', $got['fuel']['raw']);
        $this->assertSame('Kymco', $got['make']['raw']);
    }

    public function test_a_cell_does_not_swallow_its_neighbour_when_a_label_is_missing(): void
    {
        $x = app(DocumentExtractor::class);
        // Without BODY TYPE the SERIES cell has no label to its right, yet MOTORCYCLE must not leak into it.
        $words = array_values(array_filter($this->words('cr_01_clean'), fn ($w) => ! in_array(strtoupper($w['text']), ['BODY', 'TYPE'], true) || $w['top'] < 800));

        $this->assertSame('LX150', $x->extract($words, 'CR')['series']['raw']);
    }

    public function test_it_reads_the_payment_details_of_an_or(): void
    {
        $or = $this->extract('or_02_photo');

        $this->assertSame('VFG 9672', $or['plate'], 'the plate, not the file number below it');
        $this->assertSame('Diesel', $or['fuel']);
        $this->assertSame('2022', $or['year']);
        $this->assertSame('BLUE', $or['color']);

        // The monospace year on this page was misread as 20290: it must come out blank, not as a wrong year.
        $this->assertSame('20290', $this->extract('or_01_clean')['year']);
        $this->assertNull(app(FieldNormalizer::class)->year('20290')['value']);
    }

    // ── the whole reader (needs Tesseract and the generated specimens) ───

    private function requireTesseract(): void
    {
        TesseractClient::forgetAvailability();

        if (! app(TesseractClient::class)->available()) {
            $this->markTestSkipped('Tesseract is not installed.');
        }
        if (! is_file(base_path('tests/Fixtures/ocr/samples/cr_01_clean.png'))) {
            $this->markTestSkipped('Run: python3 tests/Fixtures/ocr/generate_samples.py');
        }
    }

    public function test_the_reader_fills_the_form_fields_from_a_cr_photo(): void
    {
        $this->requireTesseract();

        $result = app(RegistrationDocumentReader::class)->read([base_path('tests/Fixtures/ocr/samples/cr_02_scan.jpg')]);

        $this->assertSame('UYP 5463', $result['fields']['plate_number']['value']);
        $this->assertSame('Suzuki', $result['fields']['brand']['value']);
        $this->assertSame('MULTICAB', $result['fields']['model']['value']);
        $this->assertSame('2002', $result['fields']['year']['value']);
        $this->assertSame('Gasoline', $result['fields']['fuel_type']['value']);
        $this->assertSame('R3S7L07A7SRB2DKNW', $result['fields']['vin']['value']);
        $this->assertSame('check', $result['fields']['vin']['status'], 'a chassis number is always double-checked');
        $this->assertSame('CR', $result['documents'][0]['type']);
    }

    public function test_the_reader_works_out_which_way_up_a_sideways_photo_is(): void
    {
        $this->requireTesseract();

        $sideways = sys_get_temp_dir().'/sideways-'.uniqid().'.png';
        $img = imagecreatefrompng(base_path('tests/Fixtures/ocr/samples/cr_01_clean.png'));
        imagepng(imagerotate($img, 90, 0xFFFFFF), $sideways);

        try {
            $result = app(RegistrationDocumentReader::class)->read([$sideways]);
        } finally {
            @unlink($sideways);
        }

        $this->assertSame('Z357YP', $result['fields']['plate_number']['value']);
        $this->assertSame('Kymco', $result['fields']['brand']['value']);
    }

    public function test_two_papers_that_agree_are_trusted_and_two_that_disagree_are_flagged(): void
    {
        $this->requireTesseract();
        $reader = app(RegistrationDocumentReader::class);
        $samples = base_path('tests/Fixtures/ocr/samples');

        // The CR and OR of one specimen vehicle? They are separate specimens, so make them agree by hand:
        // the same CR twice is "two papers" with identical values.
        $same = $reader->read(["$samples/cr_03_clean.png", "$samples/cr_03_scan.jpg"]);
        $this->assertEmpty(array_filter($same['warnings'], fn ($w) => str_contains($w, 'differs')));

        // Different vehicles: plate, year and fuel disagree, so they are flagged with the other reading offered.
        $mixed = $reader->read(["$samples/cr_03_clean.png", "$samples/or_03_clean.png"]);
        $this->assertNotEmpty(array_filter($mixed['warnings'], fn ($w) => str_contains($w, 'Plate number differs')));
        $this->assertSame('check', $mixed['fields']['plate_number']['status']);
        $this->assertContains('RYJ 2039', $mixed['fields']['plate_number']['alternatives']);
    }

    public function test_a_photo_that_is_not_a_registration_paper_yields_nothing_and_a_clear_warning(): void
    {
        $this->requireTesseract();

        $blank = sys_get_temp_dir().'/blank-'.uniqid().'.png';
        $img = imagecreatetruecolor(1200, 900);
        imagefill($img, 0, 0, 0xFFFFFF);
        imagestring($img, 5, 100, 100, 'Grocery list: eggs, rice, milk', 0x000000);
        imagepng($img, $blank);

        try {
            $result = app(RegistrationDocumentReader::class)->read([$blank]);
        } finally {
            @unlink($blank);
        }

        $this->assertSame([], $result['fields']);
        $this->assertStringContainsString('does not look like an LTO', $result['warnings'][0]);
    }
}
