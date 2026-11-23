// A site administrator must not reach any network data, from the screens or from the REST API.
const { test, expect } = require( '@playwright/test' );
const { SITE_ADMIN, login, networkPage } = require( './helpers' );

// A site under this path is one the site administrator belongs to.
const SITE_PATH = process.env.E2E_SITE_PATH || '/editorial';

test.describe( 'site administrator', () => {
	test( 'network screens are forbidden', async ( { page } ) => {
		await login( page, SITE_ADMIN, '/wp-login.php' );

		const response = await page.goto( networkPage( 'multisite-guardian' ) );

		expect( response.status() ).toBe( 403 );
		await expect( page.locator( 'body' ) ).toContainText( 'not allowed' );
	} );

	test( 'network REST routes refuse the request', async ( { page } ) => {
		await login( page, SITE_ADMIN, '/wp-login.php' );

		const response = await page.request.get( '/wp-json/multisite-guardian/v1/overview' );

		expect( [ 401, 403 ] ).toContain( response.status() );
	} );

	test( 'the site dashboard has no Multisite Guardian menu entry', async ( { page } ) => {
		await login( page, SITE_ADMIN, '/wp-login.php' );
		await page.goto( `${ SITE_PATH }/wp-admin/` );

		await expect( page.locator( '#adminmenu' ) ).not.toContainText( 'Multisite Guardian' );
	} );
} );
