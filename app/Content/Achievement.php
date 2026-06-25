<?php

namespace App\Content;

readonly class Achievement
{
    public function __construct(
        public string $date,
        public string $title,
        public ?string $metric,
        public string $blurb,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'],
            title: $data['title'],
            metric: $data['metric'] ?? null,
            blurb: $data['blurb'] ?? '',
        );
    }
}
