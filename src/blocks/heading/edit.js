import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	AlignmentControl,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	// No stable/public equivalent — the native line-height control
	// (supports.typography.lineHeight) only surfaces when the theme opts
	// in via theme.json, which this classic theme intentionally doesn't
	// use, so the control is hand-rolled with the same primitive WP core
	// wraps internally.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

const LEVEL_OPTIONS = [ 1, 2, 3, 4, 5, 6 ].map( ( level ) => ( {
	label: `H${ level }`,
	value: level,
} ) );

export default function Edit( { attributes, setAttributes } ) {
	const { content, level, textAlign, lineHeight } = attributes;
	const TagName = `h${ level }`;

	const blockProps = useBlockProps( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
		// Merged with the style block supports generates (font size, etc.),
		// so this only adds line-height without clobbering the rest.
		style: lineHeight ? { lineHeight } : undefined,
	} );

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
			</InspectorControls>

			<RichText
				{ ...blockProps }
				tagName={ TagName }
				value={ content }
				onChange={ ( value ) => setAttributes( { content: value } ) }
				placeholder={ __( 'Add heading…', 'noorifa-core' ) }
			/>
		</>
	);
}
