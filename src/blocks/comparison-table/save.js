import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { title, boxed, boxedWidth } = attributes;
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<RichText.Content
				tagName="h2"
				className="noorifa-core-comparison-table__title"
				value={ title }
			/>
			<div
				className={
					'noorifa-core-comparison-table__table' +
					( boxed ? ' is-boxed' : '' )
				}
				style={ boxed ? { maxWidth: boxedWidth } : undefined }
			>
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
