# Miki Kesel — Portfolio

A clean, single-page personal portfolio: achievements, current goals, and project
snippets. See [DESIGN.md](DESIGN.md) for the full system design and trade-offs.

**Stack:** Laravel 13 · Livewire 4 · Vue 3 · Tailwind CSS v4 · Vite · SQLite

## How it's built

- **Server-rendered Blade** for the page shell and the mostly-static sections.
- **Vue island** (`resources/js/components/ProjectsExplorer.vue`) — client-side
  filter/sort/search over the projects, hydrated from a JSON blob in the HTML.
- **Livewire island** (`resources/views/components/⚡contact-form.blade.php`) —
  the contact form: server-side validation, persistence, honeypot, rate limiting,
  queued email.
- **Alpine** (ships with Livewire) — the persisted light/dark theme toggle.
- **Flat-file content** under `content/`, parsed and cached by
  `app/Content/ContentRepository.php`.

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Terminal 1: Vite dev server (Vue + Tailwind HMR)
npm run dev
# Terminal 2: Laravel
php artisan serve
```

Then open http://127.0.0.1:8000.

> `.env`, `vendor/`, `node_modules/`, and `database/*.sqlite` are git-ignored —
> never commit them (this repo is public).

## Editing content

All site content is version-controlled flat files — **edit, commit, deploy**:

| What | Where |
|---|---|
| Name, tagline, socials, CV path | `content/profile.php` |
| Current goals | `content/goals.php` |
| Achievements | `content/achievements.php` |
| Project snippets | `content/projects/*.md` (one Markdown file each, with front-matter) |

Content is cached, so after editing run:

```bash
php artisan content:flush
```

(Run this as part of your deploy step.)

**Add a CV:** drop the PDF at `public/cv/miki-kesel-cv.pdf` (the path in
`content/profile.php`); the download button appears automatically once the file exists.

## Contact form

Submissions are stored in the `contact_submissions` table and a notification is
emailed to `MAIL_FROM_ADDRESS`. The queue runs synchronously
(`QUEUE_CONNECTION=sync`), so the email sends inline during the request — no queue
worker needed. In local dev the default mail driver is `log`, so messages land in
`storage/logs/laravel.log`; configure mail in `.env` to actually send.

## Deployment

Assets are built locally and uploaded (the server runs no Node):

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build && npm run og:build
```

Full step-by-step for shared hosting (Eco Web Hosting) — server setup, the
production `.env`, document root, and first-run commands — is in
[DEPLOYMENT.md](DEPLOYMENT.md).
