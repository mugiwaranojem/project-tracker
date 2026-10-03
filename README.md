# Project Tracker

Laravel 13 API (`/`) + Nuxt 4 SPA (`/frontend`), with Sanctum cookie auth.

## Tech stack

| Layer | Technology |
|---|---|
| Backend | [Laravel 13](https://laravel.com) (PHP 8.3), Laravel Sanctum (cookie/SPA auth) |
| Frontend | [Nuxt 4](https://nuxt.com) + [Vue 3](https://vuejs.org), TypeScript, SPA mode (`ssr: false`) |
| UI | [Nuxt UI 4](https://ui.nuxt.com) + Tailwind CSS 4 |
| Auth bridge | `nuxt-auth-sanctum` |
| Database | MySQL 8 |
| Local dev | Docker Compose (PHP-FPM, Nginx, MySQL) |
| Testing | PHPUnit 12 (SQLite in-memory), Laravel Pint |
| CI/CD | GitHub Actions -> Hostinger (rsync over SSH) |

## Local setup

You need [Docker](https://www.docker.com/) and [Node.js 22+](https://nodejs.org/).

### 1. API (http://localhost:8000)

```sh
cp .env.example .env
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate --seed
```

`--seed` creates a test user (`test@example.com` / `password`) and sample projects.

### 2. Frontend (http://localhost:3000)

```sh
cd frontend
npm install
npm run dev
```

Open http://localhost:3000 and log in with the test user.

## Everyday commands

```sh
docker compose up -d                          # start the API
docker compose down                           # stop it
docker compose exec web php artisan test      # run API tests
docker compose exec web php artisan migrate:fresh --seed   # reset the database
```

## Config notes

- The frontend calls `http://localhost:8000` by default. Override with `NUXT_PUBLIC_API_BASE_URL`
  (must include `http://` or `https://`).
- `.env.example` already sets `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` for `localhost:3000`;
  keep the frontend on that port or update both.

## AI Tools Used

- **Claude** was used throughout: scaffolding the Laravel API and Nuxt frontend, writing tests  development assistance
- All AI-generated code was reviewed, run and tested by me before being committed.

## Assumptions Made

Where the requirements didn't specify something, I decided:

- **Authentication:** only pre-created users can log in (no public registration). Every `/api/projects` route
  requires a logged-in user; I used Sanctum cookie (SPA) auth rather than tokens, so the frontend and API
  share a parent domain (`portal.` / `api.`).
- **Shared data:** all logged-in users see and manage the same projects. There are no per-user projects or roles.
- **Project fields:** client name, project name, optional description, status, priority, start date and due date.
  Client name, project name, status, priority and both dates are required; due date can't be before start date.
- **Status values:** `planning`, `in_progress`, `on_hold`, `completed` (new projects default to `planning`).
- **Priority values:** `low`, `medium`, `high` (default `medium`).
- **List behaviour:** paginated (15 per page, max 100), searchable by client, project name or description,
  filterable by status and priority, and sortable by any main column. Status and priority sort by logical order,
  not alphabetically.
- **Deleting:** a hard delete (no soft delete or archive), with a confirmation dialog in the UI.
- **Dates:** plain calendar dates with no time or timezone.
- **Hosting:** Hostinger shared hosting, so deploys are manual, the queue runs synchronously, and the frontend
  is a static SPA (`ssr: false`).
- **Seed data:** sample projects and a test user exist for local development only; production gets its own admin user.

## Deployment

GitHub Actions workflows: [.github/workflows](.github/workflows)

**Live sample:** https://portal.lostandfoundph.shop/ (API: https://api.lostandfoundph.shop)

| Email | Password |
|---|---|
| `admin@lostandfoundph.shop` | `P@ssword.123` |
