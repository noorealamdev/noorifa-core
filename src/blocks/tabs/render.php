<?php
/**
 * Tabs block server render.
 *
 * Builds the tab list from the inner tab blocks' attributes and wraps the
 * pre-rendered panels ($content) with the Interactivity API context.
 *
 * @package Noorifa Core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the panels).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$noorifa_core_tabs = array();

foreach ( $block->parsed_block['innerBlocks'] as $noorifa_core_inner ) {
	$noorifa_core_tabs[] = array(
		'uid'   => isset( $noorifa_core_inner['attrs']['uid'] ) ? (string) $noorifa_core_inner['attrs']['uid'] : '',
		'title' => isset( $noorifa_core_inner['attrs']['title'] ) ? (string) $noorifa_core_inner['attrs']['title'] : '',
	);
}

if ( empty( $noorifa_core_tabs ) ) {
	return;
}

$noorifa_core_context = array(
	'active' => array( 'id' => $noorifa_core_tabs[0]['uid'] ),
);

// The initial state is rendered server-side (first tab active); the
// Interactivity API directives keep it in sync after hydration.
$noorifa_core_panels = new WP_HTML_Tag_Processor( $content );
$noorifa_core_index  = 0;

while ( $noorifa_core_panels->next_tag( array( 'class_name' => 'noorifa-core-tabs__panel' ) ) ) {
	if ( 0 !== $noorifa_core_index ) {
		$noorifa_core_panels->set_attribute( 'hidden', true );
	}
	++$noorifa_core_index;
}

$noorifa_core_content = $noorifa_core_panels->get_updated_html();

// Self-contained max-width instead of relying on the theme/template to
// constrain block content. Applied to this block's own wrapper — save.js
// stores only the panel inner blocks with no wrapper of its own, so
// changing what render.php builds here carries no validation/deprecation
// risk at all.
$noorifa_core_boxed        = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width  = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
$noorifa_core_wrapper_args = array();

if ( $noorifa_core_boxed ) {
	$noorifa_core_wrapper_args['class'] = 'is-boxed';
	$noorifa_core_wrapper_args['style'] = 'max-width:' . $noorifa_core_boxed_width . 'px';
}
?>
<div
	<?php echo get_block_wrapper_attributes( $noorifa_core_wrapper_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
	data-wp-interactive="noorifa-core/tabs"
	data-wp-context="<?php echo esc_attr( (string) wp_json_encode( $noorifa_core_context ) ); ?>"
>
	<div
		class="noorifa-core-tabs__list"
		role="tablist"
		data-wp-on--keydown="actions.handleKeydown"
	>
		<?php foreach ( $noorifa_core_tabs as $noorifa_core_i => $noorifa_core_tab ) : ?>
			<button
				type="button"
				id="noorifa-core-tab-<?php echo esc_attr( $noorifa_core_tab['uid'] ); ?>"
				class="noorifa-core-tabs__tab<?php echo 0 === $noorifa_core_i ? ' is-active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo 0 === $noorifa_core_i ? 'true' : 'false'; ?>"
				tabindex="<?php echo 0 === $noorifa_core_i ? '0' : '-1'; ?>"
				aria-controls="noorifa-core-tab-panel-<?php echo esc_attr( $noorifa_core_tab['uid'] ); ?>"
				data-wp-context="<?php echo esc_attr( (string) wp_json_encode( array( 'tabId' => $noorifa_core_tab['uid'] ) ) ); ?>"
				data-wp-on--click="actions.activate"
				data-wp-bind--aria-selected="state.isSelected"
				data-wp-bind--tabindex="state.tabIndex"
				data-wp-class--is-active="state.isSelected"
			>
				<?php echo esc_html( $noorifa_core_tab['title'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<div class="noorifa-core-tabs__panels">
		<?php echo $noorifa_core_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
	</div>
</div>
