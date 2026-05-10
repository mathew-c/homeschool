# Northstar Homeschool

A TALL stack homeschool assistant for planning and running a college-bound learning day for two learners.

The current v1 is deliberately practical:

- Parent auth.
- Seeded profiles for Tor and Maty.
- Courses, credit goals, weekly hours, and college-bound credit map.
- Paper mind-map capture: one line becomes one backlog assignment card.
- Daily Kanban board: Backlog, Today, Doing, Done.
- Quick card movement plus drag-and-drop sorting with animated Livewire `wire:sort`.
- Assignment detail pages for description, due date, status, score, rubric, work samples, evidence, and reflection.
- Course hubs with editable week-by-week outlines, grading fields, assignments, external references, reading logs, and class logs.
- Seeded 9th grade World History hub based on the provided curriculum PDF: OpenStax, Crash Course, paper mind maps, and weekly summary sheets.
- Responsive top-navigation layout with Laravel Debugbar disabled by default.
- PostgreSQL via Laravel Sail.

The earlier React prototype is preserved in `prototype/react-vite`.

## Stack

- Laravel 13.5
- Livewire 4.2
- TallStackUI 3.1
- Tailwind CSS 4 via Vite
- Alpine through the Livewire/TallStackUI frontend runtime
- PostgreSQL 18 through Laravel Sail for local development

Filament is not installed. That is intentional: this is a workflow app for a parent and two students, not an admin CRUD panel. Filament may still be useful later for a private back-office area, but making it the primary surface right now would be the wrong tradeoff.

## Local Development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
npm run dev
```

Open:

```text
http://127.0.0.1:8088/dashboard
```

The root URL redirects into the dashboard flow.

Seed login:

```text
parent@example.com
password
```

## Verification

```bash
./vendor/bin/sail artisan test
npm run build
php artisan route:list --except-vendor
```

## Deployment Notes

For Sunday droplet deployment, use production env values rather than Sail defaults:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- strong `APP_KEY`
- managed or containerized PostgreSQL credentials
- `php artisan migrate --force`
- `npm ci && npm run build`
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`

No, Sail should not be treated as the production runtime. It is good for the next two days of local work; the droplet should run a production PHP/Nginx container or a standard Nginx + PHP-FPM setup.
