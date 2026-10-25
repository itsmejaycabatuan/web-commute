<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ScheduledReport;
use App\Services\PeriodReportService;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendScheduledReport extends Command
{
    protected $signature = 'reports:send
        {period=weekly : weekly (last Mon–Sun) or monthly (last calendar month)}
        {--to= : email the report to this address only (to try it out)}
        {--dry-run : print the figures without emailing anyone}';

    protected $description = 'Email the Admins a weekly or monthly report: revenue, top-ups, violations, time-keeping and maintenance cost';

    public function handle(PeriodReportService $reports): int
    {
        $kind = (string) $this->argument('period');

        if (! in_array($kind, ['weekly', 'monthly'], true)) {
            $this->error('The period must be weekly or monthly.');

            return self::INVALID;
        }

        [$from, $to] = PeriodReportService::periodFor($kind);
        $report = $reports->build($from, $to);

        $this->info(ucfirst($kind).' report for '.$report['label']);
        $this->line('  Fare revenue '.number_format($report['revenue']['total'], 2).' ('.$report['revenue']['count'].' fares)'
            .', top-ups '.number_format($report['topups']['total'], 2)
            .', violations '.$report['violations']['count']
            .', maintenance '.number_format($report['maintenance']['cost'], 2));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $stamp = $from->format('Y-m-d').'_to_'.$to->format('Y-m-d');
        $attachments = ["smartcommute_{$kind}_report_{$stamp}.csv" => $reports->summaryCsv($report)];

        if (config('reports.attach_activity_log') && ($log = $reports->activityLogCsv($from, $to))) {
            $attachments["smartcommute_activity_log_{$stamp}.csv"] = $log;
        }

        $notification = new ScheduledReport($kind, $report, $attachments);

        $recipients = $this->option('to')
            ? User::where('email', $this->option('to'))->get()
            : Notifier::withRole('admin');

        if ($recipients->isEmpty()) {
            $this->warn('There is nobody to send the report to.');

            return self::SUCCESS;
        }

        $sent = Notifier::send($recipients, $notification);

        Audit::log(Audit::SYSTEM, 'report_sent', ucfirst($kind).' report for '.$report['label'].' emailed to '.$sent.' admin(s)', null, [
            'report' => $kind,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'recipients' => $sent,
            'attachments' => array_keys($attachments),
        ]);

        $this->info("Sent to {$sent} recipient(s).");

        return self::SUCCESS;
    }
}
