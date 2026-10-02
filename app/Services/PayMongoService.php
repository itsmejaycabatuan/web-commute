<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client for PayMongo's REST API (api.paymongo.com).
 *
 * Deliberately SDK-free: the official `paymongo/paymongo-php` package is still
 * tagged 0.0.0 (Nov 2022). PayMongo uses JSON:API, which the Http facade
 * handles cleanly and which makes every call trivially fakeable in tests.
 *
 * Authentication is HTTP Basic with the secret key as the username and an
 * empty password.
 */
class PayMongoService
{
    /**
     * Hosts PayMongo actually serves. Anything else is a typo.
     */
    public const SANDBOX_HOST = 'api.paymongo.com';
    public const LIVE_HOST = 'api.live.paymongo.com';

    /**
     * Cached result of the configuration audit, so the log gets one line
     * rather than one per request.
     *
     * @var array<int, string>|null
     */
    protected static ?array $configProblems = null;

    /**
     * Audit the gateway configuration.
     *
     * A misconfigured gateway is dangerous in one direction only: live keys
     * pointed at the wrong host (or armed in a dev environment) can take real
     * money from a real commuter. These checks make that impossible to do by
     * accident — `enabled()` returns false and the gateway refuses to run.
     *
     * @return array<int, string> human-readable problems; empty means safe
     */
    public function configProblems(): array
    {
        if (self::$configProblems !== null) {
            return self::$configProblems;
        }

        $problems = [];

        if (! config('paymongo.enabled')) {
            return self::$configProblems = [];
        }

        $secret = (string) config('paymongo.secret_key');
        $host = (string) parse_url((string) config('paymongo.base_url'), PHP_URL_HOST);

        if ($secret === '') {
            $problems[] = 'PAYMONGO_ENABLED is true but PAYMONGO_SECRET_KEY is empty.';
        }

        if (! in_array($host, [self::SANDBOX_HOST, self::LIVE_HOST], true)) {
            $problems[] = "PAYMONGO_BASE_URL points at an unknown host '{$host}'.";
        }

        $isLiveKey = str_contains($secret, '_live_') || str_starts_with($secret, 'sk_live_');
        $isTestKey = str_contains($secret, '_test_') || str_starts_with($secret, 'sk_test_');

        if ($host === self::SANDBOX_HOST && $isLiveKey) {
            $problems[] = 'A LIVE secret key is configured against the SANDBOX host. Refusing to run.';
        }

        if ($host === self::LIVE_HOST && $isTestKey) {
            $problems[] = 'A SANDBOX secret key is configured against the LIVE host. Refusing to run.';
        }

        if ($isLiveKey && ! app()->environment('production')) {
            $problems[] = 'A LIVE secret key is armed in the '.app()->environment().' environment. Refusing to run outside production.';
        }

        if (! empty($problems)) {
            Log::critical('PAYMONGO CONFIGURATION REFUSED: '.implode(' ', $problems), [
                'paymongo.base_url' => config('paymongo.base_url'),
                'key_type' => $isLiveKey ? 'live' : ($isTestKey ? 'test' : 'unknown'),
                'app_env' => app()->environment(),
            ]);
        }

        return self::$configProblems = $problems;
    }

    /**
     * The gateway runs only when it is enabled AND the configuration passes the
     * safety audit.
     */
    public function enabled(): bool
    {
        return (bool) config('paymongo.enabled')
            && ! empty(config('paymongo.secret_key'))
            && empty($this->configProblems());
    }

    /** Test seam: forget the cached audit result. */
    public static function flushConfigCache(): void
    {
        self::$configProblems = null;
    }

    /**
     * True when the selected method must be settled through the gateway
     * rather than from the commuter's wallet.
     */
    public function isGatewayMethod(?string $method): bool
    {
        return in_array($method, self::gatewayMethods(), true);
    }

    /**
     * Gateway-backed methods for a fare payment. 'Wallet' is deliberately
     * absent — it is settled internally.
     */
    public static function gatewayMethods(): array
    {
        return ['GCash', 'Maya'];
    }

    /**
     * Convert pesos to the minor unit PayMongo expects (centavos).
     */
    public function toMinorUnits(float $amount): int
    {
        return (int) round($amount * (int) config('paymongo.minor_unit_factor', 100));
    }

    /**
     * Map a fare payment method to the PayMongo channel that must be allowed
     * on the checkout session. PayMongo rejects a session without an explicit
     * `payment_method_types`.
     */
    public static function channelFor(string $method): string
    {
        return match (strtolower($method)) {
            'gcash' => 'gcash',
            'maya' => 'maya',
            'card', 'credit_card' => 'card',
            default => throw new RuntimeException("Unsupported payment method for the gateway: {$method}"),
        };
    }

    /**
     * Create a payment intent for a fare.
     *
     * @param  array<int, string>  $paymentMethodsAllowed  e.g. ['gcash']
     *
     * @throws RuntimeException on any gateway/API failure — callers must treat
     *                         the payment as not started.
     */
    public function createPaymentIntent(float $amount, string $description, array $metadata = [], array $paymentMethodsAllowed = ['card']): array
    {
        $response = $this->client()->post($this->url('/payment_intents'), [
            'data' => [
                'attributes' => array_filter([
                    'currency' => config('paymongo.currency', 'PHP'),
                    'amount' => $this->toMinorUnits($amount),
                    'description' => $description,
                    'statement_descriptor' => config('paymongo.statement_descriptor'),
                    'payment_method_allowed' => array_values($paymentMethodsAllowed),
                    'metadata' => $metadata,
                ], fn ($v) => $v !== null && $v !== '' && $v !== []),
            ],
        ]);

        return $this->normalize($this->extract($response, 'payment intent'));
    }

    /**
     * Create a hosted checkout session for a fare.
     *
     * PayMongo creates the payment intent as part of the session, so this is a
     * single call. The API requires `line_items` + `payment_method_types`; the
     * older `payment_intent_id` form is rejected.
     *
     * @throws RuntimeException on failure.
     */
    public function createCheckoutSession(
        float $amount,
        string $description,
        string $successUrl,
        string $cancelUrl,
        string $channel,
        array $metadata = []
    ): array {
        $response = $this->client()->post($this->url('/checkout_sessions'), [
            'data' => [
                'attributes' => array_filter([
                    'line_items' => [[
                        'currency' => config('paymongo.currency', 'PHP'),
                        'quantity' => 1,
                        'name' => $description,
                        'amount' => $this->toMinorUnits($amount),
                    ]],
                    'payment_method_types' => [$channel],
                    'description' => $description,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'send_back_receipt' => false,
                    'metadata' => $metadata,
                ], fn ($v) => $v !== null && $v !== '' && $v !== []),
            ],
        ]);

        $data = $this->extract($response, 'checkout session');
        $attributes = $data['attributes'] ?? [];

        // The session embeds the payment intent it created.
        $intentId = $attributes['payment_intent']['id']
            ?? $attributes['payment_intent_id']
            ?? null;

        return [
            'id' => $data['id'] ?? null,
            'checkout_url' => $attributes['checkout_url'] ?? null,
            'payment_intent_id' => $intentId,
            'status' => $attributes['status'] ?? null,
        ];
    }

    /**
     * Retrieve a payment intent — used by the return URL to reconcile a payment
     * whose webhook was missed.
     *
     * @throws RuntimeException on failure.
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $response = $this->client()->get($this->url('/payment_intents/'.$paymentIntentId));

        return $this->normalize($this->extract($response, 'payment intent'));
    }

    /**
     * Flatten a JSON:API resource.
     *
     * PayMongo nests everything except `id` under `data.attributes`, so
     * `$body['data']['status']` does not exist — it is
     * `$body['data']['attributes']['status']`. Normalising in one place keeps
     * every caller correct.
     */
    protected function normalize(array $data): array
    {
        $attributes = $data['attributes'] ?? [];

        return [
            'id' => $data['id'] ?? null,
            'status' => $attributes['status'] ?? null,
            'reference_id' => $attributes['reference_id'] ?? null,
            'checkout_url' => $attributes['checkout_url'] ?? null,
            'metadata' => $attributes['metadata'] ?? [],
            'amount' => $attributes['amount'] ?? null,
        ];
    }

    /**
 * * Settled statuses PayMongo reports on a payment intent.
     *
     * Verified live against the sandbox: a successful GCash payment puts the
     * intent in **succeeded** (NOT "paid" — that was the original bug, which
     * left successful payments stuck on the pending screen forever).
     */
    public function isSettled(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['succeeded', 'paid', 'charged', 'complete', 'completed'], true);
    }

    /** Terminal-failure statuses. */
    public function isFailed(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['failed', 'canceled', 'cancelled', 'expired'], true);
    }

    /**
     * Verify a webhook signature.
     *
     * PayMongo signs the raw request body with the secret key and delivers two
     * headers: `paymongo-te-signature` = HMAC-SHA256(secret, "{timestamp}.{body}")
     * and `paymongo-signature` = HMAC-SHA256(secret, body). Both are accepted so
     * the integration survives either delivery format.
     */
    public function verifySignature(string $rawBody, ?string $teSignature, ?string $signature, ?string $timestamp = null): bool
    {
        $secret = config('paymongo.webhook_secret') ?: config('paymongo.secret_key');

        if (empty($secret) || $rawBody === '') {
            return false;
        }

        $candidates = [];

        if ($signature) {
            $candidates[] = hash_hmac('sha256', $rawBody, $secret);
        }

        if ($teSignature) {
            $candidates[] = hash_hmac('sha256', $timestamp ? $timestamp.'.'.$rawBody : $rawBody, $secret);
        }

        foreach ($candidates as $expected) {
            foreach (array_filter([$signature, $teSignature]) as $provided) {
                if (hash_equals($expected, $provided)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pull the ids/status out of a PayMongo webhook envelope.
     *
     * Returns: ['event_type', 'payment_intent_id', 'reference_id', 'transaction_id']
     */
    public function parseWebhook(string $rawBody): array
    {
        $payload = json_decode($rawBody, true);

        $attributes = $payload['data']['attributes'] ?? [];
        $eventType = $attributes['type'] ?? null;

        // Payment events:  data.attributes.data.id = payment intent id
        //                  data.attributes.data.attributes.{reference_id,metadata}
        $resource = $attributes['data'] ?? [];
        $resourceAttributes = $resource['attributes'] ?? [];

        $intentId = $resource['id'] ?? $resourceAttributes['payment_intent_id'] ?? null;
        $referenceId = $resourceAttributes['reference_id'] ?? null;
        $transactionId = $resourceAttributes['metadata']['transaction_id']
            ?? $attributes['metadata']['transaction_id']
            ?? null;

        // Fall back to the top-level resource id for some event shapes.
        if (! $intentId && $eventType === null && isset($payload['data']['id'])) {
            $intentId = $payload['data']['id'];
        }

        return [
            'event_type' => $eventType,
            'payment_intent_id' => $intentId,
            'reference_id' => $referenceId,
            'transaction_id' => $transactionId,
        ];
    }

    protected function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('paymongo.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('paymongo.base_url'), '/').$path;
    }

    /**
     * @throws RuntimeException when the gateway rejects the call or returns an
     *                          error envelope.
     */
    protected function extract(Response $response, string $what): array
    {
        $body = $response->json();

        if ($response->failed()) {
            Log::error('PAYMONGO '.$what.' FAILED', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new RuntimeException('PayMongo rejected the '.$what.' request.');
        }

        // JSON:API errors arrive under `errors`, even on a 2xx.
        if (isset($body['errors'])) {
            Log::error('PAYMONGO '.$what.' ERROR ENVELOPE', ['body' => $body]);

            throw new RuntimeException($body['errors'][0]['detail'] ?? 'PayMongo returned an error.');
        }

        return $body['data'] ?? [];
    }
}