<?php

namespace Tests\Unit\Services;

use App\Services\BrevoEventTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class BrevoEventTrackerTest extends TestCase
{
    /** @param array<int,mixed> $history */
    private function trackerWithHistory(array &$history): BrevoEventTracker
    {
        $stack = HandlerStack::create(new MockHandler([new Response(204)]));
        $stack->push(Middleware::history($history));

        return new BrevoEventTracker(['client' => new Client(['handler' => $stack])]);
    }

    public function test_it_posts_a_contact_event_with_email_and_split_name(): void
    {
        config(['services.brevo.api_key' => 'test-key']);
        $history = [];

        $this->trackerWithHistory($history)
            ->contactFormSubmitted('jane@example.com', 'Jane Q Doe');

        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('/v3/events', (string) $request->getUri());
        $this->assertSame('test-key', $request->getHeaderLine('api-key'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('contact_form_submitted', $body['event_name']);
        $this->assertSame('jane@example.com', $body['identifiers']['email_id']);
        $this->assertSame('Jane', $body['contact_properties']['FIRSTNAME']);
        $this->assertSame('Q Doe', $body['contact_properties']['LASTNAME']);
    }

    public function test_it_no_ops_when_unconfigured(): void
    {
        config(['services.brevo.api_key' => null]);
        $history = [];

        $this->trackerWithHistory($history)
            ->contactFormSubmitted('jane@example.com', 'Jane Doe');

        $this->assertCount(0, $history);
    }
}
