<?php

namespace Tests\Unit\Content;

use App\Content\Achievement;
use App\Content\ContentRepository;
use App\Content\Goal;
use App\Content\Project;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentRepositoryTest extends TestCase
{
    private string $base;

    private ContentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        // Build an isolated fixture content tree so these tests never depend on
        // the real /content files (which the owner is free to change).
        $this->base = storage_path('framework/testing/content-'.uniqid());
        File::ensureDirectoryExists($this->base.'/projects');

        File::put($this->base.'/profile.php', <<<'PHP'
        <?php
        return [
            'name' => 'Test Person',
            'role' => 'Tester',
            'tagline' => 'I test things.',
            'socials' => [['label' => 'GitHub', 'url' => 'https://example.com']],
        ];
        PHP);

        File::put($this->base.'/goals.php', <<<'PHP'
        <?php
        return [
            ['title' => 'Goal A', 'status' => 'in_progress', 'progress' => 50, 'target' => '2026-09', 'blurb' => 'a'],
            ['title' => 'Goal B', 'status' => 'planned', 'progress' => 0, 'target' => null, 'blurb' => 'b'],
        ];
        PHP);

        // Deliberately out of date order to prove sorting.
        File::put($this->base.'/achievements.php', <<<'PHP'
        <?php
        return [
            ['date' => '2024-01', 'title' => 'Older', 'metric' => null, 'blurb' => 'old'],
            ['date' => '2025-06', 'title' => 'Newer', 'metric' => 'big win', 'blurb' => 'new'],
        ];
        PHP);

        File::put($this->base.'/projects/old-one.md', <<<'MD'
        ---
        title: Old One
        year: 2020
        tags: [PHP]
        featured: false
        links:
          repo: https://example.com/old
        ---
        An older project snippet.
        MD);

        File::put($this->base.'/projects/new-one.md', <<<'MD'
        ---
        title: New One
        year: 2023
        tags: [Laravel, Vue]
        featured: true
        links:
          live: https://example.com/new
        ---
        A newer project snippet.
        MD);

        $this->repo = new ContentRepository($this->base);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->base);

        parent::tearDown();
    }

    public function test_profile_returns_the_raw_array(): void
    {
        $profile = $this->repo->profile();

        $this->assertSame('Test Person', $profile['name']);
        $this->assertSame('I test things.', $profile['tagline']);
    }

    public function test_goals_are_returned_as_dtos(): void
    {
        $goals = $this->repo->goals();

        $this->assertContainsOnlyInstancesOf(Goal::class, $goals);
        $this->assertSame('Goal A', $goals[0]->title);
        $this->assertSame(50, $goals[0]->progress);
        $this->assertSame('in_progress', $goals[0]->status);
        $this->assertNull($goals[1]->target);
    }

    public function test_achievements_are_dtos_sorted_newest_first(): void
    {
        $achievements = $this->repo->achievements();

        $this->assertContainsOnlyInstancesOf(Achievement::class, $achievements);
        $this->assertSame('Newer', $achievements[0]->title);
        $this->assertSame('big win', $achievements[0]->metric);
        $this->assertSame('Older', $achievements[1]->title);
        $this->assertNull($achievements[1]->metric);
    }

    public function test_projects_are_parsed_and_sorted_by_year_desc(): void
    {
        $projects = $this->repo->projects();

        $this->assertContainsOnlyInstancesOf(Project::class, $projects);
        $this->assertCount(2, $projects);

        // Newest first.
        $this->assertSame('New One', $projects[0]->title);
        $this->assertSame(2023, $projects[0]->year);
        $this->assertSame('new-one', $projects[0]->slug);
        $this->assertSame(['Laravel', 'Vue'], $projects[0]->tags);
        $this->assertTrue($projects[0]->featured);
        $this->assertSame('https://example.com/new', $projects[0]->links['live']);
        $this->assertSame('A newer project snippet.', $projects[0]->snippet);

        $this->assertSame('Old One', $projects[1]->title);
        $this->assertFalse($projects[1]->featured);
    }

    public function test_results_are_cached_and_flush_clears_them(): void
    {
        $this->assertCount(2, $this->repo->goals());

        // Add a third goal on disk; cached call should still return two.
        File::put($this->base.'/goals.php', <<<'PHP'
        <?php
        return [
            ['title' => 'Goal A', 'status' => 'in_progress', 'progress' => 50, 'target' => null, 'blurb' => 'a'],
            ['title' => 'Goal B', 'status' => 'planned', 'progress' => 0, 'target' => null, 'blurb' => 'b'],
            ['title' => 'Goal C', 'status' => 'planned', 'progress' => 0, 'target' => null, 'blurb' => 'c'],
        ];
        PHP);

        $this->assertCount(2, $this->repo->goals(), 'cache should still serve the old result');

        $this->repo->flush();

        $this->assertCount(3, $this->repo->goals(), 'flush should re-read from disk');
    }

    public function test_missing_projects_directory_returns_empty(): void
    {
        $repo = new ContentRepository(storage_path('framework/testing/does-not-exist-'.uniqid()));

        $this->assertSame([], $repo->projects());
    }
}
