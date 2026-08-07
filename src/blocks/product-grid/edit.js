import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	SelectControl,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

const RELATION_OPTIONS = [
	{ label: __( 'Latest products', 'noorifa-core' ), value: 'latest' },
	{
		label: __( 'Related to current product', 'noorifa-core' ),
		value: 'related',
	},
	{
		label: __( 'Upsells for current product', 'noorifa-core' ),
		value: 'upsells',
	},
	{
		label: __( 'Cross-sells for current product', 'noorifa-core' ),
		value: 'cross-sells',
	},
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		relation,
		productsToShow,
		columns,
		order,
		orderBy,
		showImage,
		showPrice,
		showAddToCart,
		boxed,
		boxedWidth,
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query', 'noorifa-core' ) }>
					<SelectControl
						label={ __( 'Products', 'noorifa-core' ) }
						value={ relation }
						options={ RELATION_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { relation: value } )
						}
					/>
					<RangeControl
						label={ __( 'Number of products', 'noorifa-core' ) }
						value={ productsToShow }
						onChange={ ( value ) =>
							setAttributes( { productsToShow: value } )
						}
						min={ 1 }
						max={ 12 }
					/>
					{ 'latest' === relation && (
						<>
							<SelectControl
								label={ __( 'Order by', 'noorifa-core' ) }
								value={ orderBy }
								options={ [
									{
										label: __( 'Date', 'noorifa-core' ),
										value: 'date',
									},
									{
										label: __( 'Title', 'noorifa-core' ),
										value: 'title',
									},
									{
										label: __( 'Random', 'noorifa-core' ),
										value: 'rand',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { orderBy: value } )
								}
							/>
							<SelectControl
								label={ __( 'Order', 'noorifa-core' ) }
								value={ order }
								options={ [
									{
										label: __(
											'Descending',
											'noorifa-core'
										),
										value: 'desc',
									},
									{
										label: __(
											'Ascending',
											'noorifa-core'
										),
										value: 'asc',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { order: value } )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'noorifa-core' ) }>
					<RangeControl
						label={ __( 'Columns', 'noorifa-core' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 4 }
					/>
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

				<PanelBody title={ __( 'Content', 'noorifa-core' ) }>
					<ToggleControl
						label={ __( 'Product image', 'noorifa-core' ) }
						checked={ showImage }
						onChange={ ( value ) =>
							setAttributes( { showImage: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Price', 'noorifa-core' ) }
						checked={ showPrice }
						onChange={ ( value ) =>
							setAttributes( { showPrice: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Add to cart button', 'noorifa-core' ) }
						checked={ showAddToCart }
						onChange={ ( value ) =>
							setAttributes( { showAddToCart: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="noorifa-core/product-grid"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
