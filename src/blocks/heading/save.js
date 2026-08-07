import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { content, level, textAlign, lineHeight } = attributes;
	const TagName = `h${ level }`;

	const blockProps = useBlockProps.save( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
		style: lineHeight ? { lineHeight } : undefined,
	} );

	return (
		<TagName { ...blockProps }>
			<RichText.Content value={ content } />
		</TagName>
	);
}
