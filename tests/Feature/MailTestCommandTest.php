<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    public function test_it_sends_a_test_email_and_reports_success(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('someone@example.com')
            ->expectsOutputToContain('Sent.')
            ->assertExitCode(0);
    }

    public function test_it_defaults_the_recipient_to_the_from_address(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'owner@example.com']);

        $this->artisan('mail:test')
            ->expectsOutputToContain('owner@example.com')
            ->assertExitCode(0);
    }

    public function test_it_reports_the_real_error_when_the_transport_fails(): void
    {
        // Force the transport to blow up so the catch block is exercised: the
        // command must surface the error and exit non-zero, never swallow it.
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('transport exploded'));

        $this->artisan('mail:test', ['to' => 'someone@example.com'])
            ->expectsOutputToContain('SEND FAILED')
            ->expectsOutputToContain('transport exploded')
            ->assertExitCode(1);
    }
}
