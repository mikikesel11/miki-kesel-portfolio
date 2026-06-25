<?php

namespace Tests\Feature;

use App\Content\ContentRepository;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    private function content(): ContentRepository
    {
        return app(ContentRepository::class);
    }

    public function test_home_page_loads_successfully(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_it_shows_profile_identity_and_seo_metadata(): void
    {
        $profile = $this->content()->profile();

        $response = $this->get('/');

        $response->assertSee($profile['name']);
        $response->assertSee($profile['role']);
        $response->assertSee($profile['tagline']);

        // <title> and meta description are driven by the profile.
        $response->assertSee('<title>'.e($profile['name'].' — '.$profile['role']).'</title>', false);
        $response->assertSee('content="'.e($profile['tagline']).'"', false);
    }

    public function test_it_renders_every_section_anchor(): void
    {
        $response = $this->get('/');

        foreach (['goals', 'achievements', 'projects', 'contact'] as $anchor) {
            $response->assertSee('id="'.$anchor.'"', false);
        }
    }

    public function test_it_renders_all_goals(): void
    {
        $response = $this->get('/');

        foreach ($this->content()->goals() as $goal) {
            $response->assertSee($goal->title);
            $response->assertSee($goal->blurb);
        }
    }

    public function test_it_renders_all_achievements_including_metrics(): void
    {
        $response = $this->get('/');

        foreach ($this->content()->achievements() as $achievement) {
            $response->assertSee($achievement->title);
            if ($achievement->metric) {
                $response->assertSee($achievement->metric);
            }
        }
    }

    public function test_it_mounts_the_vue_projects_island_with_hydration_data(): void
    {
        $response = $this->get('/');

        $response->assertSee('id="projects-explorer"', false);
        $response->assertSee('data-projects=', false);

        // Every project must be present for the client-side explorer to render.
        foreach ($this->content()->projects() as $project) {
            $response->assertSee($project->title);
        }
    }

    public function test_it_mounts_the_livewire_contact_form(): void
    {
        $this->get('/')
            ->assertSeeLivewire('contact-form')
            ->assertSee('wire:submit', false);
    }

    public function test_it_renders_the_theme_toggle(): void
    {
        $this->get('/')->assertSee('aria-label="Toggle theme"', false);
    }

    public function test_it_renders_social_links_in_the_footer(): void
    {
        $response = $this->get('/');

        foreach ($this->content()->profile()['socials'] as $social) {
            $response->assertSee($social['label']);
            $response->assertSee($social['url'], false);
        }
    }

    public function test_cv_download_button_is_hidden_when_no_pdf_present(): void
    {
        $cvPath = $this->content()->profile()['cv_path'] ?? null;

        if ($cvPath && File::exists(public_path($cvPath))) {
            $this->markTestSkipped('A real CV PDF is present; cannot assert the hidden state.');
        }

        $this->get('/')->assertDontSee('Download CV');
    }

    public function test_cv_download_button_appears_when_pdf_exists(): void
    {
        $cvPath = $this->content()->profile()['cv_path'] ?? 'cv/test-cv.pdf';
        $full = public_path($cvPath);
        $preExisting = File::exists($full);

        if (! $preExisting) {
            File::ensureDirectoryExists(dirname($full));
            File::put($full, '%PDF-1.4 test');
        }

        try {
            $this->get('/')
                ->assertSee('Download CV')
                ->assertSee('href="'.asset($cvPath).'"', false);
        } finally {
            if (! $preExisting) {
                File::delete($full);
            }
        }
    }
}
