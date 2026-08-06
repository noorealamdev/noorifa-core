import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	useSettings,
	__experimentalColorGradientControl as ColorGradientControl,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	BaseControl,
	ToggleControl,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/*
 * Defaults for the dark-card reference look — shared here (editor preview)
 * and in render.php (front-end), so the two stay in sync. Applied as
 * conditional inline styles rather than a CSS class default: once the
 * merchant clears a value in the Inspector, there'd be no inline override
 * left to beat a class rule with, so the class default would keep showing
 * regardless of intent (same issue already fixed for Trust Badges).
 */
export const DEFAULT_CARD_BACKGROUND = '#1c1c1c';
export const DEFAULT_CARD_RADIUS = 20;
export const DEFAULT_CARD_PADDING = 28;

export default function Edit( { attributes, setAttributes } ) {
	const {
		items,
		columns,
		cardBackground,
		cardBackgroundGradient,
		cardRadius,
		cardPadding,
		boxed,
		boxedWidth,
	} = attributes;
	const [ colors = [], gradients = [] ] = useSettings(
		'color.palette',
		'color.gradients'
	);
	const blockProps = useBlockProps( {
		style: { '--noorifa-core-feature-cards-columns': columns },
	} );

	// Same pairing as render.php: the dark card default needs a paired
	// light text default, since plain inheritance has no guaranteed
	// contrast against it. Only applied when the merchant hasn't set their
	// own Text Color via the block's Color panel.
	const hasOwnTextColor =
		!! attributes.textColor || !! attributes.style?.color?.text;
	const textStyle = hasOwnTextColor ? undefined : { color: '#fff' };

	const updateItem = ( index, field, value ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { items: next } );
	};

	const addItem = () =>
		setAttributes( {
			items: [
				...items,
				{
					heading: __( 'New heading', 'noorifa-core' ),
					text: __( 'Supporting text goes here.', 'noorifa-core' ),
				},
			],
		} );

	const removeItem = ( index ) =>
		setAttributes( { items: items.filter( ( _item, i ) => i !== index ) } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Columns', 'noorifa-core' ) }
						help={ __(
							'1 column reads as wide, horizontal cards. More columns make each card narrower and taller.',
							'noorifa-core'
						) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value || 1 } )
						}
						min={ 1 }
						max={ 4 }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Boxed width', 'noorifa-core' ) }
						checked={ boxed }
						onChange={ ( value ) => setAttributes( { boxed: value } ) }
						help={
							boxed
								? __(
										'Constrained to a max width and centered.',
										'noorifa-core'
								  )
								: __(
										'Stretches the full width of its container.',
										'noorifa-core'
								  )
						}
					/>
					{ boxed && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Max width (px)', 'noorifa-core' ) }
							value={ boxedWidth }
							onChange={ ( value ) =>
								setAttributes( { boxedWidth: value } )
							}
							min={ 480 }
							max={ 1800 }
							step={ 10 }
						/>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Card', 'noorifa-core' ) }>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Background', 'noorifa-core' ) }
					>
						<ColorGradientControl
							colors={ colors }
							gradients={ gradients }
							colorValue={ cardBackground }
							gradientValue={ cardBackgroundGradient }
							onColorChange={ ( value ) =>
								setAttributes( {
									cardBackground: value || '',
									// A card shows either a solid color or a
									// gradient, never both at once — picking
									// one clears the other, same as WP
									// core's own Cover block background.
									cardBackgroundGradient: '',
								} )
							}
							onGradientChange={ ( value ) =>
								setAttributes( {
									cardBackgroundGradient: value || '',
									cardBackground: '',
								} )
							}
						/>
					</BaseControl>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Corner radius', 'noorifa-core' ) }
						value={ cardRadius }
						onChange={ ( value ) =>
							setAttributes( { cardRadius: value || 0 } )
						}
						min={ 0 }
						max={ 40 }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Padding', 'noorifa-core' ) }
						value={ cardPadding }
						onChange={ ( value ) =>
							setAttributes( { cardPadding: value || 0 } )
						}
						min={ 0 }
						max={ 60 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className={
						'noorifa-core-feature-cards__list' +
						( boxed ? ' is-boxed' : '' )
					}
					style={ boxed ? { maxWidth: boxedWidth } : undefined }
				>
					{ items.map( ( item, index ) => (
						<div
							className="noorifa-core-feature-cards__item"
							key={ index }
							style={ {
								background:
									cardBackgroundGradient ||
									cardBackground ||
									DEFAULT_CARD_BACKGROUND,
								borderRadius:
									( cardRadius || DEFAULT_CARD_RADIUS ) + 'px',
								padding:
									( cardPadding || DEFAULT_CARD_PADDING ) + 'px',
							} }
						>
							<Button
								className="noorifa-core-feature-cards__remove"
								icon={ closeSmall }
								label={ __( 'Remove card', 'noorifa-core' ) }
								onClick={ () => removeItem( index ) }
							/>
							<RichText
								tagName="div"
								className="noorifa-core-feature-cards__heading"
								style={ textStyle }
								value={ item.heading }
								onChange={ ( value ) =>
									updateItem( index, 'heading', value )
								}
								placeholder={ __( 'Heading', 'noorifa-core' ) }
							/>
							<RichText
								tagName="div"
								className="noorifa-core-feature-cards__text"
								style={ textStyle }
								value={ item.text }
								onChange={ ( value ) =>
									updateItem( index, 'text', value )
								}
								placeholder={ __( 'Supporting text', 'noorifa-core' ) }
							/>
						</div>
					) ) }
				</div>
				<Button variant="secondary" onClick={ addItem }>
					{ __( 'Add card', 'noorifa-core' ) }
				</Button>
			</div>
		</>
	);
}
