# Hotwire Starter

A Laravel 13 starter with [Hotwire](https://hotwire.emaia.dev) (Turbo and Stimulus), Blade, Tailwind CSS, and Fortify authentication with email verification and two-factor authentication. The default UI language is Brazilian Portuguese.

## Quick start

Requires Docker with Compose v2. No host PHP or Composer is needed. From the directory where you want to create `my-app`, run:

```sh
docker run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
  -v "$PWD:/app" -w /app composer:2 \
  create-project emaia/laravel-hotwire-starter my-app --no-install --no-scripts
```

If you already have PHP and Composer on your host, you can run `composer create-project emaia/laravel-hotwire-starter my-app --no-install --no-scripts` instead.

Both commands only download the starter. From `my-app`, create `.env` and start the app:

```sh
[ -f .env ] || cp .env.example .env
docker compose run --rm --build app composer setup
docker compose up -d --wait
```

Setup generates the app key, runs PostgreSQL migrations, and builds the assets with Bun. On later starts, run only `docker compose up -d --wait`.

- **App:** [https://hotwire-starter.localhost:8443](https://hotwire-starter.localhost:8443/)
- **Email:** [Mailpit](http://localhost:18025/)
- **PostgreSQL:** `127.0.0.1:15432` from the host, or `db:5432` from the containers.

If your browser does not trust local HTTPS, Caddy's root certificate is at `/data/caddy/pki/authorities/local/root.crt` in the `proxy` container.

## Configure your app

Set `APP_NAME` and `APP_LOCALE` in `.env` to change the app name and language. For a custom local address, set `APP_HOST` to the hostname and `APP_URL` to the full URL. Match `HTTPS_PORT` to the port in `APP_URL`. No changes to Vite or Caddy are needed.

```dotenv
APP_HOST=my-app.localhost
APP_URL=https://my-app.localhost:9443
HTTPS_PORT=9443
```

When running multiple apps, also use different `HTTP_PORT`, `FORWARD_DB_PORT`, and `FORWARD_MAIL_PORT` values. Run `docker compose up -d --wait` after editing `.env`, and restart Vite if it is running. The example database password and Mailpit are for local development only.

## Develop

| Command                                          | Purpose                                   |
| ------------------------------------------------ | ----------------------------------------- |
| `docker compose exec app bun run dev`            | Start Vite with hot reload.               |
| `docker compose exec app bun run build`          | Build assets without the Vite dev server. |
| `docker compose exec app composer format`        | Format PHP, Blade, and frontend files.    |
| `docker compose exec app composer check`         | Run linting, static analysis, and tests.  |
| `docker compose exec -T app composer boost:sync` | Sync Laravel Boost agent skills.          |

If Vite has stopped but Laravel still tries to use it, remove the generated `public/hot` file. The included MCP configuration runs Laravel Boost in the `app` container; start Compose before connecting.

## Optimize when you need to

New apps include all Hotwire frontend dependencies so you can use every component out of the box. Once you know which components you need, use the [Hotwire installer's dependency options](https://hotwire.emaia.dev/installation) to narrow controller loading, then remove unused packages, rebuild the assets, and run `php artisan hotwire:check` in the app container.

## Maintain your app

The starter copies code into your app. Composer updates installed packages, not the copied routes, layouts, migrations, or Docker files. To adopt a starter change, compare [distribution releases](https://github.com/emaia/laravel-hotwire-starter/releases) and apply what fits your app.

Review dependency and Docker image updates in a branch and run `composer check` before merging. Keep your `.env` and app key; do not rerun `composer setup` on an existing app.

Already have a Laravel app? [Install Laravel Hotwire](https://hotwire.emaia.dev/installation) rather than overlaying the starter.

### Work on the starter source

In a clone of [`laravel-hotwire-starter-source`](https://github.com/emaia/laravel-hotwire-starter-source), use Git and Python 3.10+:

| Goal                                                 | Command                                          |
| ---------------------------------------------------- | ------------------------------------------------ |
| Restore the pinned Laravel skeleton in this checkout | `python3 tools/starter/build.py bootstrap`       |
| Assemble an independent app in a new directory       | `python3 tools/starter/build.py build ../my-app` |

The Laravel skeleton pin is in `tools/starter/upstream.json`. Update Hotwire-managed frontend dependencies alongside the Hotwire package, not as isolated version bumps.
