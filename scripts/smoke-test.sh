#!/bin/sh
# Checks the plugin's CLI output and exit codes against the running demo network.
# Run after: docker compose up -d && docker compose run --rm wpcli sh /scripts/bootstrap-network.sh
#
#   sh scripts/smoke-test.sh

set -eu

wp_cli() {
	docker compose run --rm -T wpcli wp "$@" --url=http://localhost:8095 2>/dev/null
}

pass() {
	printf 'PASS  %s\n' "$1"
}

fail() {
	printf 'FAIL  %s\n' "$1" >&2
	exit 1
}

wp_cli guardian overview --format=json | python3 -m json.tool >/dev/null || fail "overview JSON is valid"
pass "overview --format=json is valid JSON"

# The demo network contains a critical finding, so --fail-on=critical must exit with status 1.
set +e
wp_cli guardian audit --fail-on=critical >/dev/null
status=$?
set -e
[ "$status" -eq 1 ] || fail "audit --fail-on=critical exits 1 when a critical finding exists (got $status)"
pass "audit --fail-on=critical exits 1 when a critical finding exists"

wp_cli guardian audit --severity=information --format=csv >/dev/null || fail "audit --severity=information runs"
pass "audit --severity filters and exports CSV"

# RFC 4180 uses CRLF line endings, so the header line ends with a carriage return.
header=$(wp_cli guardian report --format=csv | head -1 | tr -d '\r')
[ "$header" = '"rule_id","severity","resource_type","resource","description","evidence","recommendation"' ] \
	|| fail "CSV report has the expected header (got: $header)"
pass "report --format=csv has the expected header"

wp_cli guardian report --format=json | python3 -c '
import json, sys
report = json.load(sys.stdin)
assert set(report) == {"report", "summary", "sites", "plugins", "themes", "findings"}, set(report)
assert report["findings"], "expected findings in the demo network"
' || fail "report --format=json has every section"
pass "report --format=json has every section"

archived=$(wp_cli guardian sites --status=archived --format=csv | tail -n +2 | wc -l | tr -d ' ')
[ "$archived" -eq 1 ] || fail "sites --status=archived lists the one archived demo site (got $archived)"
pass "sites --status=archived lists the archived demo site"

if wp_cli guardian themes --status=bogus >/dev/null 2>&1; then
	fail "invalid --status is rejected"
fi
pass "invalid --status is rejected"

printf '\nAll smoke checks passed.\n'
