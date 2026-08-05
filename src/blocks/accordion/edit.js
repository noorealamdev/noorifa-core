import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

const TEMPLATE = [
	[ 'noorifa-core/accordion-item' ],
	[ 'noorifa-core/accordion-item' ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { allowMultiple, boxed, boxedWidth } = attributes;
	const blockProps = useBlockProps( {
		className: boxed ? 'is-boxed' : undefined,
		style: boxed ? { maxWidth: boxedWidth } : undefined,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'noorifa-core/accordion-item' ],
		template: TEMPLATE,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Accordion Settings', 'noorifa-core' ) }
				>
					<ToggleControl
						label={ __(
							'Allow multiple open items',
							'noorifa-core'
						) }
						help={ __(
							'When off, opening an item closes the others.',
							'noorifa-core'
						) }
						checked={ allowMultiple }
						onChange={ ( value ) =>
							setAttributes( { allowMultiple: value } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Layout', 'noorifa-core' ) } initialOpen={ false }>
					<ToggleControl
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

			<div { ...innerBlocksProps } />
		</>
	);
}
