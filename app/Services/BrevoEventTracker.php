<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends custom events to Brevo's v3 Events API
 * (https://developers.brevo.com/reference/create-event).
 *
 * Authenticated with a standard API key (`api-key` header), configured via
 * BREVO_API_KEY. No-ops when unconfigured, and never throws — a tracking hiccup
 * must not affect the request that triggered it.
 */
class BrevoEventTracker
{
    private const ENDPOINT = 'https://api.brevo.com/v3/events';

    public function contactFormSubmitted(string $email, ?string $name): void
    {
        $apiKey = config('services.brevo.api_key');

        if (! $apiKey) {
            return;
        }

        // Split the full name on whitespace: first token = FIRSTNAME, rest = LASTNAME.
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = array_shift($parts) ?? '';
        $lastName = implode(' ', $parts);

        try {
            Http::asJson()
                ->timeout(5)
                ->withHeaders([
                    'accept' => 'application/json',
                    'api-key' => $apiKey,
                ])
                ->post(self::ENDPOINT, [
                    'event_name' => 'contact_form_submitted',
                    'identifiers' => [
                        'email_id' => $email,
                    ],
                    'contact_properties' => [
                        'FIRSTNAME' => $firstName,
                        'LASTNAME' => $lastName,
                    ],
                ])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('Brevo event tracking failed: '.$e->getMessage());
        }
    }
}
