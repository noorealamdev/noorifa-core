import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Pre-"boxed width" shape: the root element had no `is-boxed` class or
 * inline max-width, since the `boxed`/`boxedWidth` attributes didn't exist
 * yet. Includes every attribute block supports injects at registration
 * (anchor, className, style, backgroundColor, textColor) explicitly — a
 * deprecation does not automatically inherit those the way the main block
 * registration does, so leaving any out silently breaks parsing of
 * already-saved content that used them.
 */
const v1 = {
	attributes: {
		allowMultiple: {
			type: 'boolean',
			default: false,
		},
		anchor: { type: 'string' },
		className: { type: 'string' },
		style: { type: 'object' },
		backgroundColor: { type: 'string' },
		textColor: { type: 'string' },
	},
	save( { attributes } ) {
		const { allowMultiple } = attributes;

		const blockProps = useBlockProps.save( {
			'data-wp-interactive': 'noorifa-core/accordion',
			'data-wp-context': JSON.stringify( {
				open: { ids: [] },
				allowMultiple,
			} ),
		} );

		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
};

export default [ v1 ];
