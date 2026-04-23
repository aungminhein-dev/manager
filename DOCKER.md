# Docker Setup

This project can run with Docker using Laravel + MySQL + Redis + Livewire (Vite) + queue workers + scheduler + websocket server.

## Services

- `web`: Nginx (`http://localhost:8000`)
- `app`: PHP-FPM Laravel runtime
- `mysql`: MySQL 8.4 (`localhost:33060`)
- `redis`: Redis 7 (`localhost:63790`)
- `queue`: Laravel queue worker
- `scheduler`: Laravel scheduler worker
- `websocket`: Reverb/websocket server (`ws://localhost:8080`)
- `vite`: Vite dev server (`http://localhost:5173`)

## 1) Prepare environment

If you are switching from your SQLite config, back up your current `.env` first.

```bash
cp .env .env.backup
cp .env.docker.example .env
```

Generate app key (if empty):

```bash
docker compose run --rm app php artisan key:generate
```

## 2) Start the full stack

```bash
docker compose up -d --build
```

## 3) Run migrations

```bash
docker compose exec app php artisan migrate
```

## 3.1) Run tests in Docker (MySQL)

This project test config uses MySQL service settings in Docker.

```bash
docker compose exec app php artisan test --compact
```

For targeted scheduler tests:

```bash
docker compose exec app php artisan test --compact tests/Feature/Scheduler/SchedulerDashboardTest.php tests/Unit/Scheduler/PrioritizeTodoTest.php
```

## 4) Optional: install and enable Reverb

The websocket container supports `reverb:start` automatically if Reverb is installed.

```bash
docker compose exec app composer require laravel/reverb
# If your app needs broadcasting/reverb config scaffolding:
docker compose exec app php artisan install:broadcasting
```

Then restart websocket service:

```bash
docker compose restart websocket
```

## 5) Useful commands

```bash
# App logs
docker compose logs -f app

# Queue logs
docker compose logs -f queue

# Websocket logs
docker compose logs -f websocket

# Run tests
docker compose exec app php artisan test --compact

# Stop everything
docker compose down
```

## Notes

- App source is bind-mounted (`./:/var/www/html`) for live editing.
- `queue`, `scheduler`, and `websocket` run in dedicated containers.
- If port conflicts happen locally, adjust port mappings in `docker-compose.yml`.
