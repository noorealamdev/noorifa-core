import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { text, url, opensInNewTab, textAlign, fullWidth } = attributes;
	const blockProps = useBlockProps.save( {
		className: 'noorifa-core-button__link',
	} );

	return (
		<div
			className={ [
				'wp-block-noorifa-core-button',
				textAlign ? `has-text-align-${ textAlign }` : '',
				fullWidth ? 'is-full-width' : '',
			]
				.filter( Boolean )
				.join( ' ' ) }
		>
			<RichText.Content
				tagName="a"
				{ ...blockProps }
				value={ text }
				href={ url || undefined }
				target={ opensInNewTab ? '_blank' : undefined }
				rel={ opensInNewTab ? 'noopener noreferrer' : undefined }
			/>
		</div>
	);
}
