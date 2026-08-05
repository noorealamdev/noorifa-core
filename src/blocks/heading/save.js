import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { content, level, textAlign } = attributes;
	const TagName = `h${ level }`;

	const blockProps = useBlockProps.save( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
	} );

	return (
		<TagName { ...blockProps }>
			<RichText.Content value={ content } />
		</TagName>
	);
}
