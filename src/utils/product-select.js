import { __ } from '@wordpress/i18n';
import { ComboboxControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/*
 * Product picker backed by the WooCommerce Store API (public, no nonce
 * needed for GET). Used inside the Inline Checkout package repeater to
 * link each package to a real WC product, so price/image/name are always
 * pulled server-side from WooCommerce rather than typed by hand.
 */
export default function ProductSelect( { value, onChange, label } ) {
	const [ options, setOptions ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ loading, setLoading ] = useState( false );

	// Fetches the current selection's name once, so the control shows a
	// readable label instead of a bare id when the linked product isn't in
	// the latest search results.
	useEffect( () => {
		if ( ! value ) {
			return;
		}

		let cancelled = false;

		apiFetch( { path: `/wc/store/v1/products/${ value }` } )
			.then( ( product ) => {
				if ( cancelled || ! product ) {
					return;
				}

				setOptions( ( current ) => {
					if (
						current.some( ( o ) => o.value === String( value ) )
					) {
						return current;
					}

					return [
						{ value: String( value ), label: product.name },
						...current,
					];
				} );
			} )
			.catch( () => {} );

		return () => {
			cancelled = true;
		};
	}, [ value ] );

	// Searches products as the merchant types. Debounced lightly so a fast
	// typist doesn't fire a request per keystroke.
	useEffect( () => {
		let cancelled = false;
		setLoading( true );

		const timer = setTimeout( () => {
			apiFetch( {
				path: `/wc/store/v1/products?per_page=20&search=${ encodeURIComponent(
					search
				) }`,
			} )
				.then( ( products ) => {
					if ( cancelled ) {
						return;
					}

					setOptions( ( current ) => {
						const selected = current.filter(
							( o ) => o.value === String( value )
						);
						const fetched = ( products || [] ).map( ( p ) => ( {
							value: String( p.id ),
							label: p.name,
						} ) );
						const seen = new Set(
							selected.map( ( o ) => o.value )
						);

						return [
							...selected,
							...fetched.filter( ( o ) => ! seen.has( o.value ) ),
						];
					} );
				} )
				.catch( () => {} )
				.finally( () => {
					if ( ! cancelled ) {
						setLoading( false );
					}
				} );
		}, 300 );

		return () => {
			cancelled = true;
			clearTimeout( timer );
		};
	}, [ search, value ] );

	return (
		<ComboboxControl
			__nextHasNoMarginBottom
			label={ label || __( 'Product', 'noorifa-core' ) }
			help={
				loading ? (
					<Spinner />
				) : (
					__( 'Search a WooCommerce product.', 'noorifa-core' )
				)
			}
			value={ value ? String( value ) : null }
			options={ options }
			onFilterValueChange={ ( input ) => setSearch( input ) }
			onChange={ ( next ) => onChange( next ? parseInt( next, 10 ) : 0 ) }
		/>
	);
}
