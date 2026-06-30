<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends custom events to Brevo's REST tracking endpoint
 * (https://developers.brevo.com/docs/track-custom-events-rest).
 *
 * Authenticated with the Marketing Automation key (`ma-key`), configured via
 * BREVO_MA_KEY. No-ops when unconfigured, and never throws — a tracking hiccup
 * must not affect the request that triggered it.
 */
class BrevoEventTracker
{
    private const ENDPOINT = 'https://in-automate.brevo.com/api/v2/trackEvent';

    public function contactFormSubmitted(string $email, ?string $name): void
    {
        $maKey = config('services.brevo.ma_key');

        if (! $maKey) {
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
                    'ma-key' => $maKey,
                ])
                ->post(self::ENDPOINT, [
                    'email' => $email,
                    'event' => 'contact_form_submitted',
                    // Reserved keys `email`/`event` must not appear in properties.
                    'properties' => [
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
