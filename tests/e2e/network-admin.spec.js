// What a network administrator sees and can do in the Multisite Guardian screens.
const { test, expect } = require( '@playwright/test' );
const { ADMIN, login, networkPage } = require( './helpers' );

test.describe( 'network administrator', () => {
	test.beforeEach( async ( { page } ) => {
		await login( page, ADMIN );
	} );

	test( 'overview shows numeric network totals', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian' ) );

		await expect( page.getByRole( 'heading', { name: 'Multisite Guardian', level: 1 } ) ).toBeVisible();
		const totalRow = page.locator( 'tr', { has: page.locator( 'th', { hasText: 'Total sites' } ) } );
		await expect( totalRow.locator( 'td' ) ).toHaveText( /^\d+$/ );
	} );

	test( 'sites list filters by search and ignores unknown status values', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-sites', '&s=Editorial' ) );
		await expect( page.getByRole( 'link', { name: 'Editorial', exact: true } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Legacy archive', exact: true } ) ).toHaveCount( 0 );

		await page.goto( networkPage( 'multisite-guardian-sites', '&status=bogus' ) );
		await expect( page.locator( 'table.sites tbody tr' ).first() ).toBeVisible();
	} );

	test( 'plugin drill-down lists the sites where a plugin is active', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-plugins' ) );
		await page.getByRole( 'link', { name: 'View sites' } ).first().click();

		await expect( page.getByRole( 'heading', { name: /Sites where .* is active/ } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Open admin' } ).first() ).toBeVisible();
	} );

	test( 'matrix shows one column per live site on a small network', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-matrix' ) );

		await expect( page.getByRole( 'heading', { name: 'Plugin matrix' } ) ).toBeVisible();
		const headers = page.locator( 'table thead th' );
		// First column is the plugin name; the rest are sites.
		expect( await headers.count() ).toBeGreaterThan( 2 );
	} );

	test( 'running an audit saves it and reports the time', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-audit' ) );
		await page.getByRole( 'button', { name: 'Run and save audit' } ).click();

		await expect( page.getByText( 'Audit saved.' ) ).toBeVisible();
		await expect( page.getByText( /Last saved audit:/ ) ).toBeVisible();
	} );

	test( 'JSON report downloads as an attachment with every section', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-audit' ) );
		const href = await page.getByRole( 'link', { name: 'Download JSON report' } ).getAttribute( 'href' );

		const response = await page.request.get( new URL( href, page.url() ).toString() );

		expect( response.status() ).toBe( 200 );
		expect( response.headers()[ 'content-disposition' ] ).toContain( 'attachment' );
		expect( response.headers()[ 'x-content-type-options' ] ).toBe( 'nosniff' );
		const report = await response.json();
		expect( Object.keys( report ) ).toEqual( expect.arrayContaining( [ 'report', 'summary', 'sites', 'plugins', 'themes', 'findings' ] ) );
	} );

	test( 'the audit history lists the runs saved from the page', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-audit' ) );
		await page.getByRole( 'button', { name: 'Run and save audit' } ).click();

		await expect( page.getByRole( 'heading', { name: 'Audit history' } ) ).toBeVisible();
		await expect( page.locator( 'table' ).filter( { has: page.locator( 'th', { hasText: 'Critical' } ) } ).last().locator( 'tbody tr' ).first() ).toBeVisible();
	} );

	test( 'themes can be filtered to the ones no site uses', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-themes', '&status=unused' ) );

		await expect( page.getByRole( 'link', { name: /Unused/ } ) ).toHaveClass( /current/ );
		await expect( page.locator( 'table.themes tbody tr' ).first() ).toBeVisible();
	} );

	test( 'plugins can be filtered by activation scope', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-plugins', '&scope=network_active' ) );

		await expect( page.getByRole( 'link', { name: /Network active/ } ) ).toHaveClass( /current/ );
		await expect( page.locator( 'table.plugins tbody' ) ).toContainText( 'Network active' );
	} );

	test( 'CSV findings download with their header row', async ( { page } ) => {
		await page.goto( networkPage( 'multisite-guardian-audit' ) );
		const href = await page.getByRole( 'link', { name: 'Download CSV findings' } ).getAttribute( 'href' );

		const response = await page.request.get( new URL( href, page.url() ).toString() );

		expect( response.status() ).toBe( 200 );
		expect( response.headers()[ 'content-type' ] ).toContain( 'text/csv' );
		expect( ( await response.text() ).split( '\r\n' )[ 0 ] ).toBe( '"rule_id","severity","resource_type","resource","description","evidence","recommendation"' );
	} );

	test( 'search terms are escaped and never run as markup', async ( { page } ) => {
		const dialogs = [];
		page.on( 'dialog', ( dialog ) => {
			dialogs.push( dialog.message() );
			dialog.dismiss();
		} );

		await page.goto( networkPage( 'multisite-guardian-sites', '&s=%3Cimg%20src%3Dx%20onerror%3Dalert(1)%3E' ) );

		expect( dialogs ).toEqual( [] );
		expect( await page.content() ).not.toContain( '<img src=x onerror=alert(1)>' );
	} );
} );
