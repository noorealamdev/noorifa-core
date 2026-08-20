<?php
/**
 * Universal visibility/animation block extensions.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Applies shared cross-cutting behaviour to every Noorifa Core block's
 * rendered output: visibility/animation attributes, and fluid sizing for
 * large padding/font-size values.
 *
 * Visibility/animation: static blocks (those with a save.js) already get
 * their classes and data attributes baked into the saved markup by the
 * matching `blocks.getSaveContent.extraProps` filter in
 * src/extensions/index.js, so apply_extensions() only needs to handle
 * blocks with no save output of their own.
 *
 * Fluid sizing: unlike visibility/animation, this rewrites styles already
 * present in the rendered HTML (inline styles baked in at save time for
 * static blocks, or generated at render time for dynamic ones), so
 * apply_fluid_sizing() runs for every Noorifa Core block regardless of
 * whether it is static or dynamic.
 */
class Extensions {

	use Singleton;

	/**
	 * Viewport widths, in pixels, the two ends of the fluid scale are
	 * anchored to. At MAX_VIEWPORT and above, a value resolves to exactly
	 * itself (desktop parity, unchanged from before this feature existed).
	 * At MIN_VIEWPORT and below, it resolves to FLUID_MIN_PX regardless of
	 * how large the original value is — large values (e.g. padding used to
	 * offset/position content) must not keep eating a large *proportion*
	 * of a small screen just because they scale proportionally; they need
	 * to collapse to a small, safe amount instead. MAX_VIEWPORT matches
	 * the "desktop" breakpoint already used by the Visibility feature in
	 * src/extensions/style.scss.
	 */
	const FLUID_MIN_VIEWPORT = 375;
	const FLUID_MAX_VIEWPORT = 782;

	/**
	 * The value (in px) a large padding/font-size shrinks to at
	 * FLUID_MIN_VIEWPORT and below. Values already smaller than this are
	 * left alone (see clamp_declaration()'s `min()` floor).
	 */
	const FLUID_MIN_PX = 20;

	/**
	 * Assumed pixel size of 1rem/1em, used only to convert those units to
	 * a `vw` coefficient below; the actual rendered value keeps its
	 * original unit.
	 */
	const FLUID_PX_PER_REM = 16;

	/**
	 * Hooks the render filters.
	 */
	protected function __construct() {
		add_filter( 'render_block', array( $this, 'apply_extensions' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'apply_fluid_sizing' ), 10, 2 );
	}

	/**
	 * Injects visibility classes and animation data attributes into a
	 * dynamic Noorifa Core block's outer tag.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block, including `blockName` and `attrs`.
	 * @return string
	 */
	public function apply_extensions( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'noorifa-core/' ) ) {
			return $block_content;
		}

		if ( '' === trim( $block_content ) ) {
			return $block_content;
		}

		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );

		if ( ! $block_type || ! $block_type->is_dynamic() ) {
			return $block_content;
		}

		$attrs      = isset( $block['attrs'] ) ? (array) $block['attrs'] : array();
		$visibility = isset( $attrs['noorifaCoreVisibility'] ) ? (array) $attrs['noorifaCoreVisibility'] : array();
		$animation  = isset( $attrs['noorifaCoreAnimation'] ) ? (array) $attrs['noorifaCoreAnimation'] : array();

		$classes = array();

		if ( ! empty( $visibility['hideOnMobile'] ) ) {
			$classes[] = 'noorifa-core-hide-mobile';
		}

		if ( ! empty( $visibility['hideOnTablet'] ) ) {
			$classes[] = 'noorifa-core-hide-tablet';
		}

		if ( ! empty( $visibility['hideOnDesktop'] ) ) {
			$classes[] = 'noorifa-core-hide-desktop';
		}

		$animation_type = isset( $animation['type'] ) ? $animation['type'] : 'none';

		if ( empty( $classes ) && ( empty( $animation_type ) || 'none' === $animation_type ) ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );

		if ( ! $tags->next_tag() ) {
			return $block_content;
		}

		foreach ( $classes as $class ) {
			$tags->add_class( $class );
		}

		if ( ! empty( $animation_type ) && 'none' !== $animation_type ) {
			$tags->set_attribute( 'data-noorifa-core-animation', $animation_type );
			$tags->set_attribute( 'data-noorifa-core-duration', isset( $animation['duration'] ) ? (int) $animation['duration'] : 600 );
			$tags->set_attribute( 'data-noorifa-core-delay', isset( $animation['delay'] ) ? (int) $animation['delay'] : 0 );
		}

		return $tags->get_updated_html();
	}

	/**
	 * Wraps large inline padding/font-size values in a fluid `clamp()` so
	 * whatever value is set is used as-is at FLUID_MAX_VIEWPORT and above
	 * (desktop looks exactly like before), collapses to a small, safe
	 * FLUID_MIN_PX at FLUID_MIN_VIEWPORT and below (so a large padding
	 * used to offset/position content can't crush a phone-width layout
	 * regardless of how large the desktop value is), and interpolates
	 * smoothly in between — with zero extra per-breakpoint configuration.
	 *
	 * Small values are left effectively unchanged: the generated clamp()
	 * floor is `min( FLUID_MIN_PX, <value> )`, so a value already at or
	 * below that resolves back to itself at every viewport width.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block, including `blockName`.
	 * @return string
	 */
	public function apply_fluid_sizing( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'noorifa-core/' ) ) {
			return $block_content;
		}

		if ( false !== strpos( $block_content, 'style=' ) ) {
			$block_content = (string) preg_replace_callback(
				'/(padding(?:-top|-right|-bottom|-left)?|font-size)\s*:\s*([\d.]+)(px|rem|em)\s*;?/',
				array( $this, 'clamp_declaration' ),
				$block_content
			);
		}

		if ( false !== strpos( $block_content, '-font-size' ) ) {
			$block_content = $this->apply_fluid_preset_font_size( $block_content );
		}

		return $block_content;
	}

	/**
	 * Picking a font size PRESET (the S/M/L/XL picker, backed by
	 * `typography.fontSizes` in settings — see `useSettings(
	 * 'typography.fontSizes' )` in heading/hero/icon-list's edit.js)
	 * gets a block a `has-{slug}-font-size` CLASS, not the inline
	 * `font-size` style a CUSTOM size gets — so `apply_fluid_sizing()`'s
	 * regex above, which only ever looks at inline styles, silently
	 * skipped every preset size while custom sizes already got the
	 * fluid treatment. This resolves each preset slug actually present
	 * on a Noorifa Core block's outer tag to its real pixel value (from
	 * the exact same merged settings the editor's own picker reads) and
	 * adds an inline fluid `font-size` alongside the class — inline
	 * styles outrank the class's own (non-fluid) rule by specificity,
	 * so the class itself is left in place rather than stripped.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	private function apply_fluid_preset_font_size( $block_content ) {
		$tags = new \WP_HTML_Tag_Processor( $block_content );

		while ( $tags->next_tag() ) {
			$class_name = $tags->get_attribute( 'class' );
			if ( ! is_string( $class_name ) || ! preg_match( '/\bhas-([a-z0-9-]+)-font-size\b/', $class_name, $slug_match ) ) {
				continue;
			}

			$existing_style = (string) $tags->get_attribute( 'style' );
			if ( false !== stripos( $existing_style, 'font-size' ) ) {
				continue; // Already has its own inline font-size — leave it to the regex pass above.
			}

			$px = $this->resolve_font_size_slug( $slug_match[1] );
			if ( null === $px ) {
				continue; // Not a real registered preset slug.
			}

			$declaration = $this->clamp_declaration( array( '', 'font-size', $px, 'px' ) );
			$tags->set_attribute(
				'style',
				'' !== $existing_style ? rtrim( $existing_style, ';' ) . ';' . $declaration : $declaration
			);
		}

		return $tags->get_updated_html();
	}

	/**
	 * Resolves a font-size preset slug (e.g. 'large') to its pixel
	 * value, reading the same merged theme.json settings — origins
	 * 'default' (WordPress core's own Small/Medium/Large/Extra Large),
	 * 'theme' and 'custom' — the block editor's FontSizePicker itself
	 * reads via `useSettings( 'typography.fontSizes' )`.
	 *
	 * @param string $slug Preset slug.
	 * @return float|null Pixel value, or null if not a real registered preset.
	 */
	private function resolve_font_size_slug( $slug ) {
		$settings = \WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
		$origins  = $settings['typography']['fontSizes'] ?? array();

		foreach ( $origins as $font_sizes ) {
			foreach ( (array) $font_sizes as $font_size ) {
				if ( ( $font_size['slug'] ?? '' ) !== $slug ) {
					continue;
				}

				$size = trim( (string) ( $font_size['size'] ?? '' ) );
				if ( ! preg_match( '/^([\d.]+)(px|rem|em)$/', $size, $m ) ) {
					return null; // Already fluid (e.g. contains clamp()) or otherwise not a plain value.
				}

				return 'px' === $m[2] ? (float) $m[1] : (float) $m[1] * self::FLUID_PX_PER_REM;
			}
		}

		return null;
	}

	/**
	 * Builds a fluid clamp() replacement for a single `property: value;`
	 * regex match.
	 *
	 * Interpolates linearly between ( FLUID_MIN_VIEWPORT, FLUID_MIN_PX )
	 * and ( FLUID_MAX_VIEWPORT, <original value> ), expressed as a single
	 * `calc( Apx + Bvw )` preferred term, clamped so it never goes below
	 * the smaller of the two anchors nor above the original value.
	 *
	 * @param array $matches Regex matches: [ full, property, number, unit ].
	 * @return string
	 */
	private function clamp_declaration( $matches ) {
		list( , $property, $number, $unit ) = $matches;

		$value  = $number . $unit;
		$max_px = 'px' === $unit ? (float) $number : ( (float) $number * self::FLUID_PX_PER_REM );
		$min_px = min( $max_px, self::FLUID_MIN_PX );

		if ( $max_px <= $min_px ) {
			// Already small enough; scaling it further would only inflate it.
			return sprintf( '%s: %s;', $property, $value );
		}

		$slope     = ( $max_px - $min_px ) / ( self::FLUID_MAX_VIEWPORT - self::FLUID_MIN_VIEWPORT );
		$intercept = round( $min_px - $slope * self::FLUID_MIN_VIEWPORT, 2 );
		$vw        = round( $slope * 100, 4 );

		$preferred = $intercept >= 0
			? sprintf( 'calc(%spx + %svw)', $intercept, $vw )
			: sprintf( 'calc(%svw - %spx)', $vw, abs( $intercept ) );

		return sprintf( '%s: clamp(%spx, %s, %s);', $property, round( $min_px, 2 ), $preferred, $value );
	}
}
