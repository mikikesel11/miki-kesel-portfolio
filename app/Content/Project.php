<?php

namespace App\Content;

use JsonSerializable;

/**
 * A project snippet parsed from content/projects/*.md.
 * JsonSerializable so it can be embedded as a JSON blob for the Vue explorer.
 */
readonly class Project implements JsonSerializable
{
    public function __construct(
        public string $slug,
        public string $title,
        public int $year,
        public array $tags,
        public bool $featured,
        public array $links,
        public string $snippet,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            slug: $data['slug'],
            title: $data['title'],
            year: (int) $data['year'],
            tags: $data['tags'] ?? [],
            featured: (bool) ($data['featured'] ?? false),
            links: $data['links'] ?? [],
            snippet: $data['snippet'] ?? '',
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'year' => $this->year,
            'tags' => $this->tags,
            'featured' => $this->featured,
            'links' => $this->links,
            'snippet' => $this->snippet,
        ];
    }
}
