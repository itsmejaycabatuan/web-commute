<?php

namespace App\Support;

use Spatie\Activitylog\Models\Activity;

/**
 * Turns one activity_log row into a plain array the admin page (and the CSV export) can print:
 * readable actor / record / event, a before → after list, and the leftover facts.
 */
class AuditPresenter
{
    /** Property keys that must never be displayed, whatever a future caller stores. */
    private const HIDDEN = '/password|passwd|token|secret|license_image_data|card/i';

    /** Properties that are shown in their own sections rather than as plain facts. */
    private const STRUCTURAL = ['attributes', 'old', 'context', 'changes'];

    public static function present(Activity $activity): array
    {
        $props = $activity->properties?->toArray() ?? [];
        $context = (array) ($props['context'] ?? []);

        $actorEmail = $context['actor_email'] ?? $activity->causer?->email ?? null;
        $actorRole = $context['actor_role'] ?? null;

        $facts = [];
        foreach ($props as $key => $value) {
            if (! in_array($key, self::STRUCTURAL, true)) {
                $facts[self::label($key)] = self::value($key, $value);
            }
        }

        $subjectType = $activity->subject_type ? class_basename($activity->subject_type) : null;

        return [
            'id' => $activity->id,
            'when' => $activity->created_at,
            'category' => $activity->log_name,
            'category_label' => AuditCatalog::categoryLabel($activity->log_name),
            'category_classes' => AuditCatalog::categoryClasses($activity->log_name),
            'event' => $activity->event,
            'event_label' => AuditCatalog::eventLabel($activity->event),
            'event_classes' => AuditCatalog::eventClasses($activity->event),
            'description' => (string) $activity->description,
            'actor_email' => $actorEmail,
            'actor_role' => $actorRole ? ucwords(str_replace('_', ' ', $actorRole)) : null,
            'subject' => $subjectType && $activity->subject_id ? $subjectType.' #'.$activity->subject_id : null,
            'ip' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device' => self::device($context['user_agent'] ?? null),
            'changes' => self::changes($props),
            'tiers' => self::tiers($props),
            'facts' => $facts,
        ];
    }

    /** @return array<int,array{field:string,old:string,new:string}> */
    private static function changes(array $props): array
    {
        $old = (array) ($props['old'] ?? []);
        $new = (array) ($props['attributes'] ?? []);
        $rows = [];

        foreach (array_unique(array_merge(array_keys($new), array_keys($old))) as $field) {
            $rows[] = [
                'field' => self::label($field),
                'old' => array_key_exists($field, $old) ? self::value($field, $old[$field]) : '—',
                'new' => array_key_exists($field, $new) ? self::value($field, $new[$field]) : '—',
            ];
        }

        return $rows;
    }

    /** fare_rates_updated carries a list of tier edits instead of a flat attribute map. */
    private static function tiers(array $props): array
    {
        $rows = [];

        foreach ((array) ($props['changes'] ?? []) as $tier) {
            if (! is_array($tier) || ! isset($tier['km'])) {
                continue;
            }
            $rows[] = [
                'km' => $tier['km'].' km',
                'regular' => self::money($tier['regular']['old'] ?? null).' → '.self::money($tier['regular']['new'] ?? null),
                'discount' => self::money($tier['discount']['old'] ?? null).' → '.self::money($tier['discount']['new'] ?? null),
            ];
        }

        return $rows;
    }

    public static function label(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    public static function value(string $key, mixed $value): string
    {
        if (preg_match(self::HIDDEN, $key)) {
            return '••• hidden •••';
        }
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return mb_strimwidth((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200, '…');
        }

        return mb_strimwidth((string) $value, 0, 200, '…');
    }

    private static function money(mixed $v): string
    {
        return $v === null ? '—' : '₱'.number_format((float) $v, 2);
    }

    /** "Chrome on Windows" from a user-agent string — enough to tell devices apart, nothing more. */
    public static function device(?string $ua): ?string
    {
        if (! $ua) {
            return null;
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => null,
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return $browser && $os ? "{$browser} on {$os}" : ($browser ?? $os ?? mb_strimwidth($ua, 0, 40, '…'));
    }
}
