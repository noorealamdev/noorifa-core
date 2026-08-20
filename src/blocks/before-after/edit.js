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
	BaseControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

export default function Edit( { attributes, setAttributes } ) {
	const {
		beforeImageUrl,
		beforeLabel,
		afterImageUrl,
		afterLabel,
		initialPosition,
		height,
		badgeBackground,
		badgeTextColor,
		handleColor,
	} = attributes;

	const [ colors = [] ] = useSettings( 'color.palette' );
	const colorInstanceId = useInstanceId( Edit, 'noorifa-before-after-color' );

	const blockProps = useBlockProps( {
		className: 'noorifa-core-before-after',
		style: {
			'--noorifa-before-after-position': `${ initialPosition }%`,
			'--noorifa-before-after-height': `${ height }px`,
			...( badgeBackground
				? { '--noorifa-before-after-badge-bg': badgeBackground }
				: {} ),
			...( badgeTextColor
				? { '--noorifa-before-after-badge-text': badgeTextColor }
				: {} ),
			...( handleColor
				? { '--noorifa-before-after-handle': handleColor }
				: {} ),
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Height (px)', 'noorifa-core' ) }
						value={ height }
						onChange={ ( value ) =>
							setAttributes( { height: value || 320 } )
						}
						min={ 200 }
						max={ 900 }
						step={ 10 }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Starting position (%)', 'noorifa-core' ) }
						value={ initialPosition }
						onChange={ ( value ) =>
							setAttributes( {
								initialPosition: value ?? 50,
							} )
						}
						min={ 0 }
						max={ 100 }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Colors', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<BaseControl
						__nextHasNoMarginBottom
						id={ `${ colorInstanceId }-badge-bg` }
						label={ __( 'Badge background', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ badgeBackground }
							onChange={ ( value ) =>
								setAttributes( {
									badgeBackground: value || '',
								} )
							}
						/>
					</BaseControl>
					<BaseControl
						__nextHasNoMarginBottom
						id={ `${ colorInstanceId }-badge-text` }
						label={ __( 'Badge text color', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ badgeTextColor }
							onChange={ ( value ) =>
								setAttributes( {
									badgeTextColor: value || '',
								} )
							}
						/>
					</BaseControl>
					<BaseControl
						__nextHasNoMarginBottom
						id={ `${ colorInstanceId }-handle` }
						label={ __( 'Handle color', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ handleColor }
							onChange={ ( value ) =>
								setAttributes( {
									handleColor: value || '',
								} )
							}
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="noorifa-core-before-after__image noorifa-core-before-after__image--before">
					{ beforeImageUrl && <img src={ beforeImageUrl } alt="" /> }
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									beforeImageId: media.id,
									beforeImageUrl: media.url,
								} )
							}
							allowedTypes={ [ 'image' ] }
							value={ attributes.beforeImageId }
							render={ ( { open } ) => (
								<Button
									variant="secondary"
									className="noorifa-core-before-after__select"
									onClick={ open }
								>
									{ beforeImageUrl
										? __(
												'Replace before image',
												'noorifa-core'
										  )
										: __(
												'Select before image',
												'noorifa-core'
										  ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</div>

				<div className="noorifa-core-before-after__image noorifa-core-before-after__image--after">
					{ afterImageUrl && <img src={ afterImageUrl } alt="" /> }
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									afterImageId: media.id,
									afterImageUrl: media.url,
								} )
							}
							allowedTypes={ [ 'image' ] }
							value={ attributes.afterImageId }
							render={ ( { open } ) => (
								<Button
									variant="secondary"
									className="noorifa-core-before-after__select"
									onClick={ open }
								>
									{ afterImageUrl
										? __(
												'Replace after image',
												'noorifa-core'
										  )
										: __(
												'Select after image',
												'noorifa-core'
										  ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</div>

				<RichText
					tagName="span"
					className="noorifa-core-before-after__badge noorifa-core-before-after__badge--before"
					value={ beforeLabel }
					onChange={ ( value ) =>
						setAttributes( { beforeLabel: value } )
					}
					placeholder={ __( 'Before', 'noorifa-core' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="span"
					className="noorifa-core-before-after__badge noorifa-core-before-after__badge--after"
					value={ afterLabel }
					onChange={ ( value ) =>
						setAttributes( { afterLabel: value } )
					}
					placeholder={ __( 'After', 'noorifa-core' ) }
					allowedFormats={ [] }
				/>

				<div
					className="noorifa-core-before-after__handle-wrap"
					aria-hidden="true"
				>
					<span className="noorifa-core-before-after__line" />
					<span className="noorifa-core-before-after__handle">
						<svg
							width="10"
							height="10"
							viewBox="0 0 10 10"
							fill="none"
						>
							<path
								d="M6 1 2 5l4 4"
								stroke="currentColor"
								strokeWidth="1.5"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
						<svg
							width="10"
							height="10"
							viewBox="0 0 10 10"
							fill="none"
						>
							<path
								d="M4 1l4 4-4 4"
								stroke="currentColor"
								strokeWidth="1.5"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</span>
				</div>
			</div>
		</>
	);
}
