<?php

namespace App\Content;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Throwable;

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

    /** @return Certification[] sorted newest-first */
    public function certifications(): array
    {
        $raw = $this->remember('certifications', function () {
            $items = require $this->path('certifications.php');

            usort($items, fn (array $a, array $b) => strcmp($b['date'], $a['date']));

            return $items;
        });

        return array_map(Certification::fromArray(...), $raw);
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
                ->filter() // drop any files that failed to parse
                ->sortByDesc('year')
                ->values()
                ->all();
        });

        return array_map(Project::fromArray(...), $raw);
    }

    /**
     * Parse one project markdown file. A malformed file (e.g. bad YAML
     * front-matter) is logged and skipped rather than 500-ing the whole page.
     *
     * @return array<string,mixed>|null primitive representation, safe to cache
     */
    private function parseProject(string $file): ?array
    {
        try {
            $document = YamlFrontMatter::parseFile($file);
        } catch (Throwable $e) {
            Log::warning('Skipped malformed project content file: '.$file, [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

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
        foreach (['profile', 'goals', 'certifications', 'projects'] as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    private function path(string $relative): string
    {
        return rtrim($this->basePath, '/').'/'.$relative;
    }
}
