<?php

namespace Tests\Feature;

use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'message' => 'Hello there, this is a genuine test message.',
    ];

    private function fill(): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test('contact-form')
            ->set('name', $this->valid['name'])
            ->set('email', $this->valid['email'])
            ->set('message', $this->valid['message']);
    }

    public function test_the_form_component_renders(): void
    {
        Livewire::test('contact-form')
            ->assertOk()
            ->assertSet('sent', false);
    }

    public function test_a_valid_submission_sends_a_brevo_event_when_configured(): void
    {
        config(['services.brevo.ma_key' => 'test-ma-key']);
        Mail::fake();
        Http::fake(['in-automate.brevo.com/*' => Http::response([], 200)]);

        $this->fill()->call('submit')->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://in-automate.brevo.com/api/v2/trackEvent'
                && $request->hasHeader('ma-key', 'test-ma-key')
                && $request['event'] === 'contact_form_submitted'
                && $request['email'] === $this->valid['email']
                && $request['properties']['FIRSTNAME'] === 'Jane'
                && $request['properties']['LASTNAME'] === 'Doe';
        });
    }

    public function test_no_brevo_event_is_sent_when_unconfigured(): void
    {
        config(['services.brevo.ma_key' => null]);
        Mail::fake();
        Http::fake();

        $this->fill()->call('submit')->assertHasNoErrors();

        Http::assertNothingSent();
    }

    public function test_it_requires_all_fields(): void
    {
        Livewire::test('contact-form')
            ->call('submit')
            ->assertHasErrors([
                'name' => 'required',
                'email' => 'required',
                'message' => 'required',
            ]);

        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_it_validates_email_format_and_message_length(): void
    {
        Livewire::test('contact-form')
            ->set('name', 'Jane Doe')
            ->set('email', 'not-an-email')
            ->set('message', 'too short')
            ->call('submit')
            ->assertHasErrors(['email' => 'email', 'message' => 'min']);
    }

    public function test_a_valid_submission_is_stored(): void
    {
        Mail::fake();

        $this->fill()->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('contact_submissions', [
            'name' => $this->valid['name'],
            'email' => $this->valid['email'],
            'message' => $this->valid['message'],
        ]);

        $submission = ContactSubmission::first();
        $this->assertNotNull($submission->ip);
    }

    public function test_a_valid_submission_queues_a_notification_to_the_owner(): void
    {
        Mail::fake();

        $this->fill()->call('submit');

        Mail::assertQueued(
            ContactSubmissionReceived::class,
            fn (ContactSubmissionReceived $mail) => $mail->hasTo(config('mail.from.address'))
                && $mail->submission->email === $this->valid['email'],
        );
    }

    public function test_it_resets_fields_and_shows_a_success_state(): void
    {
        Mail::fake();

        $this->fill()->call('submit')
            ->assertSet('sent', true)
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('message', '');
    }

    public function test_a_mail_failure_does_not_break_a_successful_submission(): void
    {
        // Simulate the mail transport throwing (e.g. SMTP down). With the sync
        // queue this happens inline, but the stored submission + success state
        // must survive it.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail server down'));

        $this->fill()->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        $this->assertDatabaseHas('contact_submissions', [
            'email' => $this->valid['email'],
        ]);
    }

    public function test_the_honeypot_silently_drops_bot_submissions(): void
    {
        Mail::fake();

        $this->fill()
            ->set('website', 'http://spam.example')
            ->call('submit')
            ->assertSet('sent', true)
            ->assertHasNoErrors();

        // Looks successful to the bot, but nothing is stored or emailed.
        $this->assertDatabaseCount('contact_submissions', 0);
        Mail::assertNothingQueued();
    }

    public function test_it_rate_limits_repeated_submissions(): void
    {
        Mail::fake();

        $component = Livewire::test('contact-form');

        for ($i = 0; $i < 3; $i++) {
            $component
                ->set('name', $this->valid['name'])
                ->set('email', $this->valid['email'])
                ->set('message', $this->valid['message'])
                ->call('submit')
                ->assertHasNoErrors();
        }

        // The 4th attempt within the window is blocked.
        $component
            ->set('name', $this->valid['name'])
            ->set('email', $this->valid['email'])
            ->set('message', $this->valid['message'])
            ->call('submit')
            ->assertHasErrors('message');

        $this->assertDatabaseCount('contact_submissions', 3);
        Mail::assertQueuedCount(3);
    }
}
