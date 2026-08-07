import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	useBlockProps,
	InspectorControls,
	useSettings,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ComboboxControl,
	CheckboxControl,
	Spinner,
	Notice,
	ToggleControl,
	RangeControl,
	BaseControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit( { attributes, setAttributes } ) {
	const { productId, selectedReviews, boxed, boxedWidth, cardBackground } =
		attributes;
	const [ colors = [] ] = useSettings( 'color.palette' );
	const cardBgId = useInstanceId(
		Edit,
		'noorifa-core-review-carousel-card-bg'
	);
	const blockProps = useBlockProps();

	const [ productSearch, setProductSearch ] = useState( '' );
	const [ productOptions, setProductOptions ] = useState( [] );

	const [ reviews, setReviews ] = useState( [] );
	const [ reviewsLoading, setReviewsLoading ] = useState( false );

	useEffect( () => {
		let cancelled = false;

		apiFetch( {
			path: addQueryArgs( '/wp/v2/product', {
				search: productSearch,
				per_page: 20,
				_fields: 'id,title',
			} ),
		} )
			.then( ( results ) => {
				if ( cancelled ) {
					return;
				}

				setProductOptions(
					( Array.isArray( results ) ? results : [] ).map(
						( product ) => ( {
							value: String( product.id ),
							label: decodeEntities(
								product.title?.rendered || `#${ product.id }`
							),
						} )
					)
				);
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setProductOptions( [] );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ productSearch ] );

	useEffect( () => {
		if ( ! productId ) {
			setReviews( [] );
			return;
		}

		let cancelled = false;
		setReviewsLoading( true );

		apiFetch( {
			path: `/noorifa-core/v1/product-reviews/${ productId }/list`,
		} )
			.then( ( results ) => {
				if ( ! cancelled ) {
					setReviews( Array.isArray( results ) ? results : [] );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setReviews( [] );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setReviewsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ productId ] );

	const toggleReview = ( id ) => {
		const next = selectedReviews.includes( id )
			? selectedReviews.filter( ( existing ) => existing !== id )
			: [ ...selectedReviews, id ];

		setAttributes( { selectedReviews: next } );
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Reviews', 'noorifa-core' ) }
					initialOpen
				>
					<ComboboxControl
						label={ __( 'Product', 'noorifa-core' ) }
						value={ productId ? String( productId ) : null }
						options={ productOptions }
						onFilterValueChange={ setProductSearch }
						onChange={ ( value ) =>
							setAttributes( {
								productId: value ? Number( value ) : 0,
								selectedReviews: [],
							} )
						}
						help={ __(
							'Search by product name, then pick reviews below.',
							'noorifa-core'
						) }
					/>

					{ !! productId && reviewsLoading && <Spinner /> }

					{ !! productId && ! reviewsLoading && ! reviews.length && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This product has no reviews yet.',
								'noorifa-core'
							) }
						</Notice>
					) }

					{ !! productId &&
						! reviewsLoading &&
						reviews.map( ( review ) => (
							<CheckboxControl
								key={ review.id }
								label={ `${ '★'.repeat(
									Math.round( review.rating )
								) } ${ review.author } — ${ review.excerpt }` }
								checked={ selectedReviews.includes(
									review.id
								) }
								onChange={ () => toggleReview( review.id ) }
							/>
						) ) }
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
						id={ cardBgId }
						label={ __( 'Card background', 'noorifa-core' ) }
					>
						<ColorPalette
							colors={ colors }
							value={ cardBackground }
							onChange={ ( value ) =>
								setAttributes( { cardBackground: value || '' } )
							}
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>
			<WooPlaceholder
				icon="slides"
				label={ __( 'Review Carousel', 'noorifa-core' ) }
				instructions={
					selectedReviews.length
						? sprintf(
								/* translators: %d: number of selected reviews. */
								__(
									'%d review(s) selected. Preview appears on the front end.',
									'noorifa-core'
								),
								selectedReviews.length
						  )
						: __(
								'Pick a product and select reviews in the block settings panel.',
								'noorifa-core'
						  )
				}
			/>
		</div>
	);
}
