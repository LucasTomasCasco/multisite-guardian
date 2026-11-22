#!/bin/sh
# Builds a demo Multisite network for local evaluation. Safe to run more than once.
#
# Runs inside the wpcli service:
#   docker compose run --rm wpcli sh /scripts/bootstrap-network.sh
#
# Network updates come from WordPress's own update checks, which contact wordpress.org.
# If the sandbox is offline, update statuses stay "unknown" and the rest of the demo still works.

set -eu

URL="${GUARDIAN_URL:-http://localhost:8095}"
ADMIN_PASSWORD="${GUARDIAN_ADMIN_PASSWORD:?GUARDIAN_ADMIN_PASSWORD must be set}"
DEMO=/demo
PLUGIN_DIR=wp-content/plugins

log() {
	printf '==> %s\n' "$1"
}

site_url() {
	# Sites are path-based: http://localhost:8095/<slug>/. The main site is the empty slug.
	if [ -z "$1" ]; then
		printf '%s/' "$URL"
	else
		printf '%s/%s/' "$URL" "$1"
	fi
}

site_id() {
	wp eval "echo get_id_from_blogname( '$1' );"
}

ensure_site() {
	slug="$1"
	title="$2"
	if ! wp site list --field=path | grep -qx "/$slug/"; then
		wp site create --slug="$slug" --title="$title" --email="owner-$slug@guardian.test" >/dev/null
		log "created site $slug"
	fi
}

set_site_status() {
	# $1 = site slug, $2 = status (archived | spam | deleted)
	id=$(site_id "$1")
	case "$2" in
		archived) wp site archive "$id" >/dev/null ;;
		spam) wp site spam "$id" >/dev/null ;;
		deleted) wp eval "wp_update_site( $id, array( 'deleted' => 1 ) );" ;;
	esac
}

# Waits up to 2 minutes so a broken database fails loudly instead of hanging.
wait_for() {
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

log "waiting for WordPress and the database"
wait_for "wp-config.php" test -f wp-config.php
# A missing installation is fine here. Only a failed database connection means "not ready yet".
database_ready() {
	output=$(wp core is-installed --url="$URL" 2>&1 || true)
	case "$output" in
		*"Error establishing a database connection"*) return 1 ;;
		*) return 0 ;;
	esac
}

wait_for "database connection" database_ready

if ! wp core is-installed --network >/dev/null 2>&1; then
	log "installing Multisite"
	wp core multisite-install \
		--url="$URL" \
		--title="Multisite Guardian demo" \
		--admin_user=admin \
		--admin_password="$ADMIN_PASSWORD" \
		--admin_email=admin@guardian.test \
		--skip-email
fi

log "creating sites"
ensure_site editorial "Editorial"
ensure_site shop "Shop"
ensure_site news "News"
ensure_site portfolio "Portfolio"
ensure_site press "Press office"
ensure_site docs "Documentation"
ensure_site landing "Landing pages"
ensure_site legacy-archive "Legacy archive"
ensure_site spam-test "Spam test"
ensure_site old-campaign "Old campaign"

set_site_status legacy-archive archived
set_site_status spam-test spam
set_site_status old-campaign deleted

# Application passwords (Basic auth for the REST API) require HTTPS unless the environment is "local".
# This sandbox is local-only, so it sets that explicitly.
wp config set WP_ENVIRONMENT_TYPE local --type=constant >/dev/null

log "installing demo fixtures"
cp -r "$DEMO/themes/guardian-child" "$DEMO/themes/guardian-unused" wp-content/themes/
cp -r "$DEMO/plugins/guardian-demo-seo" "$DEMO/plugins/guardian-demo-forms" "$PLUGIN_DIR/"
mkdir -p wp-content/mu-plugins
cp "$DEMO/mu-plugins/guardian-demo-mu.php" wp-content/mu-plugins/
cp "$DEMO/dropins/advanced-cache.php" wp-content/

log "enabling themes network-wide"
for theme in twentytwentythree twentytwentyfour twentytwentyfive guardian-child; do
	wp theme enable "$theme" --network >/dev/null
done

log "assigning themes and plugins per site"
wp theme activate twentytwentyfive --url="$(site_url '')" >/dev/null
wp theme activate twentytwentyfour --url="$(site_url editorial)" >/dev/null
wp theme activate guardian-child --url="$(site_url shop)" >/dev/null
wp theme activate twentytwentyfour --url="$(site_url news)" >/dev/null
wp theme activate twentytwentythree --url="$(site_url portfolio)" >/dev/null

wp plugin activate hello --network >/dev/null
wp plugin activate akismet --url="$(site_url shop)" >/dev/null
wp plugin activate akismet --url="$(site_url press)" >/dev/null
wp plugin activate guardian-demo-forms --url="$(site_url news)" >/dev/null
wp plugin activate guardian-demo-forms --url="$(site_url docs)" >/dev/null
wp plugin activate multisite-guardian --network >/dev/null

log "adding one reference to a missing plugin file (for the audit)"
wp eval '
	$blog_id = get_id_from_blogname( "press" );
	$missing = "guardian-demo-removed/guardian-demo-removed.php";
	$active  = get_blog_option( $blog_id, "active_plugins", array() );
	if ( ! in_array( $missing, $active, true ) ) {
		$active[] = $missing;
		update_blog_option( $blog_id, "active_plugins", $active );
	}
'

log "creating a site administrator who is not a network administrator"
if ! wp user get newsadmin >/dev/null 2>&1; then
	wp user create newsadmin newsadmin@guardian.test --role=administrator \
		--user_pass="$ADMIN_PASSWORD" --url="$(site_url news)" >/dev/null
fi

log "running WordPress update checks (contacts wordpress.org; failure is tolerated)"
wp cron event run wp_update_plugins wp_update_themes >/dev/null 2>&1 || log "update checks unavailable, statuses will show as unknown"

log "done: $URL/wp-admin/network/admin.php?page=multisite-guardian"
