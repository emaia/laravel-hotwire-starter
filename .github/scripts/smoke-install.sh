#!/usr/bin/env bash
set -euo pipefail

package=emaia/laravel-hotwire-starter
mode=${1:-}
version=${2:-}
if [[ "$mode" == archive && $# == 1 ]]; then
    version=0.0.0
elif [[ "$mode" != packagist || $# != 2 || ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo 'Usage: smoke-install.sh archive | packagist X.Y.Z' >&2
    exit 2
fi

scratch=$(mktemp -d "${TMPDIR:-/tmp}/hotwire-install.XXXXXXXX")
suffix=$(printf '%s' "${scratch##*.}" | LC_ALL=C tr '[:upper:]' '[:lower:]')
project="hotwire-install-$suffix"
image="$project:dev"
app="$scratch/my-app"
override="$scratch/compose.smoke.yaml"
host_uid=$(id -u)
host_gid=$(id -g)

compose() {
    docker compose --project-name "$project" \
        --project-directory "$app" -f "$app/compose.dev.yaml" -f "$override" "$@"
}

cleanup() {
    if [[ -f "$app/.env" && -f "$override" ]]; then
        compose down --volumes >/dev/null 2>&1 || true
        docker image rm "$image" >/dev/null 2>&1 || true
    fi
    rm -r -- "$scratch"
}
trap cleanup EXIT

composer_args=(create-project)
if [[ "$mode" == archive ]]; then
    # Match GitHub's ZIP structure and honor the distribution's export attributes.
    git archive --format=zip --prefix=starter/ HEAD > "$scratch/starter.zip"
    metadata=$(jq -nc --arg name "$package" --arg version "$version" '{
        type: "package", package: {name: $name, type: "project", version: $version,
        dist: {type: "zip", url: "file:///app/starter.zip"}}
    }')
    composer_args+=(--repository="$metadata")
fi

docker run --rm --user "$host_uid:$host_gid" -e COMPOSER_HOME=/tmp/composer \
    -v "$scratch:/app" -w /app composer:2 \
    "${composer_args[@]}" "$package" my-app "$version" --no-install --no-scripts --no-interaction

cd "$app"
test ! -e vendor
[ -f .env ] || cp .env.example .env
grep -q '^UID=' .env || printf 'UID=%s\n' "$host_uid" >> .env
grep -q '^GID=' .env || printf 'GID=%s\n' "$host_gid" >> .env
grep -qx 'DB_CONNECTION=pgsql' .env

# Isolate host bindings and image tags without changing Caddy's advertised HTTPS port.
cat > "$override" <<YAML
services:
  app:
    image: $image
  scheduler:
    image: $image
  queue:
    image: $image
  db:
    ports: !override
      - '127.0.0.1::5432'
  mailpit:
    ports: !override
      - '127.0.0.1::8025'
  proxy:
    ports: !override
      - '127.0.0.1::80'
      - '127.0.0.1::443'
YAML

compose run --rm --build app composer setup
if ! compose up -d --wait --wait-timeout 90; then
    compose ps
    compose logs --tail 30
    exit 1
fi

https_binding=$(compose port --protocol tcp proxy 443)
origin=https://hotwire-starter.localhost:8443
request=(curl --fail --silent --show-error --max-time 10
    --cacert "$scratch/caddy.crt"
    --connect-to "hotwire-starter.localhost:8443:127.0.0.1:${https_binding##*:}")
ready=false
for attempt in {1..15}; do
    if compose exec -T proxy cat /data/caddy/pki/authorities/local/root.crt > "$scratch/caddy.crt" 2>"$scratch/proxy-probe.log" &&
        "${request[@]}" "$origin/up" >/dev/null 2>>"$scratch/proxy-probe.log"; then
        ready=true
        break
    fi
    if [[ "$attempt" -lt 15 ]]; then sleep 2; fi
done
if [[ "$ready" == false ]]; then
    echo 'Caddy did not serve HTTPS with its local CA.' >&2
    cat "$scratch/proxy-probe.log" >&2
    compose logs --tail 30 proxy
    exit 1
fi

http_binding=$(compose port --protocol tcp proxy 80)
redirect=$(curl --fail --silent --show-error --max-time 10 --output /dev/null --write-out '%{redirect_url}' \
    --connect-to "hotwire-starter.localhost:8080:127.0.0.1:${http_binding##*:}" \
    http://hotwire-starter.localhost:8080/login)
test "$redirect" = "$origin/login"
"${request[@]}" "$origin/login" > "$scratch/login.html"
for entry in resources/css/app.css resources/js/app.js; do
    asset=$(jq -er --arg entry "$entry" '.[$entry].file' public/build/manifest.json)
    url="$origin/build/$asset"
    grep -Fq "$url" "$scratch/login.html"
    "${request[@]}" "$url" >/dev/null
done
echo "Installed $package $version from $mode; PostgreSQL, HTTPS, login and compiled assets verified."
