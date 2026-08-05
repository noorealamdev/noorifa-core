<?php
/**
 * Sideloads template images into the site's own Media Library on import.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * A premade template's `content` may reference images that don't yet exist
 * on the destination site — either this plugin's own bundled sample assets,
 * or (for the optional Cloud source) a genuinely remote URL. Left as-is,
 * that content would keep depending on this plugin's files or a third-party
 * server indefinitely, and would break outright if either ever goes away.
 *
 * `rewrite_content()` downloads each such image into the site's real Media
 * Library exactly once (reusing the same attachment on every later import)
 * and rewrites the content to reference the new local attachment, so
 * imported content is fully self-contained from the moment it's inserted.
 */
class Media_Sideloader {

	/**
	 * Post meta key recording the original source URL an attachment was
	 * sideloaded from, so a later import reuses it instead of duplicating it.
	 */
	const SOURCE_META_KEY = '_noorifa_core_source_url';

	/**
	 * Marks a bundled-asset reference inside a template's `content`, e.g.
	 * `noorifa-core-bundled-asset:templates/library/assets/photo.jpg`. Not a
	 * real URL scheme — a portable placeholder resolved to a path relative to
	 * this plugin's own directory, so it works identically on every site
	 * regardless of domain, unlike an absolute plugins_url() reference (which
	 * would only ever match on the exact site it was authored on). Never
	 * rendered as-is: rewrite_content() always replaces it before the content
	 * reaches the editor.
	 */
	const BUNDLED_ASSET_SCHEME = 'noorifa-core-bundled-asset:';

	/**
	 * Rewrites every external image URL in a template's content to point at
	 * a real, local Media Library attachment.
	 *
	 * @param string $content Serialized block HTML.
	 * @return string
	 */
	public static function rewrite_content( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		$urls = self::find_external_urls( $content );

		if ( empty( $urls ) ) {
			return $content;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $urls as $url ) {
			$sideloaded = self::sideload( $url );

			if ( ! $sideloaded ) {
				continue;
			}

			// Attribute pairs (e.g. the Hero block's `backgroundImage:
			// {url, id}`) first, so the original site's meaningless
			// attachment id isn't left behind once the URL is replaced.
			$content = preg_replace(
				'/"url":"' . preg_quote( $url, '/' ) . '","id":\d+/',
				'"url":"' . $sideloaded['url'] . '","id":' . $sideloaded['id'],
				$content
			);

			// Core Image/Media & Text blocks: the original attachment id also
			// shows up as `"mediaId":N` in the block comment and a
			// `wp-image-N` class on the <img> itself. Find the id tied to
			// *this* <img src="...">, so both get swapped for the new
			// attachment's real id too — otherwise they'd keep pointing at
			// an id that means nothing (or belongs to something else) here.
			if ( preg_match( '/<img[^>]*src="' . preg_quote( $url, '/' ) . '"[^>]*class="[^"]*wp-image-(\d+)/', $content, $img_match ) ) {
				$old_id  = $img_match[1];
				$content = preg_replace( '/\bwp-image-' . $old_id . '\b/', 'wp-image-' . $sideloaded['id'], $content );
				$content = preg_replace( '/"mediaId":' . $old_id . '\b/', '"mediaId":' . $sideloaded['id'], $content );
			}

			$content = str_replace( $url, $sideloaded['url'], $content );
		}

		return $content;
	}

	/**
	 * Finds every image reference in the content that isn't already local to
	 * this site's own uploads directory — either a genuine remote URL, or
	 * this plugin's own `noorifa-core-bundled-asset:` placeholder.
	 *
	 * @param string $content Serialized block HTML.
	 * @return string[]
	 */
	private static function find_external_urls( $content ) {
		preg_match_all(
			'/(?:https?:\/\/[^"\'\s)]+|' . preg_quote( self::BUNDLED_ASSET_SCHEME, '/' ) . '[^"\'\s)]+)\.(?:jpe?g|png|gif|webp|svg)/i',
			$content,
			$matches
		);

		$upload_base_url = wp_get_upload_dir()['baseurl'];

		return array_values(
			array_unique(
				array_filter(
					$matches[0],
					static function ( $url ) use ( $upload_base_url ) {
						return 0 !== strpos( $url, $upload_base_url );
					}
				)
			)
		);
	}

	/**
	 * Returns the attachment for a source URL, sideloading it if needed.
	 *
	 * @param string $url Source image URL.
	 * @return array{id:int,url:string}|null
	 */
	private static function sideload( $url ) {
		$existing = self::find_existing_attachment( $url );

		if ( $existing ) {
			return $existing;
		}

		$local_path = self::local_file_for_url( $url );

		$upload = $local_path
			? self::upload_bits_from_path( $local_path )
			: self::upload_bits_from_remote( $url );

		return self::finish_attachment( $url, $upload );
	}

	/**
	 * Resolves a bundled-asset reference (this plugin's own files) to its
	 * filesystem path, so it can be read directly instead of requesting it
	 * over HTTP — which, for a placeholder like `noorifa-core-bundled-asset:`,
	 * has no real host to even request from in the first place.
	 *
	 * @param string $url Source image reference.
	 * @return string|null
	 */
	private static function local_file_for_url( $url ) {
		if ( 0 === strpos( $url, self::BUNDLED_ASSET_SCHEME ) ) {
			$relative = substr( $url, strlen( self::BUNDLED_ASSET_SCHEME ) );
		} elseif ( 0 === strpos( $url, NOORIFA_CORE_URL ) ) {
			// Back-compat: a template built against this exact site's own
			// plugins_url() rather than the portable placeholder above.
			$relative = substr( $url, strlen( NOORIFA_CORE_URL ) );
		} else {
			return null;
		}

		$path = NOORIFA_CORE_DIR . $relative;

		return is_readable( $path ) ? $path : null;
	}

	/**
	 * Reads a local file's bytes into the uploads directory.
	 *
	 * @param string $path Absolute filesystem path.
	 * @return array
	 */
	private static function upload_bits_from_path( $path ) {
		$filename = wp_unique_filename( wp_upload_dir()['path'], wp_basename( $path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading this plugin's own bundled local asset, not a remote URL.
		return wp_upload_bits( $filename, null, file_get_contents( $path ) );
	}

	/**
	 * Downloads a genuinely remote file's bytes into the uploads directory.
	 *
	 * @param string $url Remote image URL.
	 * @return array
	 */
	private static function upload_bits_from_remote( $url ) {
		$tmp = download_url( $url, 15 );

		if ( is_wp_error( $tmp ) ) {
			return array( 'error' => $tmp->get_error_message() );
		}

		$filename = wp_unique_filename( wp_upload_dir()['path'], wp_basename( wp_parse_url( $url, PHP_URL_PATH ) ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading our own just-downloaded temp file, not a remote URL.
		$bits = file_get_contents( $tmp );

		wp_delete_file( $tmp );

		return wp_upload_bits( $filename, null, $bits );
	}

	/**
	 * Finishes creating the attachment post from uploaded bits.
	 *
	 * @param string $source_url Original URL, recorded for de-duplication.
	 * @param array  $upload     Result of wp_upload_bits().
	 * @return array{id:int,url:string}|null
	 */
	private static function finish_attachment( $source_url, $upload ) {
		if ( empty( $upload['file'] ) || ! empty( $upload['error'] ) ) {
			return null;
		}

		$filetype      = wp_check_filetype( $upload['file'] );
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_file_name( pathinfo( $upload['file'], PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			return null;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		update_post_meta( $attachment_id, self::SOURCE_META_KEY, $source_url );

		return array(
			'id'  => $attachment_id,
			'url' => wp_get_attachment_url( $attachment_id ),
		);
	}

	/**
	 * Finds an attachment already sideloaded from this exact source URL, so
	 * re-applying a template (or a second product using the same one)
	 * reuses the same Media Library item instead of duplicating it.
	 *
	 * @param string $url Source image URL.
	 * @return array{id:int,url:string}|null
	 */
	private static function find_existing_attachment( $url ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one-time lookup keyed on a value only this plugin ever writes.
					array(
						'key'   => self::SOURCE_META_KEY,
						'value' => $url,
					),
				),
			)
		);

		if ( empty( $existing ) ) {
			return null;
		}

		return array(
			'id'  => $existing[0],
			'url' => wp_get_attachment_url( $existing[0] ),
		);
	}
}
