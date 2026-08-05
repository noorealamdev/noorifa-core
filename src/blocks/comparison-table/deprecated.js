import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';

/**
 * Pre-"boxed width" shape: the table wrapper had no `is-boxed` class or
 * inline max-width, since the `boxed`/`boxedWidth` attributes didn't exist
 * yet. Includes every attribute block supports injects at registration
 * (anchor, className, style, backgroundColor, textColor, gradient,
 * fontSize) explicitly — a deprecation does not automatically inherit
 * those the way the main block registration does.
 */
const v1 = {
	attributes: {
		title: {
			type: 'string',
			source: 'html',
			selector: 'h2',
			default: 'Compare Options',
		},
		style: {
			type: 'object',
			default: {
				spacing: {
					padding: {
						top: '3rem',
						right: '1.5rem',
						bottom: '3rem',
						left: '1.5rem',
					},
				},
			},
		},
		anchor: { type: 'string' },
		className: { type: 'string' },
		backgroundColor: { type: 'string' },
		textColor: { type: 'string' },
		gradient: { type: 'string' },
		fontSize: { type: 'string' },
	},
	save( { attributes } ) {
		const { title } = attributes;
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<RichText.Content
					tagName="h2"
					className="noorifa-core-comparison-table__title"
					value={ title }
				/>
				<div className="noorifa-core-comparison-table__table">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
};

export default [ v1 ];
