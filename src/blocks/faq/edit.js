import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

const TEMPLATE = [
	[
		'noorifa-core/accordion',
		{},
		[
			[
				'noorifa-core/accordion-item',
				{
					title: __( 'How long does shipping take?', 'noorifa-core' ),
					uid: 'faq-shipping',
				},
				[
					[
						'core/paragraph',
						{
							content: __(
								'Most orders ship within 1–2 business days and arrive within 5–7 business days depending on your location.',
								'noorifa-core'
							),
						},
					],
				],
			],
			[
				'noorifa-core/accordion-item',
				{
					title: __( 'What is your return policy?', 'noorifa-core' ),
					uid: 'faq-returns',
				},
				[
					[
						'core/paragraph',
						{
							content: __(
								'We offer a 30-day return window on unused items in their original packaging. Contact us to start a return.',
								'noorifa-core'
							),
						},
					],
				],
			],
			[
				'noorifa-core/accordion-item',
				{
					title: __( 'Do you ship internationally?', 'noorifa-core' ),
					uid: 'faq-international',
				},
				[
					[
						'core/paragraph',
						{
							content: __(
								'Yes, we ship to most countries worldwide. International shipping rates are calculated at checkout.',
								'noorifa-core'
							),
						},
					],
				],
			],
		],
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { title, boxed, boxedWidth } = attributes;
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{
			className:
				'noorifa-core-faq__accordion' + ( boxed ? ' is-boxed' : '' ),
			style: boxed ? { maxWidth: boxedWidth } : undefined,
		},
		{
			allowedBlocks: [ 'noorifa-core/accordion' ],
			template: TEMPLATE,
			templateInsertUpdatesSelection: false,
		}
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
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
			</InspectorControls>
			<RichText
				tagName="h2"
				className="noorifa-core-faq__title"
				value={ title }
				onChange={ ( value ) => setAttributes( { title: value } ) }
				placeholder={ __(
					'Frequently Asked Questions',
					'noorifa-core'
				) }
			/>
			<div { ...innerBlocksProps } />
		</div>
	);
}
