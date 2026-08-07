import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { getAccordionStyle } from './shared';

export default function save( { attributes } ) {
	const { allowMultiple, boxed } = attributes;

	const blockProps = useBlockProps.save( {
		'data-wp-interactive': 'noorifa-core/accordion',
		'data-wp-context': JSON.stringify( {
			open: { ids: [] },
			allowMultiple,
		} ),
		className: boxed ? 'is-boxed' : undefined,
		style: getAccordionStyle( attributes ),
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
