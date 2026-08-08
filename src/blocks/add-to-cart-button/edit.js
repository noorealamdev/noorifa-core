import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	BlockControls,
	AlignmentControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ProductSelect from '../../utils/product-select';

export default function Edit( { attributes, setAttributes } ) {
	const { productId, quantity, action, text, textAlign, fullWidth } =
		attributes;

	// Supports (color, typography, spacing, border, shadow) live on the link
	// itself — the visible button box — exactly like the Advanced Button.
	const blockProps = useBlockProps( {
		className: 'noorifa-core-add-to-cart-button__link',
	} );

	const defaultText =
		'buy' === action
			? __( 'Buy now', 'noorifa-core' )
			: __( 'Add to cart', 'noorifa-core' );

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
				<PanelBody title={ __( 'Product', 'noorifa-core' ) }>
					<ProductSelect
						value={ productId }
						onChange={ ( id ) =>
							setAttributes( { productId: id } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						type="number"
						min="1"
						label={ __( 'Quantity', 'noorifa-core' ) }
						value={ quantity }
						onChange={ ( value ) =>
							setAttributes( {
								quantity: Math.max(
									1,
									parseInt( value, 10 ) || 1
								),
							} )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'On click', 'noorifa-core' ) }
						value={ action }
						options={ [
							{
								label: __( 'Add to cart', 'noorifa-core' ),
								value: 'add',
							},
							{
								label: __(
									'Buy now (go to checkout)',
									'noorifa-core'
								),
								value: 'buy',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { action: value } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
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
					'wp-block-noorifa-core-add-to-cart-button__wrap',
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
					placeholder={ defaultText }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
				/>
			</div>
		</>
	);
}
