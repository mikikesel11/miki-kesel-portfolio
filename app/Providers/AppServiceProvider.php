<?php

namespace App\Providers;

use App\Content\ContentRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ContentRepository::class,
            fn () => new ContentRepository(base_path('content')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Send mail via Brevo's transactional API (reuses the api-key, no SMTP).
        // Enable with MAIL_MAILER=brevo. Same pattern Laravel uses for Mailgun/Postmark.
        Mail::extend('brevo', function (array $config) {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', $config['key'] ?? config('services.brevo.api_key')),
            );
        });
    }
}
