<?php

namespace App\Content;

readonly class Certification
{
    public function __construct(
        public string $title,
        public string $issuer,
        public ?string $instructor,
        public string $date,
        public ?string $url,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            issuer: $data['issuer'] ?? 'Udemy',
            instructor: $data['instructor'] ?? null,
            date: $data['date'],
            url: $data['url'] ?? null,
        );
    }
}
