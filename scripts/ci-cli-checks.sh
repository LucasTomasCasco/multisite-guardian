#!/bin/sh
# Runs the WP-CLI commands against the CI network and checks their output and exit codes.
#
#   E2E_URL=http://127.0.0.1:8088 sh scripts/ci-cli-checks.sh <wordpress-path>

set -eu

WP_PATH="${1:?usage: ci-cli-checks.sh <wordpress-path>}"
URL="${E2E_URL:?E2E_URL must be set}"

wp() {
	command wp --path="$WP_PATH" --url="$URL" "$@"
}

pass() {
	printf 'PASS  %s\n' "$1"
}

fail() {
	printf 'FAIL  %s\n' "$1" >&2
	exit 1
}

wp guardian overview --format=json | python3 -m json.tool >/dev/null || fail "overview prints valid JSON"
pass "overview --format=json is valid JSON"

set +e
wp guardian audit --fail-on=critical >/dev/null 2>&1
status=$?
set -e
[ "$status" -eq 1 ] || fail "audit --fail-on=critical exits 1 with a critical finding (got $status)"
pass "audit --fail-on=critical exits 1 with a critical finding"

header=$(wp guardian report --format=csv | head -1 | tr -d '\r')
[ "$header" = '"rule_id","severity","resource_type","resource","description","evidence","recommendation"' ] \
	|| fail "CSV report has the expected header (got: $header)"
pass "report --format=csv has the expected header"

wp guardian report --format=json | python3 -c '
import json, sys
report = json.load(sys.stdin)
assert set(report) == {"report", "summary", "sites", "plugins", "themes", "findings"}, set(report)
assert any(f["rule_id"] == "PLUGIN_REFERENCE_MISSING" for f in report["findings"]), "missing reference finding"
' || fail "report --format=json has every section and the missing-reference finding"
pass "report --format=json has every section and the missing-reference finding"

archived=$(wp guardian sites --status=archived --format=csv | tail -n +2 | wc -l | tr -d ' ')
[ "$archived" -eq 1 ] || fail "sites --status=archived lists the archived site (got $archived)"
pass "sites --status=archived lists the archived site"

if wp guardian themes --status=bogus >/dev/null 2>&1; then
	fail "an invalid --status is rejected"
fi
pass "an invalid --status is rejected"

printf '\nAll CLI checks passed.\n'
