# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OneLeague is a monorepo with two sub-projects:
- `api/` — Laravel 13 REST API (PHP 8.3, PostgreSQL)
- `app/` — Vue 3 frontend (Vite)

Everything runs through Docker Compose. There is **no authentication** — Sanctum has been removed and all API routes are open.

## Docker — Start / Stop

```bash
# Start all services (postgres, api, nginx, app)
docker compose up -d

# Rebuild after Dockerfile changes
docker compose up -d --build

# Stop
docker compose down

# Stop and delete volumes (wipes the database)
docker compose down -v
```

**Service ports:**
| Service  | Host port | Purpose                   |
|----------|-----------|---------------------------|
| nginx    | 8080      | Laravel API (via PHP-FPM) |
| app      | 5173      | Vue dev server            |
| postgres | 5432      | PostgreSQL                |

## API (Laravel — `api/`)

```bash
# Exec into the running container
docker compose exec api bash

# Artisan shortcuts
php artisan migrate
php artisan migrate:fresh --seed
php artisan make:model Foo -mcr   # model + migration + controller + resource
php artisan route:list

# Tests (PHPUnit)
php artisan test
php artisan test --filter=ExampleTest
```

**Key facts:**
- All REST endpoints are defined in `routes/api.php` (currently empty — add new routes here).
- Database is PostgreSQL. When running in Docker, DB credentials come from `docker-compose.yml` — no `api/.env` required. When running Laravel outside Docker (`php artisan serve`), copy `api/.env.example` and set `DB_HOST=127.0.0.1`.
- No auth middleware in the stack.

## App (Vue 3 — `app/`)

```bash
# Exec into the running container
docker compose exec app sh

# Or run from host (requires Node)
cd app && npm install && npm run dev
```

**Key facts:**
- Entry point: `app/src/main.js` → `App.vue`.
- `app/vite.config.js` has no proxy configured yet. To forward `/api` calls to the Laravel backend during local dev, add a Vite proxy pointing to `http://localhost:8080`.

## Architecture

**Request flow:** Browser → nginx:8080 → PHP-FPM (api:9000) → Laravel → PostgreSQL

The Vue SPA and Laravel API are decoupled. In production the Vue app hits the nginx port (8080) for API calls. In development, a Vite proxy (not yet configured) would handle this.

**Docker volumes:**
- `./api` is bind-mounted into both the `api` and `nginx` containers so code changes are reflected immediately without rebuilding.
- `./app` is bind-mounted into the `app` container; `node_modules` is kept in an anonymous volume to avoid host/container conflicts.
