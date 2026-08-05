import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit( { attributes, setAttributes } ) {
	const { badgeText } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Badge', 'noorifa-core' ) }>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Badge text', 'noorifa-core' ) }
						help={ __(
							'Optional short text shown next to the price — e.g. "Free shipping today". Leave empty to hide it.',
							'noorifa-core'
						) }
						value={ badgeText }
						onChange={ ( value ) =>
							setAttributes( { badgeText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<WooPlaceholder
					icon="cart"
					label={ __( 'Sticky Add to Cart Bar', 'noorifa-core' ) }
					instructions={ __(
						'Shows a bar fixed to the bottom of the screen once the visitor scrolls past the main Add to Cart block. Preview appears on the product page.',
						'noorifa-core'
					) }
				/>
			</div>
		</>
	);
}
