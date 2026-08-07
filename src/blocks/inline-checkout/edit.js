import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	// No stable/public equivalent — the same native Color control WP core's
	// own inspector panels use throughout this plugin.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientControl as ColorGradientControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
	BaseControl,
	Disabled,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { plus, closeSmall } from '@wordpress/icons';
import ServerSideRender from '@wordpress/server-side-render';
import ProductSelect from '../../utils/product-select';

export default function Edit( { attributes, setAttributes } ) {
	const {
		formHeading,
		packagesHeading,
		packages,
		billingHeading,
		nameLabel,
		addressLabel,
		phoneLabel,
		phoneHelp,
		orderHeading,
		shippingLabel,
		paymentTitle,
		paymentDescription,
		privacyText,
		buttonText,
		accentColor,
	} = attributes;

	const accentControlId = useInstanceId(
		Edit,
		'noorifa-core-inline-checkout-accent'
	);

	const updatePackage = ( index, field, value ) => {
		const next = packages.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { packages: next } );
	};

	const addPackage = () =>
		setAttributes( {
			packages: [
				...packages,
				{
					productId: 0,
					label: '',
					description: '',
					badge: '',
					showQty: true,
				},
			],
		} );

	const removePackage = ( index ) =>
		setAttributes( {
			packages: packages.filter( ( _item, i ) => i !== index ),
		} );

	const movePackage = ( index, direction ) => {
		const target = index + direction;
		if ( target < 0 || target >= packages.length ) {
			return;
		}
		const next = packages.slice();
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { packages: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Packages', 'noorifa-core' ) }>
					{ packages.map( ( pkg, index ) => (
						<div
							key={ index }
							className="noorifa-core-inline-checkout-repeater__item"
						>
							<div className="noorifa-core-inline-checkout-repeater__head">
								<strong>
									{ __( 'Package', 'noorifa-core' ) }{ ' ' }
									{ index + 1 }
								</strong>
								<div className="noorifa-core-inline-checkout-repeater__actions">
									<Button
										size="small"
										disabled={ index === 0 }
										onClick={ () =>
											movePackage( index, -1 )
										}
										label={ __(
											'Move up',
											'noorifa-core'
										) }
									>
										↑
									</Button>
									<Button
										size="small"
										disabled={
											index === packages.length - 1
										}
										onClick={ () =>
											movePackage( index, 1 )
										}
										label={ __(
											'Move down',
											'noorifa-core'
										) }
									>
										↓
									</Button>
									<Button
										size="small"
										isDestructive
										icon={ closeSmall }
										onClick={ () => removePackage( index ) }
										label={ __( 'Remove', 'noorifa-core' ) }
									/>
								</div>
							</div>
							<ProductSelect
								value={ pkg.productId }
								onChange={ ( id ) =>
									updatePackage( index, 'productId', id )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Title (optional override)',
									'noorifa-core'
								) }
								help={ __(
									'Leave empty to use the product name.',
									'noorifa-core'
								) }
								value={ pkg.label || '' }
								onChange={ ( value ) =>
									updatePackage( index, 'label', value )
								}
							/>
							<TextareaControl
								__nextHasNoMarginBottom
								label={ __( 'Description', 'noorifa-core' ) }
								value={ pkg.description || '' }
								onChange={ ( value ) =>
									updatePackage( index, 'description', value )
								}
							/>
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Badge (e.g. Best Deal)',
									'noorifa-core'
								) }
								value={ pkg.badge || '' }
								onChange={ ( value ) =>
									updatePackage( index, 'badge', value )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Quantity stepper',
									'noorifa-core'
								) }
								checked={ pkg.showQty !== false }
								onChange={ ( value ) =>
									updatePackage( index, 'showQty', value )
								}
							/>
						</div>
					) ) }
					<Button
						variant="secondary"
						icon={ plus }
						onClick={ addPackage }
					>
						{ __( 'Add package', 'noorifa-core' ) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __( 'Text & labels', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Form heading', 'noorifa-core' ) }
						value={ formHeading }
						onChange={ ( value ) =>
							setAttributes( { formHeading: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Packages heading', 'noorifa-core' ) }
						value={ packagesHeading }
						onChange={ ( value ) =>
							setAttributes( { packagesHeading: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Billing heading', 'noorifa-core' ) }
						value={ billingHeading }
						onChange={ ( value ) =>
							setAttributes( { billingHeading: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Name field label', 'noorifa-core' ) }
						value={ nameLabel }
						onChange={ ( value ) =>
							setAttributes( { nameLabel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Address field label', 'noorifa-core' ) }
						value={ addressLabel }
						onChange={ ( value ) =>
							setAttributes( { addressLabel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Phone field label', 'noorifa-core' ) }
						value={ phoneLabel }
						onChange={ ( value ) =>
							setAttributes( { phoneLabel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Phone field hint', 'noorifa-core' ) }
						value={ phoneHelp }
						onChange={ ( value ) =>
							setAttributes( { phoneHelp: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Order heading', 'noorifa-core' ) }
						value={ orderHeading }
						onChange={ ( value ) =>
							setAttributes( { orderHeading: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Shipping label', 'noorifa-core' ) }
						value={ shippingLabel }
						onChange={ ( value ) =>
							setAttributes( { shippingLabel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Payment title', 'noorifa-core' ) }
						value={ paymentTitle }
						onChange={ ( value ) =>
							setAttributes( { paymentTitle: value } )
						}
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Payment description', 'noorifa-core' ) }
						value={ paymentDescription }
						onChange={ ( value ) =>
							setAttributes( { paymentDescription: value } )
						}
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Privacy note', 'noorifa-core' ) }
						value={ privacyText }
						onChange={ ( value ) =>
							setAttributes( { privacyText: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Button text', 'noorifa-core' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Style', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<BaseControl
						__nextHasNoMarginBottom
						id={ accentControlId }
						label={ __( 'Accent color', 'noorifa-core' ) }
					>
						<ColorGradientControl
							id={ accentControlId }
							colorValue={ accentColor }
							onColorChange={ ( value ) =>
								setAttributes( {
									accentColor: value || '#3a9e3a',
								} )
							}
							enableAlpha={ false }
							__experimentalIsRenderedInSidebar
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="noorifa-core/inline-checkout"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
