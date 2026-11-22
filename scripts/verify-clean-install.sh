#!/bin/sh
# Installs a release ZIP into a new WordPress Multisite with its own database and volumes, then runs the CLI.
# It uses separate containers and port 8091, so the demo environment is not touched.
#
#   sh scripts/build-release.sh
#   sh scripts/verify-clean-install.sh dist/multisite-guardian-<version>.zip

set -eu

ZIP="${1:?usage: verify-clean-install.sh <release.zip>}"
PREFIX=guardian-clean
PORT=8091
WORK=$(mktemp -d)

cleanup() {
	docker rm -fv "$PREFIX-wp" "$PREFIX-db" >/dev/null 2>&1 || true
	docker network rm "$PREFIX-net" >/dev/null 2>&1 || true
	rm -rf "$WORK"
}
trap cleanup EXIT

wait_until() {
	description="$1"
	shift
	attempts=0
	until "$@" >/dev/null 2>&1; do
		attempts=$((attempts + 1))
		if [ "$attempts" -ge 60 ]; then
			echo "timed out waiting for $description" >&2
			exit 1
		fi
		sleep 2
	done
}

wp_cli() {
	# wp-config.php reads the database settings from the environment of each process, so WP-CLI needs them too.
	docker run --rm --network "$PREFIX-net" --volumes-from "$PREFIX-wp" --user 33:33 \
		-e WORDPRESS_DB_HOST="$PREFIX-db" -e WORDPRESS_DB_USER=wordpress \
		-e WORDPRESS_DB_PASSWORD=wordpress -e WORDPRESS_DB_NAME=wordpress \
		wordpress:cli-2.12-php8.3 wp "$@" --url="http://localhost:$PORT"
}

unzip -q "$ZIP" -d "$WORK"

docker network create "$PREFIX-net" >/dev/null
docker run -d --name "$PREFIX-db" --network "$PREFIX-net" \
	-e MYSQL_DATABASE=wordpress -e MYSQL_USER=wordpress -e MYSQL_PASSWORD=wordpress -e MYSQL_ROOT_PASSWORD=root \
	mysql:8.0 --skip-ssl --default-authentication-plugin=mysql_native_password >/dev/null

wait_until "database" docker exec "$PREFIX-db" mysqladmin ping -h127.0.0.1 -uroot -proot --ssl-mode=DISABLED

docker run -d --name "$PREFIX-wp" --network "$PREFIX-net" -p "$PORT:80" \
	-e WORDPRESS_DB_HOST="$PREFIX-db" -e WORDPRESS_DB_USER=wordpress \
	-e WORDPRESS_DB_PASSWORD=wordpress -e WORDPRESS_DB_NAME=wordpress \
	wordpress:6.8-php8.3-apache >/dev/null

wait_until "wp-config.php" docker exec "$PREFIX-wp" test -f /var/www/html/wp-config.php

# The release folder is copied in as-is, so this also proves the ZIP runs without Composer or vendor/.
docker cp "$WORK/multisite-guardian" "$PREFIX-wp:/var/www/html/wp-content/plugins/"
docker exec "$PREFIX-wp" chown -R 33:33 /var/www/html/wp-content/plugins/multisite-guardian

wp_cli core multisite-install \
	--title="Clean install" --admin_user=admin --admin_password=clean-install-only \
	--admin_email=admin@guardian.test --skip-email >/dev/null

wp_cli plugin activate multisite-guardian --network >/dev/null
wp_cli plugin list --status=active-network --field=name | grep -qx multisite-guardian
echo "PASS  plugin network-activated from the release ZIP"

wp_cli guardian overview --format=json | python3 -m json.tool >/dev/null
echo "PASS  overview returns valid JSON on a fresh network"

wp_cli guardian audit --format=csv >/dev/null
echo "PASS  audit runs on a fresh network"

wp_cli guardian report --format=json | python3 -c 'import json, sys; json.load(sys.stdin)'
echo "PASS  report is valid JSON on a fresh network"

echo "Clean install verified. Containers and volumes are removed on exit."
