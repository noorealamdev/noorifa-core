import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	AlignmentControl,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

const LEVEL_OPTIONS = [ 1, 2, 3, 4, 5, 6 ].map( ( level ) => ( {
	label: `H${ level }`,
	value: level,
} ) );

export default function Edit( { attributes, setAttributes } ) {
	const { content, level, textAlign } = attributes;
	const TagName = `h${ level }`;

	const blockProps = useBlockProps( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
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
