<?php
/**
 * Block registration manager.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers and registers every block in the build directory.
 */
class Manager {

	use Singleton;

	/**
	 * Option name storing the list of disabled block names.
	 */
	const DISABLED_OPTION = 'noorifa_core_disabled_blocks';

	/**
	 * Hooks block registration.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_category' ), 10, 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_inserter_styles' ) );
	}

	/**
	 * Tints every Noorifa Core block's inserter icon a deep yellow so the
	 * plugin's blocks are easy to spot among all the other blocks in the
	 * inserter / block list. The `editor-block-list-item-{name}` class WP
	 * puts on each inserter item lets one attribute selector cover them all.
	 *
	 * @return void
	 */
	public function editor_inserter_styles() {
		$css = '[class*="editor-block-list-item-noorifa-core-"] .block-editor-block-icon,'
			. '[class*="editor-block-list-item-noorifa-core-"] .block-editor-block-icon svg'
			. '{color:#c99a00;fill:currentColor}';

		wp_add_inline_style( 'wp-edit-blocks', $css );
	}

	/**
	 * Registers every block found in build/blocks, skipping disabled ones.
	 *
	 * @return void
	 */
	public function register_blocks() {
		/**
		 * Filters the disabled block names.
		 *
		 * @param string[] $disabled Block names that will not be registered.
		 */
		$disabled = (array) apply_filters(
			'noorifa-core/disabled_blocks',
			(array) get_option( self::DISABLED_OPTION, array() )
		);

		foreach ( $this->get_block_dirs() as $dir ) {
			$metadata = $this->get_block_metadata( $dir );

			if ( empty( $metadata['name'] ) || in_array( $metadata['name'], $disabled, true ) ) {
				continue;
			}

			register_block_type( $dir );
		}
	}

	/**
	 * Adds the Noorifa Core category to the top of the block inserter.
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	public function register_category( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'noorifa-core',
					'title' => __( 'Noorifa Core', 'noorifa-core' ),
					'icon'  => null,
				),
			),
			$categories
		);
	}

	/**
	 * Returns the absolute paths of every built block directory.
	 *
	 * @return string[]
	 */
	public function get_block_dirs() {
		$dirs = glob( NOORIFA_CORE_DIR . 'build/blocks/*', GLOB_ONLYDIR );

		if ( ! is_array( $dirs ) ) {
			$dirs = array();
		}

		/**
		 * Filters the block directories to register.
		 *
		 * Add-ons can append their own build directories; every directory
		 * must contain a block.json file.
		 *
		 * @param string[] $dirs Absolute block directory paths.
		 */
		$dirs = (array) apply_filters( 'noorifa-core/block_dirs', $dirs );

		return array_filter(
			$dirs,
			static function ( $dir ) {
				return file_exists( $dir . '/block.json' );
			}
		);
	}

	/**
	 * Reads and decodes a block.json file.
	 *
	 * @param string $dir Block directory.
	 * @return array
	 */
	public function get_block_metadata( $dir ) {
		$file = trailingslashit( $dir ) . 'block.json';

		if ( ! is_readable( $file ) ) {
			return array();
		}

		$metadata = wp_json_file_decode( $file, array( 'associative' => true ) );

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Returns metadata for every discovered block, keyed by block name.
	 *
	 * @return array[]
	 */
	public function get_all_blocks() {
		$blocks = array();

		foreach ( $this->get_block_dirs() as $dir ) {
			$metadata = $this->get_block_metadata( $dir );

			if ( ! empty( $metadata['name'] ) ) {
				$blocks[ $metadata['name'] ] = $metadata;
			}
		}

		return $blocks;
	}
}
