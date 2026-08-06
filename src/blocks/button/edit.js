import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	BlockControls,
	AlignmentControl,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { text, url, opensInNewTab, textAlign, fullWidth } = attributes;

	// The link itself carries every style support (color, typography,
	// spacing, border, shadow) since it's the actual visible button box;
	// the wrapper below only ever handles the button's own alignment.
	const blockProps = useBlockProps( {
		className: 'noorifa-core-button__link',
	} );

	return (
		<>
			<BlockControls>
				<AlignmentControl
					value={ textAlign }
					onChange={ ( value ) =>
						setAttributes( { textAlign: value } )
					}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Link Settings', 'noorifa-core' ) }>
					<TextControl
						label={ __( 'URL', 'noorifa-core' ) }
						value={ url }
						onChange={ ( value ) =>
							setAttributes( { url: value } )
						}
						placeholder="https://"
					/>
					<ToggleControl
						label={ __( 'Open in new tab', 'noorifa-core' ) }
						checked={ opensInNewTab }
						onChange={ ( value ) =>
							setAttributes( { opensInNewTab: value } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<ToggleControl
						label={ __( 'Full width', 'noorifa-core' ) }
						checked={ fullWidth }
						onChange={ ( value ) =>
							setAttributes( { fullWidth: value } )
						}
						help={
							fullWidth
								? __(
										'The button stretches to fill its container.',
										'noorifa-core'
								  )
								: __(
										'The button sizes to fit its text (default).',
										'noorifa-core'
								  )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div
				className={ [
					'wp-block-noorifa-core-button',
					textAlign ? `has-text-align-${ textAlign }` : '',
					fullWidth ? 'is-full-width' : '',
				]
					.filter( Boolean )
					.join( ' ' ) }
			>
				<RichText
					tagName="a"
					{ ...blockProps }
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					placeholder={ __( 'Add text…', 'noorifa-core' ) }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
				/>
			</div>
		</>
	);
}
