import { useBlockProps, RichText } from '@wordpress/block-editor';
import { getSubheadingStyle } from './shared';

export default function save( { attributes } ) {
	const {
		content,
		level,
		textAlign,
		lineHeight,
		showSubheading,
		subheading,
		subheadingPosition,
	} = attributes;
	const TagName = `h${ level }`;

	const blockProps = useBlockProps.save( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
		style: lineHeight ? { lineHeight } : undefined,
	} );

	// Off state renders exactly the historical markup (block wrapper IS the
	// heading tag), so headings saved before the subheading feature stay
	// byte-identical and don't trip block validation.
	if ( ! showSubheading ) {
		return (
			<TagName { ...blockProps }>
				<RichText.Content value={ content } />
			</TagName>
		);
	}

	const subheadingEl = (
		<RichText.Content
			tagName="p"
			className="noorifa-core-heading__subheading"
			style={ getSubheadingStyle( attributes ) }
			value={ subheading }
		/>
	);

	return (
		<div { ...blockProps }>
			{ 'above' === subheadingPosition && subheadingEl }
			<TagName>
				<RichText.Content value={ content } />
			</TagName>
			{ 'below' === subheadingPosition && subheadingEl }
		</div>
	);
}
