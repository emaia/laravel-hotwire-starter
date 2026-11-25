FROM serversideup/php:8.6-frankenphp-alpine3.24@sha256:9eb7178d5c68307ca4f45a83cacf274047c75f3b15f1a6684ba5bf176d51571c AS base

USER root

RUN install-php-extensions sockets intl pdo_sqlite

COPY --from=oven/bun:1.4.2-alpine /usr/local/bin/bun /usr/local/bin/bun

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID

USER www-data

FROM base AS dev

USER root

RUN apk add --no-cache git nodejs postgresql18-client

USER www-data

# Chromium needs glibc; keep browser tooling outside the Alpine application image.
FROM node:24-bookworm-slim AS browser

COPY --from=oven/bun:1.4.2 /usr/local/bin/bun /usr/local/bin/bun

WORKDIR /opt/playwright

ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

COPY bun.lock /tmp/playwright-bun.lock

RUN PLAYWRIGHT_VERSION="$(bun -e 'const lock = Bun.JSONC.parse(await Bun.file("/tmp/playwright-bun.lock").text()); const version = lock.packages.playwright[0].split("@")[1]; if (!/^\d+\.\d+\.\d+$/.test(version)) throw new Error("Expected a stable locked Playwright version"); console.log(version);')" && \
    bun add --cwd /opt/playwright --exact --ignore-scripts "playwright@${PLAYWRIGHT_VERSION}" && \
    /opt/playwright/node_modules/.bin/playwright install --with-deps chromium && \
    chmod -R a+rX /ms-playwright && \
    rm -rf /var/lib/apt/lists/* /root/.bun/install/cache /tmp/playwright-bun.lock

USER node

EXPOSE 3000

HEALTHCHECK --interval=5s --timeout=3s --start-period=10s --retries=5 \
    CMD node -e 'const socket = require("node:net").connect(3000, "127.0.0.1", () => socket.end()); socket.setTimeout(2000, () => { socket.destroy(); process.exit(1); }); socket.on("error", () => process.exit(1));'

CMD ["node", "node_modules/playwright/cli.js", "run-server", "--host", "0.0.0.0", "--port", "3000"]

FROM base AS production
