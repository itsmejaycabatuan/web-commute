<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

/**
 * The one place the application writes to the audit trail (`activity_log`).
 *
 * What is audited:
 *  - changes to business records, automatically, through App\Models\Concerns\Audited
 *    (who changed which record, old → new values);
 *  - security and account events that are not a model change (sign-in, sign-out, password
 *    changes, suspensions, approvals, fare-guide uploads), through Audit::log().
 *
 * What is NOT audited: page views, map polling, location broadcasts, clock-in status toggles,
 * theme preferences. Diagnostics (database / gateway failures) go to storage/logs, not here.
 *
 * Every entry is stamped with the request context by enrich() (IP, user agent, who acted).
 */
class Audit
{
    // log_name values — the admin page filters on these.
    public const AUTH = 'auth';
    public const ACCOUNT = 'account';
    public const DRIVER = 'driver';
    public const FLEET = 'fleet';
    public const FARE = 'fare';
    public const VIOLATION = 'violation';
    public const TIMEKEEPING = 'timekeeping';
    public const PAYMENT = 'payment';
    public const SYSTEM = 'system';

    /**
     * @param  string      $category    one of the constants above (stored as log_name)
     * @param  string      $event       machine name, e.g. login, login_failed, suspended (stored as event)
     * @param  string      $description short sentence a human can read, without ":" placeholders
     * @param  Model|null  $subject     the record the event is about
     * @param  array       $properties  extra facts (never passwords, tokens or card data)
     * @param  Model|null  $causer      who did it; defaults to the signed-in user
     */
    public static function log(
        string $category,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null
    ): ?Activity {
        try {
            $logger = activity($category)->event($event);

            if ($subject) {
                $logger->performedOn($subject);
            }
            if ($causer) {
                $logger->causedBy($causer);
            }
            if ($properties) {
                $logger->withProperties($properties);
            }

            return $logger->log($description);
        } catch (\Throwable $e) {
            // Auditing must never take a request down, but a lost entry must not be silent either.
            Log::error('AUDIT LOG FAILED: '.$e->getMessage(), [
                'category' => $category,
                'event' => $event,
                'description' => $description,
            ]);

            return null;
        }
    }

    /**
     * Stamps request context onto an activity just before it is saved.
     * Hooked in AppServiceProvider (Activity::creating). Adds properties.context:
     * ip, user_agent, actor_email, actor_role. Existing keys are never overwritten.
     */
    public static function enrich(Activity $activity): void
    {
        try {
            $properties = $activity->properties instanceof Collection
                ? $activity->properties
                : collect($activity->properties ?? []);

            $context = (array) $properties->get('context', []);

            if (! app()->runningInConsole() || app()->runningUnitTests()) {
                $request = request();
                $context += array_filter([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
                ]);
            }

            $causer = $activity->causer;
            if ($causer) {
                $context += array_filter([
                    // Snapshot: the account may be deleted later, and the log must still say who acted.
                    'actor_email' => $causer->email ?? null,
                    'actor_role' => method_exists($causer, 'getRoleNames') ? $causer->getRoleNames()->first() : null,
                ]);
            }

            if ($context) {
                $activity->properties = $properties->put('context', $context);
            }
        } catch (\Throwable $e) {
            Log::warning('AUDIT CONTEXT FAILED: '.$e->getMessage());
        }
    }
}
