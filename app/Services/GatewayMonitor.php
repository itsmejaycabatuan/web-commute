<?php

namespace App\Services;

use App\Notifications\GatewayAlert;
use App\Support\Notifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Counts payment-gateway errors and tells the Admins when they pile up: `gateway_failure_threshold`
 * errors inside `gateway_failure_window_minutes` send one email for that window, however many more follow.
 */
class GatewayMonitor
{
    private const COUNT_KEY = 'gateway-monitor.failures';
    private const LAST_KEY = 'gateway-monitor.last';
    private const ALERTED_KEY = 'gateway-monitor.alerted';

    public function recordFailure(string $context): int
    {
        $window = max(1, (int) config('alerts.gateway_failure_window_minutes', 15));
        $threshold = max(1, (int) config('alerts.gateway_failure_threshold', 3));
        $ttl = now()->addMinutes($window);

        Cache::add(self::COUNT_KEY, 0, $ttl);
        $count = (int) Cache::increment(self::COUNT_KEY);
        Cache::put(self::LAST_KEY, $context, $ttl);

        Log::warning('Payment gateway failure recorded.', ['context' => $context, 'failures_in_window' => $count]);

        if ($count >= $threshold && Cache::add(self::ALERTED_KEY, true, $ttl)) {
            Notifier::toRole('admin', new GatewayAlert($count, $window, $context));
        }

        return $count;
    }

    /** Test seam: forget what has been counted. */
    public function reset(): void
    {
        Cache::forget(self::COUNT_KEY);
        Cache::forget(self::LAST_KEY);
        Cache::forget(self::ALERTED_KEY);
    }
}
