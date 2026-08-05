/**
 * Extends the default @wordpress/scripts config with the template
 * library app and the block-extensions engine, neither of which are a
 * single block and therefore not auto-detected.
 *
 * With --experimental-modules the default export is an array of two
 * configs: [ scripts, modules ]. These are classic scripts, so they are
 * added to the scripts config only.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const addExtraEntries = ( config ) => ( {
	...config,
	entry: {
		...( typeof config.entry === 'function'
			? config.entry()
			: config.entry ),
		'library/index': path.resolve( process.cwd(), 'src/library/index.js' ),
		'extensions/index': path.resolve( process.cwd(), 'src/extensions/index.js' ),
		'extensions/view': path.resolve( process.cwd(), 'src/extensions/view.js' ),
	},
} );

module.exports = Array.isArray( defaultConfig )
	? defaultConfig.map( ( config, index ) =>
			index === 0 ? addExtraEntries( config ) : config
	  )
	: addExtraEntries( defaultConfig );
