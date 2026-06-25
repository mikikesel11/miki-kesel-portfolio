<?php

namespace Tests\Feature;

use App\Content\ContentRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentFlushCommandTest extends TestCase
{
    public function test_it_runs_and_reports_success(): void
    {
        $this->artisan('content:flush')
            ->expectsOutputToContain('Content cache flushed.')
            ->assertExitCode(0);
    }

    public function test_it_clears_cached_content(): void
    {
        // Warm the cache via the repository, then confirm the command empties it.
        app(ContentRepository::class)->goals();
        $this->assertTrue(Cache::has('content:goals'));

        $this->artisan('content:flush')->assertExitCode(0);

        $this->assertFalse(Cache::has('content:goals'));
    }
}
