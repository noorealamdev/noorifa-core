import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	useSettings,
	// No stable/public equivalent exists for the native Color/Gradient
	// tabbed background control WP core's own Cover block uses — see the
	// usage below for the crash this API already has to work around.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientControl as ColorGradientControl,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	BaseControl,
	ToggleControl,
	// No stable/public equivalent — same segmented-control primitive WP
	// core's own inspector panels use throughout (e.g. Hero's background
	// type toggle already in this plugin).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
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
export const DEFAULT_IMAGE_WIDTH = 56;

// render.php keeps its own copy of this logic (PHP/JS can't share a
// module, same as the DEFAULT_CARD_* constants above). Centers/right-
// aligns via margin instead of relying on a parent text-align, since the
// wrap is a block-level element regardless of the card's own text
// alignment.
export function getImageAlignStyle( align ) {
	if ( 'center' === align ) {
		return { marginLeft: 'auto', marginRight: 'auto' };
	}
	if ( 'right' === align ) {
		return { marginLeft: 'auto', marginRight: 0 };
	}
	return { marginLeft: 0, marginRight: 'auto' };
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		items,
		columns,
		cardBackground,
		cardBackgroundGradient,
		cardRadius,
		cardPadding,
		imageWidth,
		imageAlign,
		boxed,
		boxedWidth,
	} = attributes;
	const backgroundControlId = useInstanceId(
		Edit,
		'noorifa-core-feature-cards-background'
	);
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

	// A single combined update (not two updateItem() calls back to back) —
	// both fields have to land in the same setAttributes() call, since two
	// separate calls would each read the same not-yet-updated `items` and
	// the second would silently discard whatever the first just set (the
	// exact bug already found and fixed for the gradient/color controls
	// above).
	const updateItemImage = ( index, media ) => {
		const next = items.slice();
		next[ index ] = {
			...next[ index ],
			imageId: media.id,
			imageUrl: media.url,
		};
		setAttributes( { items: next } );
	};

	const removeItemImage = ( index ) => {
		const next = items.slice();
		next[ index ] = {
			...next[ index ],
			imageId: undefined,
			imageUrl: undefined,
		};
		setAttributes( { items: next } );
	};

	const addItem = () =>
		setAttributes( {
			items: [
				...items,
				{
					heading: __( 'New heading', 'noorifa-core' ),
					text: '',
				},
			],
		} );

	const removeItem = ( index ) =>
		setAttributes( { items: items.filter( ( _item, i ) => i !== index ) } );

	const resolvedImageSize = ( imageWidth || DEFAULT_IMAGE_WIDTH ) + 'px';
	const imageAlignStyle = getImageAlignStyle( imageAlign || 'left' );

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
						onChange={ ( value ) =>
							setAttributes( { boxed: value } )
						}
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
						id={ backgroundControlId }
						label={ __( 'Background', 'noorifa-core' ) }
					>
						<ColorGradientControl
							colors={ colors }
							gradients={ gradients }
							// WP core's own gradient-bar control crashes
							// (`Cannot read properties of undefined
							// (reading 'orientation')`) if it's ever handed
							// an empty string instead of a real gradient or
							// undefined — it tries to parse the value even
							// on the very first render of the Gradient tab.
							colorValue={ cardBackground || undefined }
							gradientValue={
								cardBackgroundGradient || undefined
							}
							// A card shows either a solid color or a gradient,
							// never both — same as WP core's own Cover block
							// background. But the control calls BOTH
							// callbacks on every interaction (e.g. picking a
							// gradient fires onGradientChange with the real
							// value AND THEN onColorChange with undefined,
							// as its own "color is now unset" signal) — only
							// treat a truthy value as a real pick that
							// should clear the other side; an undefined/''
							// call only clears its own attribute.
							onColorChange={ ( value ) =>
								setAttributes(
									value
										? {
												cardBackground: value,
												cardBackgroundGradient: '',
										  }
										: { cardBackground: '' }
								)
							}
							onGradientChange={ ( value ) =>
								setAttributes(
									value
										? {
												cardBackgroundGradient: value,
												cardBackground: '',
										  }
										: { cardBackgroundGradient: '' }
								)
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
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Image width', 'noorifa-core' ) }
						help={ __(
							'Applies to every card that has an image. The image is always a circle, so height follows automatically.',
							'noorifa-core'
						) }
						value={ imageWidth || DEFAULT_IMAGE_WIDTH }
						onChange={ ( value ) =>
							setAttributes( { imageWidth: value || 0 } )
						}
						min={ 24 }
						max={ 160 }
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Image alignment', 'noorifa-core' ) }
						value={ imageAlign || 'left' }
						onChange={ ( value ) =>
							setAttributes( { imageAlign: value || 'left' } )
						}
					>
						<ToggleGroupControlOption
							value="left"
							label={ __( 'Left', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="center"
							label={ __( 'Center', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="right"
							label={ __( 'Right', 'noorifa-core' ) }
						/>
					</ToggleGroupControl>
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
									( cardRadius || DEFAULT_CARD_RADIUS ) +
									'px',
								padding:
									( cardPadding || DEFAULT_CARD_PADDING ) +
									'px',
							} }
						>
							<Button
								className="noorifa-core-feature-cards__remove"
								icon={ closeSmall }
								label={ __( 'Remove card', 'noorifa-core' ) }
								onClick={ () => removeItem( index ) }
							/>

							<div
								className="noorifa-core-feature-cards__image-wrap"
								style={ imageAlignStyle }
							>
								{ item.imageUrl ? (
									<>
										<img
											src={ item.imageUrl }
											alt=""
											className="noorifa-core-feature-cards__image"
											style={ {
												width: resolvedImageSize,
												height: resolvedImageSize,
											} }
										/>
										<MediaUploadCheck>
											<MediaUpload
												onSelect={ ( media ) =>
													updateItemImage(
														index,
														media
													)
												}
												allowedTypes={ [ 'image' ] }
												value={ item.imageId }
												render={ ( { open } ) => (
													<Button
														className="noorifa-core-feature-cards__image-replace"
														onClick={ open }
														variant="secondary"
														size="small"
														style={ {
															width: resolvedImageSize,
															height: resolvedImageSize,
														} }
													>
														{ __(
															'Replace',
															'noorifa-core'
														) }
													</Button>
												) }
											/>
										</MediaUploadCheck>
										<Button
											className="noorifa-core-feature-cards__image-remove"
											icon={ closeSmall }
											label={ __(
												'Remove image',
												'noorifa-core'
											) }
											onClick={ () =>
												removeItemImage( index )
											}
										/>
									</>
								) : (
									<MediaUploadCheck>
										<MediaUpload
											onSelect={ ( media ) =>
												updateItemImage( index, media )
											}
											allowedTypes={ [ 'image' ] }
											render={ ( { open } ) => (
												<Button
													className="noorifa-core-feature-cards__image-placeholder"
													onClick={ open }
													variant="tertiary"
													style={ {
														width: resolvedImageSize,
														height: resolvedImageSize,
													} }
												>
													{ __(
														'Add image',
														'noorifa-core'
													) }
												</Button>
											) }
										/>
									</MediaUploadCheck>
								) }
							</div>

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
								placeholder={ __(
									'Supporting text',
									'noorifa-core'
								) }
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
