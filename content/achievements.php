<?php

// Achievements — short, dated highlights. `metric` is optional but punchy.
// Listed newest-first; the repository sorts by date descending anyway.

return [
    [
        'date' => '2025-11',
        'title' => 'Led a platform performance overhaul',
        'metric' => 'cut p95 latency ~40%',
        'blurb' => 'Profiled hot paths, added targeted caching, and removed N+1 queries across the app.',
    ],
    [
        'date' => '2025-04',
        'title' => 'Shipped a self-serve billing flow',
        'metric' => null,
        'blurb' => 'Designed and built an end-to-end subscription experience with Laravel and Stripe.',
    ],
    [
        'date' => '2024-08',
        'title' => 'Mentored two junior engineers',
        'metric' => null,
        'blurb' => 'Ran weekly pairing sessions and code reviews; both shipped features solo within a quarter.',
    ],
];
