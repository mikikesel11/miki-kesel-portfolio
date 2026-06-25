<?php

namespace App\Content;

readonly class Goal
{
    public function __construct(
        public string $title,
        public string $status,
        public int $progress,
        public ?string $target,
        public string $blurb,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            status: $data['status'] ?? 'planned',
            progress: (int) ($data['progress'] ?? 0),
            target: $data['target'] ?? null,
            blurb: $data['blurb'] ?? '',
        );
    }
}
