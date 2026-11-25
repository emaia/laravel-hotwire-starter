# Hotwire Starter

A starter kit for Laravel 13 with PHP 8.4, Blade, Tailwind CSS, and [Hotwire](https://hotwire.emaia.dev) (Turbo and Stimulus). It includes registration, email verification, password reset, two-factor authentication, and a dashboard with account settings. The default interface language is Brazilian Portuguese.

## Choose your workflow

From the root of a Git clone of the starter, use one of these commands (requires Python 3.10+):

| Goal                       | Command                                          | Result                                                       |
| -------------------------- | ------------------------------------------------ | ------------------------------------------------------------ |
| Create a new app           | `python3 tools/starter/build.py build ../my-app` | Creates a complete, independent app in `../my-app`.          |
| Work on the starter itself | `python3 tools/starter/build.py bootstrap`       | Restores the upstream Laravel files in the current checkout. |

## Create an app from the starter kit

For a local build, choose an empty or nonexistent destination. After `build` finishes, switch to the new directory (`cd ../my-app`) and follow [first-time setup](#first-time-setup). You do not need to run `bootstrap`.

If a GitHub CI archive is available, you can extract it and run first-time setup from its `app/` directory instead. Neither the archive nor the generated app requires Python to run.

Once the package is published, use the [Laravel installer](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits) with PHP 8.4 or later and Composer on your host machine:

```sh
composer global require laravel/installer
laravel new my-app --using=emaia/laravel-hotwire-starter --no-node --no-boost --no-interaction
cd my-app
```

Before running setup, check the new app's `.env` and `.env.example`: the installer may set `DB_CONNECTION=sqlite` and comment out `DB_PASSWORD`. To use PostgreSQL in Docker, set `DB_CONNECTION=pgsql` and make sure `DB_PASSWORD` is uncommented and set in both files. Bun installs the frontend dependencies in the container, and Laravel Boost is already included.

## First-time setup

Requires Docker with Compose v2. From the project root, run:

```sh
[ -f .env ] || cp .env.example .env
docker compose run --rm --build app composer setup
docker compose up -d --wait
```

`composer setup` installs dependencies, generates the application key, runs migrations, and builds the assets. If the app is already set up, keep your `.env` and run `docker compose up -d --wait` instead.

- **App:** [hotwire-starter.localhost:8443](https://hotwire-starter.localhost:8443/)
- **Local email:** [Mailpit](http://localhost:18025/)
- **PostgreSQL:** `127.0.0.1:15432` from the host; `db:5432` from the containers.

If your browser does not trust local HTTPS, Caddy's root certificate is at `/data/caddy/pki/authorities/local/root.crt` in the `proxy` container.

## Change the local URL

Edit `.env` in the project root. Set `APP_HOST` to the hostname only (for Caddy) and `APP_URL` to the full URL (for Laravel and Vite):

```dotenv
APP_HOST=my-app.localhost
APP_URL=https://my-app.localhost:8443
HTTPS_PORT=8443
```

If you change the HTTPS port, update both `APP_URL` and `HTTPS_PORT`. Update `.env.example` too if you want to share the new defaults. No changes to the Caddyfile or `vite.config.js` are needed: the Vite dev server reads `APP_URL` for its public origin and hot reload. Building the assets does not require `APP_URL`.

If the stack is already running, run `docker compose up -d --wait` to apply the changes and restart Vite if it is running. To run multiple projects at once, give each project distinct `HTTP_PORT`, `HTTPS_PORT`, `FORWARD_DB_PORT`, and `FORWARD_MAIL_PORT` values.

## Development

- `docker compose exec app bun run dev` — start Vite with hot reload.
- `docker compose exec app bun run build` — build assets to serve without the Vite dev server.
- `docker compose exec app php artisan migrate` — apply new migrations.
- `docker compose exec app composer format` — format PHP and Blade with Pint, and frontend files with Prettier.
- `docker compose exec app composer check` — run linting, static analysis, PHP and JavaScript tests, and formatting checks.

If Laravel still tries to load assets from Vite after the dev server has stopped, remove the generated `public/hot` file to switch back to the built assets.

## Laravel Boost

To install or refresh the local agent skills, including Laravel Hotwire, run this after first-time setup:

```sh
docker compose exec -T app composer boost:sync
```

The generated skill copies are not tracked in Git. `boost.json` controls the package selection; `AGENTS.md` holds the starter's own instructions. With the default configuration, syncing leaves the project instructions and MCP configuration unchanged. If you add a custom skill, keep its source in `.ai/skills/`.

Both `.mcp.json` and `opencode.json` run Laravel Boost in the `app` container. Start the Compose stack before connecting your MCP client. To configure Codex (`.codex/config.toml`) or Zed (`.zed/settings.json`) locally, use:

```sh
docker compose exec -T app php artisan boost:mcp
```

You do not need to run `boost:install --mcp` for clients that are already configured. If you rerun it, check the client configuration: the Boost installer can overwrite the command and switch it back to PHP on the host.

## Maintaining the starter source

After `bootstrap`, Docker, Vite, and your editor work from the project root. Git ignores the restored Laravel files locally.

- **Laravel skeleton:** Review [upstream tags](https://github.com/laravel/laravel/tags) and update `tools/starter/upstream.json` with the tag, full commit, and SHA-256 of `https://codeload.github.com/laravel/laravel/tar.gz/<commit>`. Keep `omit` for upstream files that should not be shipped. Rerun `bootstrap`; it updates unchanged generated files but stops before overwriting local edits. To customize a generated file, edit it and use `git add -f <path>` to make it part of the overlay. `composer update` does not update these skeleton files.
- **Laravel Hotwire:** Update the Composer package and lockfile, then run `php artisan hotwire:check` in the app container. If it reports loader drift, review the output of `php artisan hotwire:check --fix`, reconcile any changed frontend dependencies, and rebuild with Bun. The standard `composer check` includes the Hotwire check.
- **Container updates:** Dependabot checks Docker images each weekday. The Server Side Up image is pinned by digest on the PHP 8.4 / FrankenPHP / Alpine 3.24 channel so it can propose rebuilds on the same tag. The source repository also checks that digest daily, independently of Dependabot. CI builds and tests the proposed image; updates are not merged or deployed automatically.

For Packagist, publish the assembled app from a distribution repository. Do not register the overlay-only source repository as the installable package.
