/*
 * Replaces each variation <select> with a row of clickable swatch buttons
 * (using each variation's own image where one is set), without touching
 * WooCommerce's own variation-matching script — that script
 * (wc-add-to-cart-variation.js) reads/writes the <select> directly and
 * reacts to its native 'change' event, so the select stays in the DOM
 * (just visually hidden) and keeps driving price/stock/image updates
 * exactly as it would with the dropdown visible.
 */
function getVariationImages( form, attributeName ) {
	const raw = form && form.dataset.product_variations;

	if ( ! raw || 'false' === raw ) {
		return {};
	}

	let variations;

	try {
		variations = JSON.parse( raw );
	} catch ( error ) {
		return {};
	}

	if ( ! Array.isArray( variations ) ) {
		return {};
	}

	const images = {};

	variations.forEach( ( variation ) => {
		const value = variation.attributes && variation.attributes[ attributeName ];
		const image = variation.image;

		if ( value && image && ! images[ value ] ) {
			images[ value ] = image.gallery_thumbnail_src || image.thumb_src || image.src;
		}
	} );

	return images;
}

function buildSwatches( select ) {
	const options = Array.from( select.options ).filter(
		( option ) => option.value
	);

	if ( ! options.length ) {
		return;
	}

	const form = select.closest( 'form.variations_form' );
	const variationImages = getVariationImages( form, select.name );

	const wrapper = document.createElement( 'div' );
	wrapper.className = 'noorifa-core-swatches';

	const buttons = options.map( ( option ) => {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'noorifa-core-swatch';
		button.disabled = option.disabled;
		button.setAttribute( 'aria-pressed', option.selected ? 'true' : 'false' );
		button.setAttribute( 'aria-label', option.textContent );
		button.title = option.textContent;

		const imageUrl = variationImages[ option.value ];

		if ( imageUrl ) {
			button.classList.add( 'noorifa-core-swatch--image' );
			const img = document.createElement( 'img' );
			img.src = imageUrl;
			img.alt = '';
			img.setAttribute( 'draggable', 'false' );
			button.appendChild( img );
		} else {
			button.textContent = option.textContent;
		}

		if ( option.selected ) {
			button.classList.add( 'is-selected' );
		}

		button.addEventListener( 'click', () => {
			if ( button.disabled ) {
				return;
			}

			select.value = option.value;
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		wrapper.appendChild( button );

		return button;
	} );

	const syncSwatches = () => {
		buttons.forEach( ( button, index ) => {
			const option = options[ index ];
			button.disabled = option.disabled;
			button.classList.toggle( 'is-selected', option.value === select.value );
			button.setAttribute(
				'aria-pressed',
				option.value === select.value ? 'true' : 'false'
			);
		} );
	};

	select.addEventListener( 'change', syncSwatches );

	// WooCommerce disables non-matching <option> elements as sibling
	// attributes change (e.g. picking a Size that isn't available in the
	// chosen Color); watch for that so the swatches stay in sync too.
	const observer = new MutationObserver( syncSwatches );
	options.forEach( ( option ) =>
		observer.observe( option, { attributes: true, attributeFilter: [ 'disabled' ] } )
	);

	select.insertAdjacentElement( 'afterend', wrapper );
	select.classList.add( 'noorifa-core-visually-hidden-select' );
}

function buildQuantityStepper( quantityWrapper ) {
	const input = quantityWrapper.querySelector( 'input.qty' );

	if ( ! input ) {
		return;
	}

	const min = input.min ? Number( input.min ) : 1;
	const max = input.max ? Number( input.max ) : Infinity;

	const makeStepButton = ( label, delta ) => {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'noorifa-core-quantity-step';
		button.textContent = label;
		button.setAttribute( 'aria-label', delta > 0 ? 'Increase quantity' : 'Decrease quantity' );

		button.addEventListener( 'click', () => {
			const current = Number( input.value ) || min;
			const next = Math.min( max, Math.max( min, current + delta ) );

			if ( next !== current ) {
				input.value = next;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		} );

		return button;
	};

	quantityWrapper.classList.add( 'noorifa-core-quantity' );
	input.insertAdjacentElement( 'beforebegin', makeStepButton( '−', -1 ) );
	input.insertAdjacentElement( 'afterend', makeStepButton( '+', 1 ) );
}

document
	.querySelectorAll(
		'.wp-block-noorifa-core-product-add-to-cart .variations select'
	)
	.forEach( buildSwatches );

document
	.querySelectorAll( '.wp-block-noorifa-core-product-add-to-cart .quantity' )
	.forEach( buildQuantityStepper );
