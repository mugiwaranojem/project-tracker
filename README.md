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

## Deployment

GitHub Actions workflows: [.github/workflows](.github/workflows)

**Live sample:** https://portal.lostandfoundph.shop/ (API: https://api.lostandfoundph.shop)

| Email | Password |
|---|---|
| `admin@lostandfoundph.shop` | `P@ssword.123` |
