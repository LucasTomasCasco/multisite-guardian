# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [0.2.0] - 2026-11-23

### Added

- Plugin matrix: a plugin-by-site grid for networks of up to 25 live sites.
- Spanish (es_ES) translation of the interface and every audit finding, with a `.pot` template for more languages.
- End-to-end tests with Playwright in CI: network admin screens, matrix, drill-down, audit, report download, and site administrator refusal.
- CLI checks in CI, run against a freshly built Multisite network.
- CI matrix now covers WordPress 6.5.5 (the declared minimum) and MariaDB 10.11.
- Security policy (`SECURITY.md`) with private vulnerability reporting.

### Changed

- The audit reports `WP_DEBUG` as Critical when `WP_DEBUG_DISPLAY` shows errors to visitors.
- Snapshot read uses one blog switch per site, restored in a `finally` block.
- Refresh requests within one minute of the last snapshot return the cached copy.
- Snapshot cache schema is versioned (schema 2). Older cached snapshots are rebuilt.
- Report downloads send `X-Content-Type-Options: nosniff`.
- The Docker demo binds to localhost only and uses port 8095.

### Fixed

- Demo subdirectory rewrite rules, so `/site/wp-admin/` no longer redirects to itself.
- A cached snapshot from an earlier version could break the overview page with a fatal error.

## [0.1.0] - 2026-11-22

### Added

- Network Admin section with Overview, Sites, Plugins, Themes and Audit pages.
- Sites inventory with status classification (enabled, archived, spam, deleted), search, filter views and pagination.
- Plugin inventory that separates network-active, site-active, inactive, must-use and drop-in entries, with a per-plugin list of the sites where it is active.
- Theme inventory with network enablement, usage per site, and unused detection that treats a parent of an in-use child theme as in use.
- Update status for plugins and themes from WordPress's own update data, classified as update available, up to date, outdated, or unknown.
- Deterministic audit with nine rules: `PLUGIN_UPDATE_AVAILABLE`, `THEME_UPDATE_AVAILABLE`, `UPDATE_DATA_STALE`, `PLUGIN_UNUSED`, `THEME_UNUSED`, `SITE_NOT_ENABLED`, `PLUGIN_REFERENCE_MISSING`, `DEBUG_MODE_ENABLED` and `PHP_BELOW_MINIMUM`.
- REST API under `multisite-guardian/v1`: `overview`, `sites`, `sites/{id}`, `plugins`, `themes` and `audit`, with capability checks, parameter validation and `X-WP-Total` pagination headers.
- WP-CLI commands under `guardian`: `overview`, `sites`, `plugins`, `themes`, `audit` and `report`, with table, JSON and CSV output where applicable. `audit --fail-on=<severity>` exits with status 1 for CI gating.
- Consolidated report in JSON and CSV, downloadable from the Audit page and available from WP-CLI. CSV cells are protected against spreadsheet formula injection.
- Local Docker Compose environment (MySQL 8.0, WordPress 6.8, WP-CLI) and a bootstrap script that builds a demo Multisite network.
- Unit tests for the rules, engine, services and CSV exporter; integration tests against the WordPress PHPUnit suite in Multisite mode; GitHub Actions workflow for PHP lint, WordPress Coding Standards, unit and integration tests.

### Notes

- Requires WordPress Multisite (6.5 or later, tested on 6.5.5 and 6.8.3) and PHP 8.3 or later. MySQL 8.0 and MariaDB 10.11 are tested. The PHP minimum is a documented policy that can be changed with the `multisite_guardian_minimum_php_version` filter. PHP 8.2 was dropped because its security support ends at the end of 2026.
- The plugin makes no requests to WordPress.org or any other external service. Update status depends on the update checks WordPress already runs.
