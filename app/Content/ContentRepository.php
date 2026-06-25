<?php

namespace App\Content;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\YamlFrontMatter\YamlFrontMatter;

/**
 * Reads the flat-file content under /content and returns typed data.
 *
 * The cache only ever stores plain arrays of primitives (never DTO objects),
 * so there are no class-serialization pitfalls; DTOs are rehydrated on the way
 * out. Caches are flushed on deploy via `php artisan content:flush`, so a
 * `git push` is all it takes to update the site.
 */
class ContentRepository
{
    private const CACHE_PREFIX = 'content:';

    public function __construct(private readonly string $basePath)
    {
    }

    /** @return array<string,mixed> */
    public function profile(): array
    {
        return $this->remember('profile', fn () => require $this->path('profile.php'));
    }

    /** @return Goal[] */
    public function goals(): array
    {
        $raw = $this->remember('goals', fn () => require $this->path('goals.php'));

        return array_map(Goal::fromArray(...), $raw);
    }

    /** @return Achievement[] sorted newest-first */
    public function achievements(): array
    {
        $raw = $this->remember('achievements', function () {
            $items = require $this->path('achievements.php');

            usort($items, fn (array $a, array $b) => strcmp($b['date'], $a['date']));

            return $items;
        });

        return array_map(Achievement::fromArray(...), $raw);
    }

    /** @return Project[] sorted newest-first */
    public function projects(): array
    {
        $raw = $this->remember('projects', function () {
            $dir = $this->path('projects');

            if (! File::isDirectory($dir)) {
                return [];
            }

            return collect(File::files($dir))
                ->filter(fn ($file) => $file->getExtension() === 'md')
                ->map(fn ($file) => $this->parseProject($file->getPathname()))
                ->sortByDesc('year')
                ->values()
                ->all();
        });

        return array_map(Project::fromArray(...), $raw);
    }

    /** @return array<string,mixed> primitive representation, safe to cache */
    private function parseProject(string $file): array
    {
        $document = YamlFrontMatter::parseFile($file);

        return [
            'slug' => pathinfo($file, PATHINFO_FILENAME),
            'title' => $document->matter('title') ?? 'Untitled',
            'year' => (int) ($document->matter('year') ?? 0),
            'tags' => $document->matter('tags') ?? [],
            'featured' => (bool) ($document->matter('featured') ?? false),
            'links' => $document->matter('links') ?? [],
            'snippet' => trim($document->body()),
        ];
    }

    private function remember(string $key, callable $callback): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, $callback);
    }

    public function flush(): void
    {
        foreach (['profile', 'goals', 'achievements', 'projects'] as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    private function path(string $relative): string
    {
        return rtrim($this->basePath, '/').'/'.$relative;
    }
}
