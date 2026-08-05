import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { uid } = attributes;

	const blockProps = useBlockProps.save( {
		id: `noorifa-core-tab-panel-${ uid }`,
		className: 'noorifa-core-tabs__panel',
		role: 'tabpanel',
		'aria-labelledby': `noorifa-core-tab-${ uid }`,
		'data-wp-context': JSON.stringify( { tabId: uid } ),
		'data-wp-bind--hidden': '!state.isSelected',
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
