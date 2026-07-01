<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

class BrevoMailTransportTest extends TestCase
{
    public function test_the_brevo_mailer_resolves_the_api_transport(): void
    {
        config([
            'services.brevo.api_key' => 'test-key',
            'mail.mailers.brevo' => ['transport' => 'brevo', 'key' => 'test-key'],
        ]);

        $transport = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(BrevoApiTransport::class, $transport);
    }
}
