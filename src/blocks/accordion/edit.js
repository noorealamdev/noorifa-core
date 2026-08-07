import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	useSettings,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	RangeControl,
	BaseControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { getAccordionStyle } from './shared';

const TEMPLATE = [
	[ 'noorifa-core/accordion-item' ],
	[ 'noorifa-core/accordion-item' ],
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		allowMultiple,
		boxed,
		boxedWidth,
		titleBackground,
		contentBackground,
	} = attributes;
	const [ colors = [] ] = useSettings( 'color.palette' );
	const titleBgId = useInstanceId( Edit, 'noorifa-core-accordion-title-bg' );
	const contentBgId = useInstanceId(
		Edit,
		'noorifa-core-accordion-content-bg'
	);
	const blockProps = useBlockProps( {
		className: boxed ? 'is-boxed' : undefined,
		style: getAccordionStyle( attributes ),
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'noorifa-core/accordion-item' ],
		template: TEMPLATE,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Accordion Settings', 'noorifa-core' ) }>
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
				<PanelBody
					title={ __( 'Colors', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<BaseControl
						__nextHasNoMarginBottom
						id={ titleBgId }
						label={ __( 'Title bar background', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ titleBackground }
							onChange={ ( value ) =>
								setAttributes( {
									titleBackground: value || '',
								} )
							}
						/>
					</BaseControl>
					<BaseControl
						__nextHasNoMarginBottom
						id={ contentBgId }
						label={ __( 'Content background', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ contentBackground }
							onChange={ ( value ) =>
								setAttributes( {
									contentBackground: value || '',
								} )
							}
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}
