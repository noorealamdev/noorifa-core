import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { text, url, opensInNewTab, textAlign } = attributes;
	const blockProps = useBlockProps.save( {
		className: 'noorifa-core-button__link',
	} );

	return (
		<div
			className={
				textAlign
					? `wp-block-noorifa-core-button has-text-align-${ textAlign }`
					: 'wp-block-noorifa-core-button'
			}
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
