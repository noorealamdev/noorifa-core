import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit( { attributes, setAttributes } ) {
	const { showCount } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Rating', 'noorifa-core' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show review count', 'noorifa-core' ) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
						help={ __(
							'Shows the “(N customer reviews)” link next to the stars.',
							'noorifa-core'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<WooPlaceholder
				icon="star-filled"
				label={ __( 'Product Rating', 'noorifa-core' ) }
				instructions={ __(
					"Displays the current product's star rating. Preview appears on the product page.",
					'noorifa-core'
				) }
			/>
		</div>
	);
}
