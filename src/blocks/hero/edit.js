import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	useSettings,
	RichText,
	InspectorControls,
	BlockControls,
	AlignmentControl,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	BaseControl,
	FontSizePicker,
	RangeControl,
	ColorPalette,
	ToggleControl,
	// No stable/public equivalent — this is the same primitive WP core's
	// own native supports.typography.lineHeight control wraps internally,
	// needed here since Hero has two independent text elements rather
	// than the single element that native support assumes.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import {
	getBackgroundStyle,
	hasBackgroundVideo,
	hasOverlay,
} from './background';

const ACTIONS_TEMPLATE = [ [ 'noorifa-core/button' ] ];

export default function Edit( { attributes, setAttributes } ) {
	const {
		heading,
		subheading,
		backgroundType,
		backgroundImage,
		backgroundVideo,
		overlayColor,
		overlayOpacity,
		textAlign,
		headingFontSize,
		subheadingFontSize,
		headingLineHeight,
		subheadingLineHeight,
		boxed,
		boxedWidth,
	} = attributes;
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );
	const [ colors = [] ] = useSettings( 'color.palette' );
	const overlayColorControlId = useInstanceId(
		Edit,
		'noorifa-core-hero-overlay-color'
	);
	const headingTypographyControlId = useInstanceId(
		Edit,
		'noorifa-core-hero-heading-typography'
	);
	const subheadingTypographyControlId = useInstanceId(
		Edit,
		'noorifa-core-hero-subheading-typography'
	);

	const blockProps = useBlockProps( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
		style: getBackgroundStyle(
			backgroundType,
			backgroundImage,
			backgroundVideo,
			overlayOpacity
		),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'noorifa-core-hero__actions' },
		{
			allowedBlocks: [ 'noorifa-core/button', 'core/buttons' ],
			template: ACTIONS_TEMPLATE,
			templateInsertUpdatesSelection: false,
		}
	);

	const onSelectImage = ( media ) =>
		setAttributes( {
			backgroundImage: { url: media.url, id: media.id },
		} );

	const onRemoveImage = () =>
		setAttributes( { backgroundImage: { url: '', id: 0 } } );

	const onSelectVideo = ( media ) =>
		setAttributes( {
			backgroundVideo: { url: media.url, id: media.id },
		} );

	const onRemoveVideo = () =>
		setAttributes( { backgroundVideo: { url: '', id: 0 } } );

	const showVideo = hasBackgroundVideo( backgroundType, backgroundVideo );
	const showOverlay = hasOverlay( overlayOpacity );

	return (
		<>
			<BlockControls>
				<AlignmentControl
					value={ textAlign }
					onChange={ ( value ) =>
						setAttributes( { textAlign: value || 'center' } )
					}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Background', 'noorifa-core' ) }>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Type', 'noorifa-core' ) }
						value={ backgroundType }
						onChange={ ( value ) =>
							setAttributes( { backgroundType: value } )
						}
					>
						<ToggleGroupControlOption
							value="image"
							label={ __( 'Image', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="video"
							label={ __( 'Video', 'noorifa-core' ) }
						/>
					</ToggleGroupControl>

					{ 'video' === backgroundType ? (
						<>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onSelectVideo }
									allowedTypes={ [ 'video' ] }
									value={ backgroundVideo?.id }
									render={ ( { open } ) => (
										<Button
											variant="secondary"
											onClick={ open }
											className="noorifa-core-hero__select-image"
										>
											{ backgroundVideo?.url
												? __(
														'Replace video',
														'noorifa-core'
												  )
												: __(
														'Select video',
														'noorifa-core'
												  ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
							{ backgroundVideo?.url && (
								<Button
									variant="link"
									isDestructive
									onClick={ onRemoveVideo }
								>
									{ __( 'Remove video', 'noorifa-core' ) }
								</Button>
							) }
						</>
					) : (
						<>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onSelectImage }
									allowedTypes={ [ 'image' ] }
									value={ backgroundImage?.id }
									render={ ( { open } ) => (
										<Button
											variant="secondary"
											onClick={ open }
											className="noorifa-core-hero__select-image"
										>
											{ backgroundImage?.url
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
							{ backgroundImage?.url && (
								<Button
									variant="link"
									isDestructive
									onClick={ onRemoveImage }
								>
									{ __( 'Remove image', 'noorifa-core' ) }
								</Button>
							) }
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Overlay', 'noorifa-core' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Opacity', 'noorifa-core' ) }
						help={ __(
							'Darkens the background so text stays readable.',
							'noorifa-core'
						) }
						value={ overlayOpacity }
						onChange={ ( value ) =>
							setAttributes( { overlayOpacity: value ?? 0 } )
						}
						min={ 0 }
						max={ 100 }
					/>
					{ showOverlay && (
						<BaseControl
							__nextHasNoMarginBottom
							id={ overlayColorControlId }
							label={ __( 'Color', 'noorifa-core' ) }
						>
							<ColorPalette
								colors={ colors }
								value={ overlayColor }
								onChange={ ( value ) =>
									setAttributes( {
										overlayColor: value || '#000000',
									} )
								}
							/>
						</BaseControl>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Typography', 'noorifa-core' ) }>
					<BaseControl
						__nextHasNoMarginBottom
						id={ headingTypographyControlId }
						label={ __( 'Heading', 'noorifa-core' ) }
					>
						<FontSizePicker
							__nextHasNoMarginBottom
							value={ headingFontSize }
							fontSizes={ fontSizes }
							onChange={ ( value ) =>
								setAttributes( {
									headingFontSize: value || '',
								} )
							}
						/>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Line height', 'noorifa-core' ) }
							value={ headingLineHeight }
							min={ 0 }
							step={ 0.1 }
							onChange={ ( value ) =>
								setAttributes( {
									headingLineHeight: value || '',
								} )
							}
						/>
					</BaseControl>
					<BaseControl
						__nextHasNoMarginBottom
						id={ subheadingTypographyControlId }
						label={ __( 'Subheading', 'noorifa-core' ) }
					>
						<FontSizePicker
							__nextHasNoMarginBottom
							value={ subheadingFontSize }
							fontSizes={ fontSizes }
							onChange={ ( value ) =>
								setAttributes( {
									subheadingFontSize: value || '',
								} )
							}
						/>
						<NumberControl
							__next40pxDefaultSize
							label={ __( 'Line height', 'noorifa-core' ) }
							value={ subheadingLineHeight }
							min={ 0 }
							step={ 0.1 }
							onChange={ ( value ) =>
								setAttributes( {
									subheadingLineHeight: value || '',
								} )
							}
						/>
					</BaseControl>
				</PanelBody>

				<PanelBody
					title={ __( 'Layout', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Boxed width', 'noorifa-core' ) }
						checked={ boxed }
						onChange={ ( value ) =>
							setAttributes( { boxed: value } )
						}
						help={
							boxed
								? __(
										'Heading, subheading and buttons are constrained to a max width and centered.',
										'noorifa-core'
								  )
								: __(
										'Heading, subheading and buttons stretch the full width of the hero.',
										'noorifa-core'
								  )
						}
					/>
					{ boxed && (
						<RangeControl
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
				{ showVideo && (
					<video
						className="noorifa-core-hero__bg-video"
						src={ backgroundVideo.url }
						autoPlay
						muted
						loop
						playsInline
					/>
				) }
				{ showOverlay && (
					<div
						className="noorifa-core-hero__overlay"
						style={ {
							backgroundColor: overlayColor,
							opacity: overlayOpacity / 100,
						} }
					/>
				) }
				<div
					className={
						'noorifa-core-hero__content' +
						( boxed ? ' is-boxed' : '' )
					}
					style={ boxed ? { maxWidth: boxedWidth } : undefined }
				>
					<RichText
						tagName="h1"
						className="noorifa-core-hero__heading"
						style={ {
							...( headingFontSize && {
								fontSize: headingFontSize,
							} ),
							...( headingLineHeight && {
								lineHeight: headingLineHeight,
							} ),
						} }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
						placeholder={ __(
							'Your Hero Heading',
							'noorifa-core'
						) }
					/>
					<RichText
						tagName="p"
						className="noorifa-core-hero__subheading"
						style={ {
							...( subheadingFontSize && {
								fontSize: subheadingFontSize,
							} ),
							...( subheadingLineHeight && {
								lineHeight: subheadingLineHeight,
							} ),
						} }
						value={ subheading }
						onChange={ ( value ) =>
							setAttributes( { subheading: value } )
						}
						placeholder={ __(
							'Add a supporting subheading…',
							'noorifa-core'
						) }
					/>
					<div { ...innerBlocksProps } />
				</div>
			</div>
		</>
	);
}
