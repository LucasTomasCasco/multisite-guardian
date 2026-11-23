// Shared helpers for the end-to-end suite. Credentials come from the environment, with local-sandbox defaults.
const ADMIN = {
	user: process.env.E2E_ADMIN_USER || 'admin',
	password: process.env.E2E_ADMIN_PASSWORD || 'guardian-local-only',
};

const SITE_ADMIN = {
	user: process.env.E2E_SITE_ADMIN_USER || 'siteadmin',
	password: process.env.E2E_SITE_ADMIN_PASSWORD || 'guardian-local-only',
};

async function login( page, credentials, loginPath = '/wp-login.php' ) {
	await page.goto( loginPath );
	await page.fill( '#user_login', credentials.user );
	await page.fill( '#user_pass', credentials.password );
	await Promise.all( [ page.waitForLoadState(), page.click( '#wp-submit' ) ] );
}

function networkPage( slug, query = '' ) {
	return `/wp-admin/network/admin.php?page=${ slug }${ query }`;
}

module.exports = { ADMIN, SITE_ADMIN, login, networkPage };
