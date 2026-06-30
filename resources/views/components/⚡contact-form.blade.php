<?php

use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|min:2|max:120')]
    public string $name = '';

    #[Validate('required|email|max:190')]
    public string $email = '';

    #[Validate('required|string|min:10|max:2000')]
    public string $message = '';

    // Honeypot: real users never fill this; bots usually do.
    public string $website = '';

    public bool $sent = false;

    public function submit(): void
    {
        // Silently succeed for bots that tripped the honeypot.
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $this->validate();

        $key = 'contact:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 3)) {
            $this->addError('message', 'Too many messages just now — please try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, decaySeconds: 600);

        $submission = ContactSubmission::create([
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);

        // Notify the owner, but never let a mail failure break the UX — the
        // submission is already stored. This matters with the sync queue, where
        // the send runs inline during the request instead of in a worker.
        try {
            Mail::to(config('mail.from.address'))->queue(new ContactSubmissionReceived($submission));
        } catch (\Throwable $e) {
            Log::warning('Contact notification failed to send: '.$e->getMessage());
        }

        $this->reset(['name', 'email', 'message']);
        $this->sent = true;
    }
};
?>

<div>
    @if ($sent)
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            <p class="font-medium">Thanks — your message is on its way.</p>
            <p class="mt-1 text-sm">I'll get back to you soon.</p>
            <button type="button" wire:click="$set('sent', false)" class="mt-3 text-sm underline underline-offset-4">
                Send another
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            {{-- Honeypot: visually hidden, ignored by humans --}}
            <div class="hidden" aria-hidden="true">
                <label>Website<input type="text" wire:model="website" tabindex="-1" autocomplete="off" /></label>
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                <input id="name" type="text" wire:model.blur="name"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:focus:border-zinc-500 dark:focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <input id="email" type="email" wire:model.blur="email"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:focus:border-zinc-500 dark:focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Message</label>
                <textarea id="message" rows="5" wire:model.blur="message"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:focus:border-zinc-500 dark:focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
                @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                @click="trackBrevo()"
                class="rounded-lg bg-accent px-5 py-2.5 font-medium text-white transition hover:bg-accent-hover disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Send message</span>
                <span wire:loading wire:target="submit">Sending…</span>
            </button>
        </form>
    @endif
</div>
