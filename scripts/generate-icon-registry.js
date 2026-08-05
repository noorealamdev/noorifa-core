/**
 * Generates includes/Blocks/WP_Icon_Registry.php from the full
 * @wordpress/icons library, so PHP (server-side render) and JS (editor
 * picker) share the exact same ~330 icons under the exact same keys,
 * without hand-copying SVG path data per icon.
 *
 * Run manually with `node scripts/generate-icon-registry.js` whenever
 * @wordpress/icons is updated — not part of the normal build, since this
 * library changes rarely.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const ReactDOMServer = require( 'react-dom/server' );
const icons = require( '@wordpress/icons' );

const entries = Object.keys( icons )
	.filter( ( key ) => key !== 'Icon' )
	.sort()
	.map( ( key ) => {
		// Each export here is already a rendered React *element* (built
		// with (0, jsx)(SVG, {...}) at module load time), not a component
		// function — pass it straight to renderToStaticMarkup as-is.
		const markup = ReactDOMServer.renderToStaticMarkup( icons[ key ] );
		return [ key, markup ];
	} );

/*
 * A few hand-drawn additions @wordpress/icons doesn't have (no health/
 * wellness icons in that library) — prefixed "nt-" so they can never
 * collide with a future @wordpress/icons export name. Kept here (not in
 * a separate file) so regenerating this registry doesn't require
 * re-adding them by hand.
 */
const EXTRA_ICONS = {
	'nt-shieldCheck':
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
	'nt-leaf':
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 19c0-8 5-15 14-15 0 9-6 15-14 15z"/><path d="M5 19c3-3 6-6 9-11"/></svg>',
	'nt-capsule':
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17a5 5 0 010-7l3-3a5 5 0 017 7l-3 3a5 5 0 01-7 0z"/><path d="M9.5 9.5l5 5"/></svg>',
	'nt-medal':
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4.5"/><path d="M9 12l-2 8 5-3 5 3-2-8"/></svg>',
};

Object.entries( EXTRA_ICONS ).forEach( ( [ key, markup ] ) => {
	entries.push( [ key, markup ] );
} );

const phpEntries = entries
	.map( ( [ key, markup ] ) => {
		const escaped = markup.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" );
		return `\t\t\t'${ key }' => '${ escaped }',`;
	} )
	.join( '\n' );

const php = `<?php
/**
 * Auto-generated icon registry mirroring the full @wordpress/icons
 * library (same icons, same keys, used throughout the block editor's
 * own UI) — regenerate with \`node scripts/generate-icon-registry.js\`
 * whenever @wordpress/icons is updated. Do not hand-edit.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Looks up ready-to-echo <svg>...</svg> markup by @wordpress/icons key.
 */
class WP_Icon_Registry {

	/**
	 * Icon key => full <svg> markup (as rendered by @wordpress/icons).
	 *
	 * @return array<string,string>
	 */
	public static function get_icons() {
		return array(
${ phpEntries }
		);
	}

	/**
	 * Renders the <svg> markup for a given icon key.
	 *
	 * @param string $key     Icon key (matches an @wordpress/icons export name).
	 * @param string $fallback Icon key to use if $key isn't recognized.
	 * @return string
	 */
	public static function render( $key, $fallback = 'shield' ) {
		$icons = self::get_icons();

		return $icons[ $key ] ?? ( $icons[ $fallback ] ?? '' );
	}
}
`;

const outPath = path.resolve(
	__dirname,
	'../includes/Blocks/WP_Icon_Registry.php'
);
fs.writeFileSync( outPath, php );
console.log( `Wrote ${ entries.length } icons to ${ outPath }` );
