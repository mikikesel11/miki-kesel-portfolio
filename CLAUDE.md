# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A single-page personal portfolio (projects, goals, certifications, contact form) built as a
server-rendered Blade page with two client-side "islands". Designed to run on cheap shared
hosting: assets are built locally (server runs no Node) and the queue runs `sync` (no worker daemon).

**Stack:** Laravel 13 · Livewire 4 · Vue 3 · Tailwind CSS v4 · Vite 8 · PHPUnit 12 · SQLite (dev) / MySQL (prod)

## Commands

```bash
composer setup              # one-shot local bootstrap (.env, key, migrate, npm install, build)
composer dev                # run everything: php serve + queue + pail logs + vite (concurrently)
php artisan serve           # app only (pair with `npm run dev` in another terminal for HMR)
npm run dev                 # Vite dev server (Vue + Tailwind HMR)

npm run build               # compile Vue + Tailwind into public/build (required before deploy)
npm run og:build            # regenerate public/og-image.png (after tagline/share-card changes)

composer test               # clears config cache, then runs the full suite
php artisan test            # run tests directly
php artisan test --filter=ContactFormTest              # single test class
php artisan test tests/Feature/ContactFormTest.php     # single test file
./vendor/bin/pint           # format PHP (Laravel Pint — the linter/formatter here)

php artisan content:flush   # clear cached flat-file content (run after editing content/)
php artisan mail:test you@example.com   # diagnose Brevo mail; prints mailer/from/masked-key + real transport error
```

## Architecture

**Single route.** `routes/web.php` maps `/` → invokable `PageController`, which pulls four
buckets (`profile`, `goals`, `certifications`, `projects`) from `ContentRepository` and renders
`home.blade.php`. There is no admin, no auth, no API.

**Content is flat files, not the database.** All site copy lives under `content/` and is
version-controlled — edit, commit, deploy:
- `content/profile.php`, `content/goals.php`, `content/certifications.php` return PHP arrays.
- `content/projects/*.md` — one Markdown file per project with YAML front-matter (`title`, `year`,
  `tags`, `featured`, `links`); the body is the snippet.

`app/Content/ContentRepository.php` reads these, sorts them, and `Cache::rememberForever`s them.
**Key invariant: the cache only ever stores arrays of primitives, never DTO objects** — the typed
DTOs (`Goal`, `Certification`, `Project`) are rehydrated via `fromArray(...)` on the way *out* of
the cache. A malformed project file is logged and skipped, not fatal. Because content is cached
forever, **any content edit needs `php artisan content:flush`** to show up. The repository is bound
as a singleton in `AppServiceProvider` with `base_path('content')`.

**Two front-end islands** (the rest of the page is static Blade):
- **Vue** (`resources/js/components/ProjectsExplorer.vue`) — client-side filter/sort/search over
  projects. Hydrated from a JSON blob in the HTML: `Project implements JsonSerializable`, and
  `home.blade.php` emits `json_encode($projects)` into a `data-projects` attribute the Vue app reads.
  Changing `Project`'s serialized shape means updating the Vue side too.
- **Livewire** (`resources/views/components/⚡contact-form.blade.php` — the `⚡` prefix is Livewire 4's
  single-file-component convention) — server-side validation, persistence to `contact_submissions`,
  honeypot field, and IP-based rate limiting. On submit it queues `ContactSubmissionReceived` mail
  (sync queue → sends inline) and calls `BrevoEventTracker`.

**Mail goes through Brevo's transactional API, not SMTP.** `AppServiceProvider::boot()` registers a
`brevo` mailer via `symfony/brevo-mailer` (`brevo+api` DSN, reusing `BREVO_API_KEY`); enable with
`MAIL_MAILER=brevo`. The **From must be a Brevo-verified sender**; the recipient is decoupled via
`CONTACT_TO_ADDRESS` (config key `mail.contact_to`, falls back to `MAIL_FROM_ADDRESS`). `BrevoEventTracker`
also hits the Brevo API for event tracking. `mail:test` exists because the contact form swallows send
errors — it sends inline and surfaces the real transport exception (essential on hosts without tinker).

**Security headers** are added globally by `app/Http/Middleware/SecurityHeaders.php` (CSP, X-Frame-Options, etc.).

## Deployment context (shared hosting)

Deploy target is **Eco Web Hosting** (CloudLinux/cPanel). Full runbook in `DEPLOYMENT.md`. Critical constraints:
- `shell_exec` / `proc_open` are **disabled** → `composer install --no-scripts` then a manual
  `php artisan package:discover`; `php artisan tinker` does **not** work there.
- Build model is **build locally, upload the result** — `public/build/` and `vendor/` are git-ignored;
  the server never runs Node.
- Deploy flow: `git pull` → `composer install --no-scripts` → `php artisan package:discover` →
  `config:cache` / `route:cache` / `view:cache` → `content:flush`.

## Conventions

- **Immutability:** DTOs are readonly; never mutate cached arrays in place — rehydrate.
- **`.env` is never committed** (this repo is **public**). Same for `vendor/`, `node_modules/`,
  `database/*.sqlite`. Locally-installed ECC skills under `.claude/skills/` are git-ignored except
  the project's own `.claude/skills/miki-kesel-portfolio/`.
- **Git workflow:** branch off `main` → PR → CI green → merge via **merge commit**; sync local main
  with `git merge --ff-only origin/main` (never `git pull`). Never commit to `main` directly.
- Tests use in-memory SQLite with `Mail::fake()` / `Mail::shouldReceive()`; place feature tests in
  `tests/Feature`, unit tests in `tests/Unit`.

## Related docs

- `README.md` — quickstart and content-editing guide.
- `DEPLOYMENT.md` — full shared-hosting runbook (PHP selector, doc root, prod `.env`, verification).
- `DESIGN.md` — system design and trade-offs.
