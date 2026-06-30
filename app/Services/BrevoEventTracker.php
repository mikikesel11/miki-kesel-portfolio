<?php

namespace App\Services;

use Brevo\Brevo;
use Brevo\Event\Requests\CreateEventRequest;
use Brevo\Event\Types\CreateEventRequestIdentifiers;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends custom events to Brevo's v3 Events API via the official SDK
 * (getbrevo/brevo-php), authenticated with a standard API key (BREVO_API_KEY).
 *
 * No-ops when unconfigured, and never throws — a tracking hiccup must not affect
 * the request that triggered it.
 */
class BrevoEventTracker
{
    /**
     * @param array<string,mixed> $clientOptions Brevo SDK options — used in tests
     *        to inject a mock HTTP client (e.g. ['client' => $guzzle]).
     */
    public function __construct(private readonly array $clientOptions = [])
    {
    }

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
            (new Brevo($apiKey, $this->clientOptions))
                ->event
                ->createEvent(new CreateEventRequest([
                    'eventName' => 'contact_form_submitted',
                    'identifiers' => new CreateEventRequestIdentifiers(['emailId' => $email]),
                    'contactProperties' => [
                        'FIRSTNAME' => $firstName,
                        'LASTNAME' => $lastName,
                    ],
                ]));
        } catch (Throwable $e) {
            Log::warning('Brevo event tracking failed: '.$e->getMessage());
        }
    }
}
