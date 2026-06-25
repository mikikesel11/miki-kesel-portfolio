<?php

namespace Tests\Unit\Content;

use App\Content\Achievement;
use App\Content\Goal;
use App\Content\Project;
use PHPUnit\Framework\TestCase;

class DtoTest extends TestCase
{
    public function test_goal_from_array_applies_defaults(): void
    {
        $goal = Goal::fromArray(['title' => 'Only a title']);

        $this->assertSame('Only a title', $goal->title);
        $this->assertSame('planned', $goal->status);
        $this->assertSame(0, $goal->progress);
        $this->assertNull($goal->target);
        $this->assertSame('', $goal->blurb);
    }

    public function test_achievement_from_array_handles_optional_metric(): void
    {
        $achievement = Achievement::fromArray(['date' => '2025-01', 'title' => 'Did a thing']);

        $this->assertSame('2025-01', $achievement->date);
        $this->assertNull($achievement->metric);
    }

    public function test_project_from_array_and_json_round_trip(): void
    {
        $data = [
            'slug' => 'demo',
            'title' => 'Demo',
            'year' => 2022,
            'tags' => ['A', 'B'],
            'featured' => true,
            'links' => ['repo' => 'https://example.com'],
            'snippet' => 'A snippet.',
        ];

        $project = Project::fromArray($data);

        $this->assertSame('demo', $project->slug);
        $this->assertSame(2022, $project->year);
        $this->assertTrue($project->featured);

        // The Vue island hydrates from this JSON shape — keep it stable.
        $this->assertSame($data, $project->jsonSerialize());
        $this->assertSame($data, json_decode(json_encode($project), true));
    }
}
