import { __ } from '@wordpress/i18n';
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
	} = attributes;
	const [ colors = [] ] = useSettings( 'color.palette' );
	const colorInstanceId = useInstanceId( Edit, 'noorifa-image-card-color' );

	const blockProps = useBlockProps( {
		className: 'noorifa-core-image-card',
		style: {
			'--noorifa-image-card-cols': columns,
			'--noorifa-image-card-min-height': `${ minHeight }px`,
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
				},
			],
		} );

	const removeItem = ( index ) =>
		setAttributes( {
			items: items.filter( ( _item, i ) => i !== index ),
		} );

	return (
		<>
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
									style={ { opacity: item.overlay / 100 } }
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

							<div className="noorifa-core-image-card__controls">
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
								<TextControl
									__nextHasNoMarginBottom
									label={ __( 'Link URL', 'noorifa-core' ) }
									value={ item.linkUrl }
									onChange={ ( value ) =>
										updateItem( index, 'linkUrl', value )
									}
									placeholder="https://"
								/>
								<RangeControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Image overlay',
										'noorifa-core'
									) }
									value={ item.overlay || 0 }
									onChange={ ( value ) =>
										updateItem(
											index,
											'overlay',
											value || 0
										)
									}
									min={ 0 }
									max={ 100 }
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
