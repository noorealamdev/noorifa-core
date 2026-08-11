import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
	// No stable/public equivalent — same segmented-control primitive WP
	// core's own inspector panels use throughout (also used by this plugin's
	// Hero and Feature Cards blocks).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Derives a preview poster from a YouTube URL, so a card shows something
 * without the merchant having to upload a poster separately. Vimeo has no
 * public thumbnail URL without an API call, so it falls back to the styled
 * placeholder. render.php mirrors this logic for the front end.
 *
 * @param {string} url Video URL.
 * @return {string} Thumbnail URL, or '' if none can be derived.
 */
function youtubeThumb( url ) {
	if ( ! url ) {
		return '';
	}
	const match = url.match(
		/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/
	);
	return match ? `https://i.ytimg.com/vi/${ match[ 1 ] }/hqdefault.jpg` : '';
}

export default function Edit( { attributes, setAttributes } ) {
	const { items, layout, columns, cardBackground, boxed, boxedWidth } =
		attributes;

	const blockProps = useBlockProps( {
		className: 'noorifa-core-video-testimonials is-' + layout,
		style: cardBackground
			? { '--noorifa-video-card-bg': cardBackground }
			: undefined,
	} );

	const updateItem = ( index, field, value ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { items: next } );
	};

	// A single combined update so both fields land in the same setAttributes()
	// call — two back-to-back updateItem() calls would each read the same
	// stale `items` and the second would discard the first (same pattern as
	// Feature Cards).
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
					videoType: 'url',
					videoUrl: '',
					videoFileId: 0,
					videoFileUrl: '',
					posterId: 0,
					posterUrl: '',
					name: '',
					role: '',
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
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Display as', 'noorifa-core' ) }
						value={ layout }
						onChange={ ( value ) =>
							setAttributes( { layout: value || 'grid' } )
						}
					>
						<ToggleGroupControlOption
							value="grid"
							label={ __( 'Grid', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="carousel"
							label={ __( 'Carousel', 'noorifa-core' ) }
						/>
					</ToggleGroupControl>
					{ 'grid' === layout && (
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
					) }
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
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className={
						'noorifa-core-video-testimonials__list' +
						( boxed ? ' is-boxed' : '' )
					}
					style={ {
						...( boxed ? { maxWidth: boxedWidth } : {} ),
						'--noorifa-video-cols': columns,
					} }
				>
					{ items.map( ( item, index ) => {
						const poster =
							item.posterUrl ||
							( 'url' === item.videoType
								? youtubeThumb( item.videoUrl )
								: '' );

						return (
							<div
								className="noorifa-core-video-testimonials__item"
								key={ index }
							>
								<Button
									className="noorifa-core-video-testimonials__remove"
									icon={ closeSmall }
									label={ __(
										'Remove testimonial',
										'noorifa-core'
									) }
									onClick={ () => removeItem( index ) }
								/>

								<div
									className="noorifa-core-video-testimonials__media"
									style={
										poster
											? {
													backgroundImage: `url(${ poster })`,
											  }
											: undefined
									}
								>
									<span
										className="noorifa-core-video-testimonials__play"
										aria-hidden="true"
									/>
								</div>

								<div className="noorifa-core-video-testimonials__controls">
									<ToggleGroupControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										isBlock
										label={ __(
											'Video source',
											'noorifa-core'
										) }
										value={ item.videoType || 'url' }
										onChange={ ( value ) =>
											updateItem(
												index,
												'videoType',
												value || 'url'
											)
										}
									>
										<ToggleGroupControlOption
											value="url"
											label={ __(
												'Link',
												'noorifa-core'
											) }
										/>
										<ToggleGroupControlOption
											value="upload"
											label={ __(
												'Upload',
												'noorifa-core'
											) }
										/>
									</ToggleGroupControl>

									{ 'upload' === item.videoType ? (
										<MediaUploadCheck>
											<MediaUpload
												onSelect={ ( media ) =>
													updateItemFields( index, {
														videoFileId: media.id,
														videoFileUrl: media.url,
													} )
												}
												allowedTypes={ [ 'video' ] }
												value={ item.videoFileId }
												render={ ( { open } ) => (
													<Button
														variant="secondary"
														onClick={ open }
														className="noorifa-core-video-testimonials__btn"
													>
														{ item.videoFileUrl
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
									) : (
										<TextControl
											__nextHasNoMarginBottom
											label={ __(
												'YouTube / Vimeo URL',
												'noorifa-core'
											) }
											value={ item.videoUrl }
											onChange={ ( value ) =>
												updateItem(
													index,
													'videoUrl',
													value
												)
											}
											placeholder="https://youtu.be/…"
										/>
									) }

									<MediaUploadCheck>
										<MediaUpload
											onSelect={ ( media ) =>
												updateItemFields( index, {
													posterId: media.id,
													posterUrl: media.url,
												} )
											}
											allowedTypes={ [ 'image' ] }
											value={ item.posterId }
											render={ ( { open } ) => (
												<Button
													variant="tertiary"
													onClick={ open }
													className="noorifa-core-video-testimonials__btn"
												>
													{ item.posterUrl
														? __(
																'Replace poster',
																'noorifa-core'
														  )
														: __(
																'Set poster image',
																'noorifa-core'
														  ) }
												</Button>
											) }
										/>
									</MediaUploadCheck>
									{ item.posterUrl && (
										<Button
											variant="link"
											isDestructive
											onClick={ () =>
												updateItemFields( index, {
													posterId: 0,
													posterUrl: '',
												} )
											}
										>
											{ __(
												'Remove poster',
												'noorifa-core'
											) }
										</Button>
									) }
								</div>

								<RichText
									tagName="span"
									className="noorifa-core-video-testimonials__name"
									value={ item.name }
									onChange={ ( value ) =>
										updateItem( index, 'name', value )
									}
									placeholder={ __(
										'Customer name',
										'noorifa-core'
									) }
								/>
								<RichText
									tagName="span"
									className="noorifa-core-video-testimonials__role"
									value={ item.role }
									onChange={ ( value ) =>
										updateItem( index, 'role', value )
									}
									placeholder={ __(
										'Role / location',
										'noorifa-core'
									) }
								/>
							</div>
						);
					} ) }
				</div>

				<Button
					variant="secondary"
					onClick={ addItem }
					className="noorifa-core-video-testimonials__add"
				>
					{ __( 'Add testimonial', 'noorifa-core' ) }
				</Button>
			</div>
		</>
	);
}
