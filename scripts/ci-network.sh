#!/bin/sh
# Builds a small Multisite network for the end-to-end and CLI checks. Used by CI only.
#
#   sh scripts/ci-network.sh <wordpress-path> <plugin-source-path>
#
# Expects: a MySQL database named wordpress_e2e reachable at 127.0.0.1 as root/root, and E2E_URL,
# E2E_ADMIN_PASSWORD and E2E_SITE_ADMIN_PASSWORD in the environment.

set -eu

WP_PATH="${1:?usage: ci-network.sh <wordpress-path> <plugin-source-path>}"
PLUGIN_SRC="${2:?usage: ci-network.sh <wordpress-path> <plugin-source-path>}"
URL="${E2E_URL:?E2E_URL must be set}"
ADMIN_PASSWORD="${E2E_ADMIN_PASSWORD:?E2E_ADMIN_PASSWORD must be set}"
SITE_ADMIN_PASSWORD="${E2E_SITE_ADMIN_PASSWORD:?E2E_SITE_ADMIN_PASSWORD must be set}"

wp() {
	command wp --path="$WP_PATH" --url="$URL" "$@"
}

site_id() {
	wp eval "echo get_id_from_blogname( '$1' );"
}

wp config create --dbname=wordpress_e2e --dbuser=root --dbpass=root --dbhost=127.0.0.1 --skip-check >/dev/null
wp core multisite-install --title="Guardian E2E" --admin_user=admin --admin_password="$ADMIN_PASSWORD" \
	--admin_email=admin@e2e.test --skip-email >/dev/null

wp site create --slug=editorial --title="Editorial" --email=owner-editorial@e2e.test >/dev/null
wp site create --slug=shop --title="Shop" --email=owner-shop@e2e.test >/dev/null
wp site create --slug=press --title="Press office" --email=owner-press@e2e.test >/dev/null
wp site create --slug=legacy --title="Legacy archive" --email=owner-legacy@e2e.test >/dev/null
wp site archive "$(site_id legacy)" >/dev/null

ln -s "$PLUGIN_SRC" "$WP_PATH/wp-content/plugins/multisite-guardian"
wp plugin activate multisite-guardian --network >/dev/null
wp plugin activate hello --network >/dev/null
wp plugin activate akismet --url="$URL/shop/" >/dev/null
wp plugin activate akismet --url="$URL/press/" >/dev/null

# One reference to a plugin file that does not exist, so the audit has a critical finding to report.
wp eval '
	$press = get_id_from_blogname( "press" );
	$active = get_blog_option( $press, "active_plugins", array() );
	update_blog_option( $press, "active_plugins", array_merge( $active, array( "ghost/ghost.php" ) ) );
' >/dev/null

wp user create siteadmin siteadmin@e2e.test --role=administrator \
	--user_pass="$SITE_ADMIN_PASSWORD" --url="$URL/editorial/" >/dev/null

# Subdirectory sites: /slug/wp-admin/ must resolve to the WordPress root, as Apache rewrites do on a real host.
for slug in editorial shop press legacy; do
	ln -s . "$WP_PATH/$slug"
done
cp "$PLUGIN_SRC/scripts/e2e-router.php" "$WP_PATH/router.php"
echo "network ready at $URL"
