<?php
/**
 * Removes all plugin data on uninstall.
 *
 * @package Noorifa Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'noorifa_core_disabled_blocks' );
delete_option( 'noorifa_core_version' );
delete_option( 'noorifa_core_default_layout_id' );
delete_transient( 'noorifa_core_cloud_templates' );

// Remove every Product Layout post and its per-product/per-category assignments.
$noorifa_core_layouts = get_posts(
	array(
		'post_type'     => 'noorifa_core_layout',
		'post_status'   => 'any',
		'numberposts'   => -1,
		'fields'        => 'ids',
		'no_found_rows' => true,
	)
);

foreach ( $noorifa_core_layouts as $noorifa_core_layout_id ) {
	wp_delete_post( $noorifa_core_layout_id, true );
}

delete_post_meta_by_key( '_noorifa_core_layout_id' );

$noorifa_core_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( is_array( $noorifa_core_terms ) ) {
	foreach ( $noorifa_core_terms as $noorifa_core_term_id ) {
		delete_term_meta( $noorifa_core_term_id, 'noorifa_core_layout_id' );
	}
}
