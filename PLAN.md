# Multisite Guardian — Implementation Plan

## 1. Critical review of the specification

Inconsistencies, risks and simplifications identified before implementation.

| # | Topic | Finding | Decision |
|---|-------|---------|----------|
| 1 | Plugin matrix (5.3) | A full plugin × site matrix is unreadable beyond ~10 sites and ~20 plugins. | Per-plugin drill-down ("sites where active") plus a per-site count column. This is the "equivalent view" the spec allows. |
| 2 | Persistent snapshots (5.1, 9) | Historical snapshots need a custom table, dbDelta, schema versioning. The spec marks history as secondary. | No custom table in the MVP. The last audit result and timestamp are stored in one network option. History is listed in the roadmap. |
| 3 | Vulnerabilities (5.5) | No vulnerability source is in scope (section 15 excludes external vulnerability databases). | Outdated items are reported as "update available", never as "vulnerable". |
| 4 | Update data (5.3, 10) | WordPress already fetches update data through cron into site transients. Calling api.wordpress.org again is forbidden. | Read only `update_plugins` and `update_themes` site transients. Data is classified as `update_available`, `up_to_date`, `stale` (older than two days) or `unknown` (transient missing or plugin absent). |
| 5 | switch_to_blog (9) | Switching blogs for every site is a source of Multisite context bugs. | Each site is read inside one `switch_to_blog()` call with `restore_current_blog()` in a `finally` block. The first version used `get_blog_option()` per option (four switches per site); a review found that and it was changed. |
| 6 | Overview vs. Sites Inventory (5.1, 5.2) | Both need the same per-site data. Duplicating the queries is wasteful. | One `NetworkState` builder feeds the overview, the lists, the REST API, the audit and the reports. |
| 7 | Critical severity (5.5) | "Critical" needs an objective criterion. | Only two rules use it: an active plugin reference whose file no longer exists, and a PHP version below the documented policy minimum. |
| 8 | PHP policy (5.5) | The spec does not name a minimum version. This is a business decision. | Minimum is PHP 8.3. PHP 8.2 security support ends at the end of 2026. The value can be changed with the `multisite_guardian_minimum_php_version` filter. |
| 9 | Audit on every admin load (10) | Forbidden by the spec. | The audit runs only on explicit request (admin button or WP-CLI). Its last result is stored. |
| 10 | Inventory on every admin load | Building the full state reads several options per site. | The state is cached in a site transient for 10 minutes. The admin can refresh it. WP-CLI always builds it fresh. |
| 11 | CSV exports (5.6) | Reports must include a resource summary, but a CSV file holds one table. | CSV exports the findings table only. JSON contains everything. This is documented as a limitation. |
| 12 | Installing "public" themes and plugins for demo data | The demo network needs third-party themes and plugins from WordPress.org at setup time. | Acceptable at development time only. Documented in the README. The application itself makes no external requests. |
| 13 | Multiple dashboards for a per-site admin | A site administrator must not see network data. | Network menu and REST routes require network capabilities. Tests cover both. |

## 2. Requirements, observations and decisions

- **Confirmed requirements:** the scope in sections 4 to 17 of the specification.
- **Observed facts:** local PHP 8.5.6, Docker 29.9 with the daemon running, Composer available, WP-CLI not installed on the host (it will run in a container). The existing `commerce-sync` plugin uses PHPUnit 9.6, yoast/phpunit-polyfills and `WP_TESTS_DIR`. That convention is reused.
- **Inferences:** the plugin's audience is network administrators (super admins). A per-site admin is never a valid user for network data.
- **Technical decisions:**
  - Namespace `MultisiteGuardian\`. A small PSR-4 autoloader lives in the main file, so the release ZIP needs no `vendor/` directory at runtime. Composer is used only for development tooling.
  - Capabilities: overview, audit, reports need `manage_network`. Sites need `manage_sites`. Plugins need `manage_network_plugins`. Themes need `manage_network_themes`.
  - Output: `WP_List_Table` for Sites, Plugins, Themes and Findings. This is the native WordPress convention.
  - REST responses use `X-WP-Total` and `X-WP-TotalPages` headers, the core convention.

## 3. Architecture

```
multisite-guardian.php      bootstrap, autoloader, activation guard
src/Plugin.php              hook registration
src/Inventory/NetworkState.php   single source of network data (cached)
src/Services/*.php          Overview, Sites, Plugins, Themes, Audit, Report
src/Audit/Rule.php          rule contract
src/Audit/Rules/*.php       deterministic rules
src/Audit/Engine.php        runs rules, sorts findings
src/Audit/Finding.php       finding factory and severity constants
src/Reports/Exporter.php    JSON and CSV (with formula protection)
src/REST/Controller.php     multisite-guardian/v1 routes
src/CLI/Command.php         wp guardian ...
src/Admin/*.php             menu, pages, WP_List_Table subclasses
```

Data flow: `NetworkState` → `Services` → (`Admin` | `REST` | `CLI` | `Reports`). The audit consumes the same `NetworkState`.

## 4. Milestones and acceptance criteria

1. **Foundation:** Docker Compose (MySQL 8, WordPress 6.8, WP-CLI), a multisite bootstrap script with sample sites, themes and plugin combinations. Criterion: `docker compose up` gives a working network at `localhost:8095`.
2. **Core inventory:** `NetworkState`, Services. Criterion: integration tests verify site statuses, network vs. site activation, mu-plugins and drop-ins, theme usage with child-theme dependencies, and context restoration.
3. **Audit engine:** nine rules. Criterion: unit tests with synthetic states for every rule, both triggering and not triggering.
4. **Interfaces:** Network Admin pages, REST API, WP-CLI, JSON and CSV export. Criterion: REST permission tests for a super admin and a per-site admin; CLI output tests in table, JSON and CSV.
5. **Quality:** PHPCS with WordPress Coding Standards, PHP lint, PHPUnit unit and integration suites, GitHub Actions workflow, a 50-site benchmark script. Criterion: all checks pass locally, and the benchmark results are recorded as measured.
6. **Publication:** README, CHANGELOG, LICENSE (GPL-2.0-or-later), release ZIP, clean-install verification. Screenshots are added only if they are taken from the running environment.

## 5. Out of scope (section 15)

Remote site management, mass updates, mass activation, backups, malware scanning, external vulnerability feeds, notifications, SaaS, payments, React or Vue, telemetry, remote HTTP monitoring.

## 6. Status at the end of the MVP work

| Milestone | Status | Evidence |
|-----------|--------|----------|
| 1. Foundation | Done | `docker compose up` with MySQL 8.0, WordPress 6.8.3 and WP-CLI 2.12. `scripts/bootstrap-network.sh` builds 11 sites with real themes, plugins, a must-use plugin, a drop-in and a missing plugin reference. |
| 2. Core inventory | Done | Integration suite: 18 tests, 44 assertions, 1 explicit skip (no must-use plugins in the test network). |
| 3. Audit engine | Done | 9 rules with unit tests on synthetic snapshots (30 tests in total with the other unit tests). |
| 4. Interfaces | Done | Five Network Admin pages, six REST routes, `wp guardian` commands, JSON and CSV export. HTTP checks confirm 200/403/404 by role. |
| 5. Quality | Done, except items noted | PHPCS (WordPress Coding Standards) passes. CI workflow written but not run on GitHub. 50-site benchmark measured twice. |
| 6. Publication | Partly done | README, CHANGELOG, LICENSE, release ZIP and a clean-install script. No git repository has been created and nothing has been committed or published. |

### Deviations from the plan, and why

- **Docblocks.** The WordPress Squiz commenting sniffs are disabled. The project documents where it adds information, as its existing code does. Every other WPCS rule is enforced.
- **File names.** Classes use `class-*.php` names with hyphens (`class-sites-table.php`). WPCS expects `class-sitestable.php`. The hyphenated form matches WordPress core, so the WPCS sniff is disabled for this one rule.
- **`--fail-on` on `wp guardian audit`.** Not in the original command list. The brief asks for pipeline automation, and a CI job needs an exit code.
- **Deleted and spam sites are not usage.** A plugin or theme active only on those sites counts as unused. The brief does not say this, so it is a judgment call. It is documented in the README.
- **WP-CLI and reports do not write the cache.** Only the admin Refresh button rewrites the shared snapshot. This was a bug found during testing: a benchmark run left a stale site count on the dashboard.

## 7. Pending or deferred items

- Historical snapshots and trend charts (roadmap).
- Persisted per-site audit history.
- CSV export of resource summaries.
- Running the CI workflow on GitHub. It has only been checked locally and by reading it.
- Measurements above 50 sites. The specification forbids claiming support for larger networks without evidence.
