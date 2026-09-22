// Captures WordPress.org listing screenshots and regenerates readme.txt's Screenshots section from the same list. Run via `make screenshots`.
'use strict';

const fs = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

const BASE_URL = process.env.WP_BASE_URL || 'https://smart-wishlist.local';
const REPO_ROOT = path.join( __dirname, '..' );
const OUT_DIR = path.join( REPO_ROOT, 'wporg-assets' );
const README_PATH = path.join( REPO_ROOT, 'src', 'readme.txt' );
const VIEWPORT = { width: 1280, height: 800 };

function shotPath( file ) {
	return path.join( OUT_DIR, file );
}

async function login( page ) {
	await page.goto( `${ BASE_URL }/wp-login.php` );
	if ( page.url().includes( 'wp-login.php' ) ) {
		await page.fill( '#user_login', 'admin' );
		await page.fill( '#user_pass', 'password' );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );
	}
}

async function hideAdminNotices( page ) {
	await page.addStyleTag( {
		content:
			'#wpbody-content .notice, #wpbody-content .updated, #wpbody-content .error, .update-nag { display: none !important; }',
	} );
}

async function hideAdminBar( page ) {
	await page.addStyleTag( {
		content:
			'#wpadminbar { display: none !important; } html { margin-top: 0 !important; }',
	} );
}

const SHOTS = [
	{
		file: 'screenshot-1.png',
		caption: 'Wishlist toggle button on a single product page',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/product/e2e-wishlist-product/` );
			await page.locator( '.wpwing-wishlist-toggle:not(.wpwing-wishlist-loop-btn)' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await hideAdminBar( page );
			await page.screenshot( { path: shotPath( 'screenshot-1.png' ) } );
		},
	},
	{
		file: 'screenshot-2.png',
		caption: 'Customer saved wishlist page with running total and social sharing',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/my-wishlist/` );
			await page.locator( '.wpwing-wishlist-table' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await hideAdminBar( page );
			await page.screenshot( { path: shotPath( 'screenshot-2.png' ) } );
		},
	},
	{
		file: 'screenshot-3.png',
		caption: 'Back-in-stock waitlist form on an out-of-stock product page',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/product/e2e-oos-simple/` );
			await page.locator( '.wpwing-waitlist-form' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await hideAdminBar( page );
			await page.screenshot( { path: shotPath( 'screenshot-3.png' ) } );
		},
	},
	{
		file: 'screenshot-4.png',
		caption: 'Variation-aware waitlist form appears when an out-of-stock variation is selected',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/product/e2e-variable/` );
			const sizeSelect = page.locator( 'select#size, select[name="attribute_size"]' ).first();
			await sizeSelect.waitFor( { state: 'visible' } );
			await sizeSelect.selectOption( 'Large' );
			await page.locator( '.wpwing-waitlist-form:not(.wpwing-wl-hidden)' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await hideAdminBar( page );
			await page.screenshot( { path: shotPath( 'screenshot-4.png' ) } );
		},
	},
	{
		file: 'screenshot-5.png',
		caption: 'Admin Waitlist dashboard with subscriber details and CSV export',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/admin.php?page=wpwing-wl-waitlist` );
			await page.locator( '.wp-list-table' ).first().waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-5.png' ) } );
		},
	},
	{
		file: 'screenshot-6.png',
		caption: 'Admin Wishlist dashboard ranking most-saved products by customer demand',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/admin.php?page=wpwing-wl-wishlist` );
			await page.locator( '.wp-list-table' ).first().waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-6.png' ) } );
		},
	},
	{
		file: 'screenshot-7.png',
		caption: 'Admin Settings screen - feature toggles, styling, and bot protection',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/admin.php?page=wpwing-wl-settings` );
			await page.locator( 'form[action="options.php"]' ).first().waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-7.png' ) } );
		},
	},
	{
		file: 'screenshot-8.png',
		caption: 'Admin Email Logs screen - delivery status tracking, retry count, and failure details',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/admin.php?page=wpwing-wl-email-logs` );
			await page.locator( '.wp-list-table' ).first().waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-8.png' ) } );
		},
	},
];

function updateReadme() {
	const readme = fs.readFileSync( README_PATH, 'utf8' );
	const list = SHOTS.map(
		( shot, i ) => `${ i + 1 }. ${ shot.caption }.`
	).join( '\n' );
	const pattern = /== Screenshots ==\n\n[\s\S]*?(?=\n== )/;
	if ( ! pattern.test( readme ) ) {
		throw new Error(
			'Could not find a "== Screenshots ==" section in readme.txt to update.'
		);
	}
	const updated = readme.replace(
		pattern,
		() => `== Screenshots ==\n\n${ list }\n`
	);
	fs.writeFileSync( README_PATH, updated );
}

async function main() {
	fs.mkdirSync( OUT_DIR, { recursive: true } );

	const browser = await chromium.launch();
	const page = await (
		await browser.newContext( {
			viewport: VIEWPORT,
			ignoreHTTPSErrors: true,
		} )
	).newPage();

	// Log in first so admin sessions and cookies are ready.
	await login( page );

	for ( const shot of SHOTS ) {
		process.stdout.write(
			`Capturing ${ shot.file } (${ shot.caption })... `
		);
		try {
			await shot.capture( page );
		} catch {
			process.stdout.write( 'retrying... ' );
			await shot.capture( page );
		}
		console.log( 'done' );
	}

	await browser.close();
	updateReadme();
	console.log(
		`\nWrote ${ SHOTS.length } screenshots to wporg-assets/ and updated readme.txt.`
	);
}

main().catch( ( err ) => {
	console.error( '\nscreenshot capture failed:', err );
	process.exit( 1 );
} );

