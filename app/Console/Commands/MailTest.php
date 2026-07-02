<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {to? : Recipient address (defaults to MAIL_FROM_ADDRESS)}';

    protected $description = 'Send a test email through the configured mailer and surface the real transport error';

    public function handle(): int
    {
        $to = $this->argument('to') ?: config('mail.from.address');
        $key = (string) config('services.brevo.api_key');

        // Show exactly what the runtime will use — the usual cause of silent
        // failures is a stale config cache or an unverified From address.
        $this->line('Mailer:        '.config('mail.default'));
        $this->line('From:          '.config('mail.from.address').' ('.config('mail.from.name').')');
        $this->line('To:            '.$to);
        $this->line('BREVO_API_KEY: '.($key === '' ? 'NOT SET' : substr($key, 0, 10).'…'.substr($key, -4)));
        $this->newLine();

        try {
            // send() (not queue()) runs inline and lets the transport exception
            // bubble up, so a Brevo rejection is printed instead of swallowed.
            Mail::raw('Test email from the portfolio site at '.now()->toDateTimeString().'.', function ($message) use ($to) {
                $message->to($to)->subject('Portfolio mail:test');
            });
        } catch (\Throwable $e) {
            $this->error('SEND FAILED: '.get_class($e));
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (and Brevo → Transactional → Logs).');

        return self::SUCCESS;
    }
}
