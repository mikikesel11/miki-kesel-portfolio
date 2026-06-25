# Deployment — Eco Web Hosting

A runbook for deploying this portfolio to **Eco Web Hosting** (shared hosting on
CloudLinux, Essential plan or higher). The app is built around two choices that
make shared hosting a clean fit:

- **Assets are built locally** — the server needs no Node.
- **`QUEUE_CONNECTION=sync`** — the contact email sends inline, so no queue
  worker daemon is required.

Most steps generalise to any cPanel/CloudLinux shared host.

---

## 0. Prerequisites

- An Eco Web Hosting plan with **SSH enabled** (standard on all plans).
- **Confirm PHP 8.3+** is available in the CloudLinux **PHP Selector** (the app
  requires `^8.3`). Ask pre-sales if unsure — this is the only hard blocker.
- A **domain** pointed at the hosting (via Eco's nameservers or an A record).
- A **transactional email** account for the contact form (Resend, Brevo,
  Mailgun, Postmark…). Free tiers are plenty. You'll need SMTP credentials.
- Local tooling: PHP 8.3+, Composer, Node 22+ (only for building).

---

## 1. Build locally

From the project root:

```bash
composer install --no-dev --optimize-autoloader   # production-optimised vendor/
npm ci
npm run build        # compiles Vue + Tailwind into public/build
npm run og:build     # regenerates public/og-image.png
```

This produces everything the server needs: `vendor/`, `public/build/`, and
`public/og-image.png`. You will upload these — the server never runs Node.

> Re-run `npm run build` whenever you change CSS/JS/Vue, and `npm run og:build`
> whenever you change the share-card source or your tagline.

---

## 2. Set the PHP version

In the Eco control panel → **PHP Selector** (CloudLinux):

1. Select **PHP 8.3** (or 8.4).
2. Ensure these extensions are enabled: `pdo`, `pdo_mysql` (or `pdo_sqlite`),
   `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`,
   `fileinfo`, `curl`.

---

## 3. Lay out the files (document root → `public/`)

Laravel must serve from `public/`, never the project root (that would expose
`.env`). Two supported patterns on Eco:

**Preferred — point the domain's document root at `public/`:**
Upload the project to a folder *outside* the web root, e.g. `~/portfolio`, then in
the control panel set the domain's **Document Root** to `~/portfolio/public`.

**Fallback (if document root is locked to `public_html`):**
Put the app in `~/portfolio`, move the contents of `public/` into `public_html/`,
and edit `public_html/index.php` so the two `require` paths point at
`~/portfolio/vendor/autoload.php` and `~/portfolio/bootstrap/app.php`.

Upload via **SFTP** or, with SSH, `git clone` the repo and upload the
locally-built `vendor/`, `public/build/`, and `public/og-image.png` separately
(they're git-ignored). Or run `composer install --no-dev` over SSH instead of
uploading `vendor/`.

---

## 4. Create the production `.env`

`.env` is never committed. Create it on the server (copy `.env.example` and edit):

```dotenv
APP_NAME="Miki Kesel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.co.uk      # MUST be the real https URL — drives og:image/og:url

APP_KEY=                                # generate in step 5

QUEUE_CONNECTION=sync

# --- Database: pick ONE ---
# MySQL (recommended on shared hosting — create the DB + user in the panel first)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass
# SQLite alternative: DB_CONNECTION=sqlite and a writable file OUTSIDE public/

CACHE_STORE=database
SESSION_DRIVER=database

# --- Mail (contact form) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@your-domain.co.uk"   # contact messages are sent here
MAIL_FROM_NAME="${APP_NAME}"
```

> If you use **SQLite** instead of MySQL, create the file outside the web root
> (e.g. `~/portfolio/database/database.sqlite`), set `DB_DATABASE` to its
> absolute path, and ensure it's writable. MySQL is the safer default here.

---

## 5. First-run commands (over SSH)

From the project root on the server:

```bash
php artisan key:generate          # writes APP_KEY into .env (run once)
php artisan migrate --force       # creates sessions, cache, jobs, contact_submissions
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan content:flush         # clear cached flat-file content
```

Make sure `storage/` and `bootstrap/cache/` are writable by the web user
(typically `755`/`775`; CloudLinux usually handles ownership automatically).

---

## 6. Enable HTTPS

In the control panel, enable the free **Let's Encrypt** SSL for the domain. With
`APP_URL` set to `https://…`, Laravel generates correct absolute URLs (important
for the OG share card). Force HTTPS at the panel/redirect level if available.

---

## 7. Cron (optional today)

The app has **no scheduled tasks** right now and uses the **sync** queue, so a
cron job is **not required**. If you later add scheduled work, add one entry in
the control panel → **Cron Jobs**:

```
* * * * * /usr/local/bin/php /home/USER/portfolio/artisan schedule:run >> /dev/null 2>&1
```

(Use the PHP 8.3 binary path the panel shows for your selected version.)

---

## 8. Post-deploy verification

- [ ] `https://your-domain.co.uk` loads (200), styles + fonts render.
- [ ] Light/dark toggle works and persists.
- [ ] Projects filter/search/sort works (Vue island loaded — check `public/build` uploaded).
- [ ] "Download CV" downloads `MikiKeselResume.pdf`.
- [ ] Contact form: submit a test message → row appears in the DB **and** an email arrives at `MAIL_FROM_ADDRESS`.
- [ ] View page source: `og:image` / `og:url` are absolute `https://your-domain…` URLs. Validate with the LinkedIn Post Inspector or opengraph.xyz.
- [ ] `https://your-domain.co.uk/og-image.png` returns the share card.

---

## 9. Updating the site later

**Content only** (goals, projects, certifications, profile):

```bash
# edit content/*.php or content/projects/*.md (locally or on the server)
php artisan content:flush      # on the server, so the change shows
```

**Code or assets** (CSS/JS/Vue, the share card):

```bash
# locally:
npm run build        # and/or npm run og:build
# upload changed files, then on the server:
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan content:flush
```

Re-run `migrate --force` only when you add migrations.
