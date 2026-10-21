<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\AuditCatalog;
use App\Support\AuditPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-only, read-only view of the audit trail (activity_log). See docs/AUDIT_TRAIL.md.
 * Nothing here edits or deletes an entry.
 */
class ActivityLogController extends Controller
{
    private const PER_PAGE = 10;

    /** An export is capped so one click cannot read the whole table into a response. */
    private const EXPORT_LIMIT = 10000;

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);

        $entries = (clone $query)->with('causer')->paginate(self::PER_PAGE)->withQueryString();
        $rows = $entries->getCollection()->map(fn (Activity $a) => AuditPresenter::present($a));

        return view('admin.activity-log.index', [
            'entries' => $entries,
            'rows' => $rows,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'categories' => AuditCatalog::categoryOptions(),
            'events' => AuditCatalog::eventOptions(),
            'stats' => [
                'today' => $this->base()->where('created_at', '>=', Carbon::today())->count(),
                'failed_signins' => $this->base()->where('log_name', Audit::AUTH)->where('event', 'login_failed')
                    ->where('created_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }

    /** CSV of whatever the filters currently match (newest first, capped). The export is itself audited. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);
        $rows = min((clone $query)->count(), self::EXPORT_LIMIT);

        Audit::log(Audit::SYSTEM, 'activity_log_exported', 'Exported the activity log ('.$rows.' rows)', null, [
            'rows' => $rows,
            'filters' => array_filter($filters),
        ]);

        $filename = 'smartcommute_activity_log_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents and ₱ correctly
            fputcsv($out, ['Entry', 'Time', 'Category', 'Event', 'Actor email', 'Actor role', 'Description',
                'Record', 'IP address', 'Device', 'Changes', 'Details']);

            foreach ((clone $query)->limit(self::EXPORT_LIMIT)->cursor() as $activity) {
                $r = AuditPresenter::present($activity);

                $changes = collect($r['changes'])->map(fn ($c) => "{$c['field']}: {$c['old']} → {$c['new']}")
                    ->merge(collect($r['tiers'])->map(fn ($t) => "{$t['km']}: regular {$t['regular']}, discount {$t['discount']}"))
                    ->implode('; ');
                $details = collect($r['facts'])->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');

                fputcsv($out, array_map([$this, 'csvSafe'], [
                    $r['id'],
                    $r['when']?->format('Y-m-d H:i:s'),
                    $r['category_label'],
                    $r['event_label'],
                    $r['actor_email'] ?? '',
                    $r['actor_role'] ?? '',
                    $r['description'],
                    $r['subject'] ?? '',
                    $r['ip'] ?? '',
                    $r['device'] ?? '',
                    $changes,
                    $details,
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{category:?string,event:?string,q:?string,from:?string,to:?string} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(AuditCatalog::CATEGORIES))],
            'event' => ['nullable', Rule::in(array_keys(AuditCatalog::EVENTS))],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        return [
            'category' => $validated['category'] ?? null,
            'event' => $validated['event'] ?? null,
            'q' => isset($validated['q']) ? trim($validated['q']) : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    /**
     * Every entry except the legacy "Action performed: …" noise the old logging wrote, which says
     * nothing and would bury the real entries until `activitylog:purge-noise` has been run.
     */
    private function base(): Builder
    {
        return Activity::query()->where('description', 'not like', 'Action performed:%');
    }

    private function query(array $f): Builder
    {
        return $this->base()
            ->when($f['category'], fn ($q, $v) => $q->where('log_name', $v))
            ->when($f['event'], fn ($q, $v) => $q->where('event', $v))
            ->when($f['from'], fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($f['to'], fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->when($f['q'], function ($q, $v) {
                // who (actor snapshot, or the email a failed sign-in tried) or what (the description)
                $like = '%'.addcslashes($v, '%_\\').'%';
                $q->where(fn ($w) => $w
                    ->where('description', 'like', $like)
                    ->orWhere('properties->context->actor_email', 'like', $like)
                    ->orWhere('properties->email', 'like', $like));
            })
            ->orderByDesc('id');
    }

    /** Stops a spreadsheet from running a cell that starts like a formula (=, +, -, @). */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
