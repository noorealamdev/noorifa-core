import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	ColorPalette,
	useSettings,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
	BaseControl,
	// No stable/public equivalent — same segmented-control primitive WP
	// core's own inspector panels use throughout (also used by this plugin's
	// Hero, Feature Cards and Video Testimonials blocks).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { closeSmall } from '@wordpress/icons';

export default function Edit( { attributes, setAttributes } ) {
	const {
		items,
		columns,
		minHeight,
		verticalAlign,
		textAlign,
		boxed,
		boxedWidth,
		borderRadius,
		subheadingFontSize,
	} = attributes;
	const [ colors = [] ] = useSettings( 'color.palette' );
	const colorInstanceId = useInstanceId( Edit, 'noorifa-image-card-color' );
	const overlayColorInstanceId = useInstanceId(
		Edit,
		'noorifa-image-card-overlay-color'
	);

	const blockProps = useBlockProps( {
		className: 'noorifa-core-image-card',
		style: {
			'--noorifa-image-card-cols': columns,
			'--noorifa-image-card-min-height': `${ minHeight }px`,
			'--noorifa-image-card-radius': `${ borderRadius || 0 }px`,
			...( subheadingFontSize
				? { '--noorifa-image-card-sub-size': `${ subheadingFontSize }em` }
				: {} ),
		},
	} );

	const updateItem = ( index, field, value ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { items: next } );
	};

	// Both fields in one setAttributes() call so the second doesn't overwrite
	// the first from a stale `items` (same pattern as the other repeaters).
	const updateItemFields = ( index, fields ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], ...fields };
		setAttributes( { items: next } );
	};

	const addItem = () =>
		setAttributes( {
			items: [
				...items,
				{
					imageId: 0,
					imageUrl: '',
					heading: __( 'New heading', 'noorifa-core' ),
					subheading: '',
					linkText: __( 'Shop Now', 'noorifa-core' ),
					linkUrl: '#',
					textColor: '',
					overlay: 0,
					overlayColor: '#000000',
				},
			],
		} );

	const removeItem = ( index ) =>
		setAttributes( {
			items: items.filter( ( _item, i ) => i !== index ),
		} );

	return (
		<>
			{ /* Targets the native "Border" slot directly (same reasoning
			     as Trust Badges' icon-size control targeting "typography")
			     so this lands inside WordPress's own Border panel instead
			     of a separate custom panel stuck at the bottom of Styles. */ }
			<InspectorControls group="border">
				<NumberControl
					__next40pxDefaultSize
					label={ __( 'Corner radius (px)', 'noorifa-core' ) }
					value={ borderRadius }
					min={ 0 }
					max={ 64 }
					step={ 1 }
					onChange={ ( value ) =>
						setAttributes( { borderRadius: value || 0 } )
					}
				/>
			</InspectorControls>
			{ /* A bare control targeting group="typography" only renders
			     once a native item (Size, Line height, …) is already
			     active — the whole native panel collapses to a "+" until
			     then, hiding any plain children along with it. Wrapping
			     in ToolsPanelItem doesn't fix this either: Slot/Fill
			     content isn't a real descendant of that panel's own
			     context provider, so it can't register as an item. A
			     small dedicated panel via group="styles" (distinct title
			     from "Typography", so it doesn't repeat Icon List's
			     duplicate-panel issue) stays reliably visible instead. */ }
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Subheading', 'noorifa-core' ) }>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Font size (em)', 'noorifa-core' ) }
						help={ __(
							'Independent from the heading — 1.05 is the default.',
							'noorifa-core'
						) }
						value={ subheadingFontSize }
						min={ 0.5 }
						max={ 3 }
						step={ 0.05 }
						onChange={ ( value ) =>
							setAttributes( {
								subheadingFontSize: value || '',
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Columns', 'noorifa-core' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value || 1 } )
						}
						min={ 1 }
						max={ 4 }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Card height (px)', 'noorifa-core' ) }
						value={ minHeight }
						onChange={ ( value ) =>
							setAttributes( { minHeight: value || 320 } )
						}
						min={ 200 }
						max={ 800 }
						step={ 10 }
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Content position', 'noorifa-core' ) }
						value={ verticalAlign }
						onChange={ ( value ) =>
							setAttributes( {
								verticalAlign: value || 'center',
							} )
						}
					>
						<ToggleGroupControlOption
							value="top"
							label={ __( 'Top', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="center"
							label={ __( 'Center', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="bottom"
							label={ __( 'Bottom', 'noorifa-core' ) }
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Text alignment', 'noorifa-core' ) }
						value={ textAlign }
						onChange={ ( value ) =>
							setAttributes( { textAlign: value || 'left' } )
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
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Boxed width', 'noorifa-core' ) }
						checked={ boxed }
						onChange={ ( value ) =>
							setAttributes( { boxed: value } )
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

				{ items.map( ( item, index ) => (
					<PanelBody
						key={ index }
						title={ sprintf(
							/* translators: %d: card number. */
							__( 'Card %d', 'noorifa-core' ),
							index + 1
						) }
						initialOpen={ false }
					>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) =>
									updateItemFields( index, {
										imageId: media.id,
										imageUrl: media.url,
									} )
								}
								allowedTypes={ [ 'image' ] }
								value={ item.imageId }
								render={ ( { open } ) => (
									<Button
										variant="secondary"
										onClick={ open }
										style={ { marginBottom: '16px' } }
									>
										{ item.imageUrl
											? __(
													'Replace image',
													'noorifa-core'
											  )
											: __(
													'Select image',
													'noorifa-core'
											  ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Image overlay', 'noorifa-core' ) }
							value={ item.overlay || 0 }
							onChange={ ( value ) =>
								updateItem( index, 'overlay', value || 0 )
							}
							min={ 0 }
							max={ 100 }
						/>
						<BaseControl
							__nextHasNoMarginBottom
							id={ `${ overlayColorInstanceId }-${ index }` }
							label={ __( 'Overlay color', 'noorifa-core' ) }
							help={ __(
								'Pick a light overlay for dark text, or a dark overlay for light text — the two need to contrast.',
								'noorifa-core'
							) }
						>
							<ColorPalette
								colors={ colors }
								value={ item.overlayColor || '#000000' }
								onChange={ ( value ) =>
									updateItem(
										index,
										'overlayColor',
										value || '#000000'
									)
								}
							/>
						</BaseControl>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Link URL', 'noorifa-core' ) }
							value={ item.linkUrl }
							onChange={ ( value ) =>
								updateItem( index, 'linkUrl', value )
							}
							placeholder="https://"
						/>
						<BaseControl
							__nextHasNoMarginBottom
							id={ `${ colorInstanceId }-${ index }` }
							label={ __( 'Text color', 'noorifa-core' ) }
						>
							<ColorPalette
								colors={ colors }
								value={ item.textColor }
								onChange={ ( value ) =>
									updateItem(
										index,
										'textColor',
										value || ''
									)
								}
							/>
						</BaseControl>
					</PanelBody>
				) ) }
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className={
						'noorifa-core-image-card__grid' +
						( boxed ? ' is-boxed' : '' )
					}
					style={ boxed ? { maxWidth: boxedWidth } : undefined }
				>
					{ items.map( ( item, index ) => (
						<div
							className={
								'noorifa-core-image-card__card is-valign-' +
								verticalAlign +
								' is-align-' +
								textAlign +
								( item.imageUrl ? '' : ' has-no-image' )
							}
							key={ index }
							style={
								item.imageUrl
									? {
											backgroundImage: `url(${ item.imageUrl })`,
									  }
									: undefined
							}
						>
							<Button
								className="noorifa-core-image-card__remove"
								icon={ closeSmall }
								label={ __( 'Remove card', 'noorifa-core' ) }
								onClick={ () => removeItem( index ) }
							/>

							{ item.overlay > 0 && (
								<span
									className="noorifa-core-image-card__overlay"
									style={ {
										opacity: item.overlay / 100,
										backgroundColor:
											item.overlayColor || '#000000',
									} }
									aria-hidden="true"
								/>
							) }

							<div
								className="noorifa-core-image-card__content"
								style={
									item.textColor
										? { color: item.textColor }
										: undefined
								}
							>
								<RichText
									tagName="h3"
									className="noorifa-core-image-card__heading"
									value={ item.heading }
									onChange={ ( value ) =>
										updateItem( index, 'heading', value )
									}
									placeholder={ __(
										'Heading',
										'noorifa-core'
									) }
								/>
								<RichText
									tagName="p"
									className="noorifa-core-image-card__text"
									value={ item.subheading }
									onChange={ ( value ) =>
										updateItem( index, 'subheading', value )
									}
									placeholder={ __(
										'Supporting text',
										'noorifa-core'
									) }
								/>
								<RichText
									tagName="span"
									className="noorifa-core-image-card__link"
									value={ item.linkText }
									onChange={ ( value ) =>
										updateItem( index, 'linkText', value )
									}
									placeholder={ __(
										'Link label',
										'noorifa-core'
									) }
								/>
							</div>
						</div>
					) ) }
				</div>

				<Button
					variant="secondary"
					onClick={ addItem }
					className="noorifa-core-image-card__add"
				>
					{ __( 'Add card', 'noorifa-core' ) }
				</Button>
			</div>
		</>
	);
}
