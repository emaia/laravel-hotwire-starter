# Hotwire Starter

## Development

- In a source checkout, run `python3 tools/starter/build.py bootstrap` before setup if `artisan` is missing. Distributed apps already include the Laravel skeleton.
- Use Docker Compose for PHP 8.4, Composer, Artisan, Bun, and PostgreSQL. Run commands as `docker compose exec -T app …`.
- Follow the setup in [README.md](README.md). Use `composer check` for linting, static analysis, PHP and JavaScript tests, and formatting checks. Run the affected tests during development.
- If the frontend changes do not appear, check whether Vite is running. Use `bun run dev` for hot reload or `bun run build` for compiled assets.
- Follow nearby conventions and use existing components. Confirm installed dependencies before using a version-specific API. Do not add dependencies or change infrastructure without approval.
- If `.ai/rules/index.md` exists, read the rules that apply to the paths you change.

## Laravel and Hotwire

- Generate local agent skills with `docker compose exec -T app composer boost:sync` if they are missing. Load the relevant skills for the task rather than reading them all.
- Use Laravel Boost's documentation search before relying on framework or package APIs. Use its read-only database tools for schema and data inspection.
- Use the existing `hw:` components and controllers. Check `php artisan hotwire:docs <topic>` for supported props and behavior before writing unfamiliar Hotwire markup.
- Run `php artisan hotwire:check` after changing how the starter uses the package.
- Package controllers load from `vendor`; publish a controller only when the app needs a local override. Extend the documented component API rather than replacing its internal controller attributes.
- Add tests for meaningful behavior changes. Use the existing Pest and Bun suites; see `playwright.config.js` for browser tests.
