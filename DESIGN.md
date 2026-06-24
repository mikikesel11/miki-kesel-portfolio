# Portfolio Website — System Design

**Owner:** Miki Kesel · **Date:** 2026-06-24 · **Status:** Draft for build

A clean, single-page personal portfolio that highlights achievements, current
goals, and snippets of older projects. Built on Laravel 12 / Livewire 3 / Vue 3 /
Tailwind v4. Content is managed as version-controlled flat files.

---

## 1. Requirements

### Functional
- One-page site with anchored sections: Hero → Current Goals → Achievements → Projects → Contact.
- **Achievements**: short, dated highlights (optionally with a metric).
- **Current goals**: what you're working toward now, with status/progress.
- **Project snippets**: title, blurb, tech tags, year, links — many of them.
- **Contact form**: visitor sends a message (validated, stored, emailed to you).
- **Filter/sort projects**: by tag/tech/year, instant, no page reload.
- **Download résumé/CV**: a PDF download button.
- **Light/dark theme toggle**: persisted across visits.

### Non-functional
- Low traffic (personal site): correctness and polish matter far more than scale.
- Fast first paint, good Lighthouse/SEO (server-rendered HTML).
- Cheap to host and operate (single small VPS or shared host, PHP 8.5).
- Content editable without a CMS — edit a file, commit, deploy.
- Accessible (keyboard, contrast, reduced-motion) and responsive.

### Constraints (chosen)
- Stack fixed: PHP/Laravel/Livewire/Tailwind/Vue.
- **Content = flat files** (Markdown + config), version-controlled.
- **Livewire and Vue both used deliberately** as a skills showcase.

---

## 2. High-Level Design

The page is server-rendered Blade. Two interactive "islands" demonstrate the two
reactivity models on purpose:

- **Livewire = server-state interactivity** → the Contact form (validation,
  persistence, mail all happen server-side over Livewire's XHR round-trips).
- **Vue = client-state interactivity** → the Projects explorer (filter/sort runs
  entirely in the browser against data hydrated as props — zero server calls).
- **Alpine** (ships free with Livewire) → the theme toggle, a few lines.

```
                    ┌─────────────────────────────────────────────┐
   Browser  ──GET──▶│  Laravel 12  (single route: GET / )          │
                    │                                              │
                    │  PageController                              │
                    │     └─ ContentRepository (cached)            │
                    │           ├─ reads content/*.md  (projects)  │
                    │           ├─ reads content/*.php (goals,      │
                    │           │   achievements, profile)         │
                    │           └─ returns typed DTOs              │
                    │                                              │
                    │  Blade view  (home.blade.php)                │
                    │   ├─ <x-hero/> <x-goals/> <x-achievements/>  │  static, SSR
                    │   ├─ @livewire('contact-form')               │──┐ server island
                    │   └─ <div id="projects" data-projects=…>     │  │ Vue island
                    └──────────────┬──────────────┬────────────────┘  │
                                   │              │                   │
                  Vite bundle ◀────┘              │            ┌──────▼──────┐
              (Vue ProjectsExplorer,              │            │  Livewire    │
               Alpine theme, Tailwind)            │            │  XHR /livewire/update
                                                  │            │  → validate  │
                                                  │            │  → store DB  │
                                                  ▼            │  → queue mail│
                                          SQLite (submissions) └──────────────┘
```

### Storage choices
- **Site content** → flat files in `content/`, parsed once and cached. No DB needed to render the page.
- **Contact submissions** → **SQLite** file DB. Zero-config, durable record, backs rate-limiting. Keeps the "no real DB to operate" promise while still persisting messages.
- **Email** → queued `Mailable` notification to you on each submission.
- **CV PDF** → static file served from `public/` (or `storage/` via a signed route if you want download counts later).

---

## 3. Deep Dive

### 3.1 Content model (flat files)

```
content/
├── profile.php           # name, tagline, socials, cv_path, meta
├── goals.php             # array of current goals
├── achievements.php      # array of achievements
└── projects/
    ├── acme-checkout.md  # frontmatter + markdown body (the snippet)
    ├── data-pipeline.md
    └── …
```

**`content/goals.php`**
```php
return [
    ['title' => 'Ship v2 of X', 'status' => 'in_progress', 'progress' => 60, 'target' => '2026-09', 'blurb' => '…'],
];
```

**`content/achievements.php`**
```php
return [
    ['date' => '2025-11', 'title' => 'Led migration to …', 'metric' => 'cut p95 by 40%', 'blurb' => '…'],
];
```

**`content/projects/acme-checkout.md`**
```markdown
---
title: Acme Checkout
year: 2023
tags: [Laravel, Payments, Vue]
featured: true
links:
  repo: https://github.com/…
  live: https://…
---
One-paragraph snippet about what it was and what you did.
```

A **`ContentRepository`** service loads these (CommonMark + YAML front-matter for
projects, `require` for the PHP arrays), maps them to small DTOs, and caches the
result with `Cache::rememberForever`. Cache is cleared on deploy via
`php artisan content:flush` (a tiny custom command) so a `git push` is all it
takes to update the site.

> Why PHP arrays for goals/achievements and Markdown for projects: goals and
> achievements are short and structured (great as arrays); project snippets have
> prose bodies and links that read better as Markdown files.

### 3.2 The single route

```php
Route::get('/', PageController::class)->name('home');     // renders everything
// Livewire registers its own /livewire/update endpoint automatically.
```

No REST/GraphQL API: the page is server-rendered and the Projects data is
embedded in the initial HTML as a `data-projects` JSON blob for Vue to hydrate —
one request, no client fetch.

### 3.3 Contact form (Livewire)
- Real-time field validation (`name`, `email`, `message`) via Livewire rules.
- **Spam defenses**: honeypot field + `RateLimiter` (e.g. 3 submissions / IP / 10 min). Livewire handles CSRF automatically.
- On submit: persist to `contact_submissions`, dispatch a **queued** `Mailable` to you, show an inline success state (no full-page reload).
- Graceful failure: if mail/queue is down the row is still stored; success message only depends on the DB write.

`contact_submissions`: `id, name, email, message, ip, user_agent, created_at`.

### 3.4 Projects explorer (Vue 3)
- Mounted on `#projects`, receives all projects as a prop (hydrated from the JSON blob).
- Reactive filter by tag/tech, sort by year, optional text search — all client-side, instant.
- Renders the same card markup the SSR fallback uses, so it's progressive: cards exist in the HTML for SEO/no-JS, Vue enhances them.
- Built with `@vitejs/plugin-vue`, mounted in `resources/js/app.js`.

### 3.5 Theme toggle (Alpine + Tailwind)
- Tailwind v4 `dark:` class strategy.
- Alpine snippet reads `localStorage.theme` (falls back to `prefers-color-scheme`), toggles the `dark` class on `<html>`, persists choice. Inline `<head>` script applies it pre-paint to avoid a flash.

### 3.6 Styling
- Tailwind v4 via the Vite plugin; design tokens (colors, spacing, font) as CSS variables so light/dark and brand tweaks are one place.
- Reusable Blade components for section shells; respect `prefers-reduced-motion`.

---

## 4. Scale & Reliability

It's a personal one-pager — the realistic ceiling is hundreds of views/day, so
the work here is "don't be slow or fragile," not "scale out."

- **Caching**: parsed content cached indefinitely (flushed on deploy). Optionally add `spatie/laravel-responsecache` to cache the full HTML; Livewire/Vue still work because their updates are separate XHR calls.
- **Assets**: Vite production build, hashed + far-future cached; serve behind Cloudflare (free) for CDN + TLS.
- **Mail resilience**: queued; a failed send doesn't lose the message (it's in SQLite). Run the queue via `php artisan queue:work` under supervisor, or `queue:listen`/database driver for simplicity.
- **Backups**: SQLite file + `content/` are both in git or a nightly copy; nothing else is stateful.
- **Monitoring**: uptime ping (e.g. a free checker) + Laravel logs; optional error tracking (Sentry/Flare) — overkill but cheap insurance.

---

## 5. Trade-offs (explicit)

| Decision | Win | Cost / Risk |
|---|---|---|
| **Flat-file content** | Versioned, no CMS, simple, fast | Editing = commit + deploy; no WYSIWYG; non-devs can't edit |
| **Livewire *and* Vue** | Shows range; clean "server vs client state" demo | Two reactivity models = more tooling + mental overhead than picking one |
| **SQLite for submissions** | Zero-config, durable, trivial ops | Single-writer (irrelevant at this volume) |
| **One-pager** | Great UX/SEO, one route, fast | Doesn't scale to lots of content (blog, case studies) without restructuring |
| **Embed projects JSON in HTML** | One request, no API to build/secure | Larger initial HTML if projects grow into the hundreds |

---

## 6. What I'd revisit as it grows

- **Editing friction hurts** → move content to DB + a small Filament/Nova admin (the `ContentRepository` interface stays; only its backing changes).
- **Want a blog / case studies** → you've outgrown a one-pager; add real routes/pages and pagination; Vue explorer pattern still applies.
- **Projects balloon** → stop embedding JSON; add a `/api/projects` endpoint Vue fetches with debounced search + pagination.
- **Traffic spikes** → response cache + CDN edge caching already cover most of it.
- **More forms / heavier interactivity** → consider standardizing on one of Livewire or Vue to cut overhead.

---

## 7. Suggested build order
1. `composer create-project laravel/laravel`, install Livewire 3, Vue + Vite plugin, Tailwind v4.
2. `ContentRepository` + DTOs + `content/` files + `content:flush` command.
3. Static SSR sections (hero, goals, achievements, footer) + Blade components.
4. Vue Projects explorer (with SSR fallback cards).
5. Livewire Contact form (validation → SQLite → queued mail → honeypot/throttle).
6. Theme toggle + dark styles.
7. CV download, meta/OG tags, polish, deploy (Forge/VPS + Cloudflare + queue worker).
