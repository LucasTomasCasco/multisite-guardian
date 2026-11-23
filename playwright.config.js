// End-to-end configuration. Set E2E_BASE_URL to the Multisite network under test.
const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: 'tests/e2e',
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'list' ], [ 'html', { open: 'never' } ] ] : 'list',
	use: {
		baseURL: process.env.E2E_BASE_URL || 'http://localhost:8095',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
