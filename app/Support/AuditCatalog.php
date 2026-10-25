<?php

namespace App\Support;

/**
 * Display metadata for the audit trail: human names and colours for the categories (log_name) and
 * events the application writes (see docs/AUDIT_TRAIL.md). Anything not listed here — including rows
 * written by the old logging — still renders, with its raw name and a neutral colour.
 *
 * Tailwind classes are spelled out in full because the CDN build only sees literal class names.
 */
class AuditCatalog
{
    /** category => [label, badge classes] */
    public const CATEGORIES = [
        'auth' => ['Sign-in & security', 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/15'],
        'account' => ['Accounts', 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/15'],
        'driver' => ['Drivers', 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/15'],
        'fleet' => ['Fleet & maintenance', 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/15'],
        'violation' => ['Violations', 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/15'],
        'timekeeping' => ['Timekeeping', 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/15'],
        'payment' => ['Payments', 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/15'],
        'fare' => ['Fares', 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/15'],
        'system' => ['System', 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/15'],
    ];

    public const FALLBACK_CATEGORY_CLASSES = 'bg-gray-500/10 text-gray-500 dark:text-[#777] border-gray-500/15';

    /** event => [label, tone]   tone: danger | warn | ok | info | neutral */
    public const EVENTS = [
        'login' => ['Signed in', 'ok'],
        'login_failed' => ['Failed sign-in', 'danger'],
        'login_blocked' => ['Blocked sign-in', 'danger'],
        'logout' => ['Signed out', 'neutral'],
        'password_changed' => ['Password changed', 'info'],
        'password_reset' => ['Password reset', 'info'],
        'password_set_by_admin' => ['Password set by admin', 'warn'],
        'sessions_revoked' => ['Other devices signed out', 'info'],
        'registered' => ['Registered', 'ok'],
        'email_verified' => ['Email verified', 'ok'],
        'drivers_assigned' => ['Drivers assigned', 'info'],
        'duty_roster_updated' => ['Duty roster updated', 'info'],
        'created' => ['Created', 'ok'],
        'updated' => ['Updated', 'info'],
        'deleted' => ['Deleted', 'danger'],
        'deleted_self' => ['Deleted own account', 'danger'],
        'anonymised' => ['Anonymised', 'danger'],
        'suspended' => ['Suspended', 'warn'],
        'reactivated' => ['Reactivated', 'ok'],
        'inactivity_warned' => ['Inactivity warning sent', 'warn'],
        'role_changed' => ['Role changed', 'warn'],
        'fare_guide_imported' => ['Fare guide imported', 'ok'],
        'fare_rates_updated' => ['Fare rates updated', 'info'],
        'activity_log_exported' => ['Activity log exported', 'warn'],
        'report_exported' => ['Report exported', 'warn'],
        'vacancy_opened' => ['Vehicle lost a driver', 'warn'],
        'replacement_assigned' => ['Replacement driver assigned', 'ok'],
        'released_from_vehicle' => ['Taken off vehicle', 'warn'],
        'report_sent' => ['Scheduled report emailed', 'info'],
        'repeat_violator_flagged' => ['Flagged as repeat violator', 'warn'],
        'fare_evasion_flagged' => ['Flagged for fare evasion', 'warn'],
    ];

    public const TONE_CLASSES = [
        'danger' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/15',
        'warn' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/15',
        'ok' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/15',
        'info' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/15',
        'neutral' => 'bg-gray-500/10 text-gray-500 dark:text-[#888] border-gray-500/15',
    ];

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORIES[$category][0] ?? ($category && $category !== 'default' ? ucfirst($category) : 'Legacy');
    }

    public static function categoryClasses(?string $category): string
    {
        return self::CATEGORIES[$category][1] ?? self::FALLBACK_CATEGORY_CLASSES;
    }

    public static function eventLabel(?string $event): string
    {
        return self::EVENTS[$event][0] ?? ($event ? ucfirst(str_replace('_', ' ', $event)) : '—');
    }

    public static function eventClasses(?string $event): string
    {
        return self::TONE_CLASSES[self::EVENTS[$event][1] ?? 'neutral'];
    }

    /** @return array<string,string> value => label, for the filter drop-downs */
    public static function categoryOptions(): array
    {
        return array_map(fn ($c) => $c[0], self::CATEGORIES);
    }

    /** @return array<string,string> */
    public static function eventOptions(): array
    {
        return array_map(fn ($e) => $e[0], self::EVENTS);
    }
}
