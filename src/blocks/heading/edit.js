import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	AlignmentControl,
	InspectorControls,
	useSettings,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	BaseControl,
	FontSizePicker,
	// No stable/public equivalents — these are the same editor primitives
	// WP core's own inspector controls use (a number field for line height
	// and a segmented control for the above/below position).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { getSubheadingStyle } from './shared';

const LEVEL_OPTIONS = [ 1, 2, 3, 4, 5, 6 ].map( ( level ) => ( {
	label: `H${ level }`,
	value: level,
} ) );

const FONT_WEIGHT_OPTIONS = [
	{ label: __( 'Default', 'noorifa-core' ), value: '' },
	{ label: __( 'Light (300)', 'noorifa-core' ), value: '300' },
	{ label: __( 'Normal (400)', 'noorifa-core' ), value: '400' },
	{ label: __( 'Medium (500)', 'noorifa-core' ), value: '500' },
	{ label: __( 'Semi Bold (600)', 'noorifa-core' ), value: '600' },
	{ label: __( 'Bold (700)', 'noorifa-core' ), value: '700' },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		content,
		level,
		textAlign,
		lineHeight,
		showSubheading,
		subheading,
		subheadingPosition,
		subheadingFontSize,
		subheadingColor,
		subheadingFontWeight,
	} = attributes;
	const TagName = `h${ level }`;
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );
	const [ colors = [] ] = useSettings( 'color.palette' );
	const subheadingColorId = useInstanceId(
		Edit,
		'noorifa-core-heading-subheading-color'
	);
	const subheadingFontSizeId = useInstanceId(
		Edit,
		'noorifa-core-heading-subheading-font-size'
	);

	const blockProps = useBlockProps( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
		// Merged with the style block supports generates (font size, etc.),
		// so this only adds line-height without clobbering the rest.
		style: lineHeight ? { lineHeight } : undefined,
	} );

	const subheadingEl = (
		<RichText
			tagName="p"
			className="noorifa-core-heading__subheading"
			style={ getSubheadingStyle( attributes ) }
			value={ subheading }
			onChange={ ( value ) => setAttributes( { subheading: value } ) }
			placeholder={ __( 'Add subheading…', 'noorifa-core' ) }
		/>
	);

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
				<PanelBody title={ __( 'Heading Settings', 'noorifa-core' ) }>
					<SelectControl
						label={ __( 'Heading Level', 'noorifa-core' ) }
						value={ level }
						options={ LEVEL_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { level: Number( value ) } )
						}
					/>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Line height', 'noorifa-core' ) }
						value={ lineHeight }
						min={ 0 }
						step={ 0.1 }
						onChange={ ( value ) =>
							setAttributes( { lineHeight: value || '' } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Subheading', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show subheading', 'noorifa-core' ) }
						checked={ showSubheading }
						onChange={ ( value ) =>
							setAttributes( { showSubheading: value } )
						}
					/>

					{ showSubheading && (
						<>
							<ToggleGroupControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Position', 'noorifa-core' ) }
								value={ subheadingPosition }
								onChange={ ( value ) =>
									setAttributes( {
										subheadingPosition: value || 'below',
									} )
								}
							>
								<ToggleGroupControlOption
									value="above"
									label={ __( 'Above', 'noorifa-core' ) }
								/>
								<ToggleGroupControlOption
									value="below"
									label={ __( 'Below', 'noorifa-core' ) }
								/>
							</ToggleGroupControl>

							<BaseControl
								__nextHasNoMarginBottom
								id={ subheadingFontSizeId }
								label={ __( 'Font size', 'noorifa-core' ) }
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
							</BaseControl>

							<NumberControl
								__next40pxDefaultSize
								label={ __( 'Line height', 'noorifa-core' ) }
								value={ attributes.subheadingLineHeight }
								min={ 0 }
								step={ 0.1 }
								onChange={ ( value ) =>
									setAttributes( {
										subheadingLineHeight: value || '',
									} )
								}
							/>

							<SelectControl
								__nextHasNoMarginBottom
								label={ __( 'Font weight', 'noorifa-core' ) }
								value={ subheadingFontWeight }
								options={ FONT_WEIGHT_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( {
										subheadingFontWeight: value || '',
									} )
								}
							/>

							<BaseControl
								__nextHasNoMarginBottom
								id={ subheadingColorId }
								label={ __( 'Text color', 'noorifa-core' ) }
							>
								<ColorPalette
									colors={ colors }
									value={ subheadingColor }
									onChange={ ( value ) =>
										setAttributes( {
											subheadingColor: value || '',
										} )
									}
								/>
							</BaseControl>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			{ showSubheading ? (
				<div { ...blockProps }>
					{ 'above' === subheadingPosition && subheadingEl }
					<RichText
						tagName={ TagName }
						value={ content }
						onChange={ ( value ) =>
							setAttributes( { content: value } )
						}
						placeholder={ __( 'Add heading…', 'noorifa-core' ) }
					/>
					{ 'below' === subheadingPosition && subheadingEl }
				</div>
			) : (
				<RichText
					{ ...blockProps }
					tagName={ TagName }
					value={ content }
					onChange={ ( value ) =>
						setAttributes( { content: value } )
					}
					placeholder={ __( 'Add heading…', 'noorifa-core' ) }
				/>
			) }
		</>
	);
}
