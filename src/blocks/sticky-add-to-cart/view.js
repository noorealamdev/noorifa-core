const FORM_ID = 'noorifa-core-product-form';

/*
 * Finds a real add-to-cart form ELSEWHERE on the page (the Product Add to
 * Cart block, or the theme's) — never the sticky bar's own fallback form.
 * When one exists, the button is re-pointed at it so the shopper's chosen
 * variation/quantity carry over; when it doesn't, the button just submits
 * the bar's own self-contained form (see render.php).
 */
function findExternalProductForm( bar ) {
	const inBlock = document.querySelector(
		'.wp-block-noorifa-core-product-add-to-cart form.cart'
	);

	if ( inBlock ) {
		return inBlock;
	}

	return (
		Array.from( document.querySelectorAll( 'form.cart' ) ).find(
			( form ) => ! bar.contains( form )
		) || null
	);
}

function wireBuyNowButton( bar, form ) {
	const button = bar.querySelector(
		'.noorifa-core-sticky-add-to-cart__button'
	);

	// No external form — leave the button on its own nested form (render.php).
	if ( ! button || ! form ) {
		return;
	}

	if ( ! form.id ) {
		form.id = FORM_ID;
	}

	button.setAttribute( 'form', form.id );
}

/*
 * Shows the bar once the main Add to Cart block has scrolled out of view
 * (the customer has scrolled past it) and hides it again when scrolled
 * back into view. Falls back to a plain scroll-distance threshold when
 * that block isn't present on the page, so the bar still degrades
 * gracefully instead of never appearing.
 */
function watchVisibility( bar ) {
	const addToCartBlock = document.querySelector(
		'.wp-block-noorifa-core-product-add-to-cart'
	);

	if ( addToCartBlock && 'IntersectionObserver' in window ) {
		const observer = new IntersectionObserver(
			( [ entry ] ) => {
				/*
				 * `isIntersecting` alone can't tell "hasn't been scrolled
				 * to yet" (block still below the viewport) apart from
				 * "scrolled past it" (block now above the viewport) — both
				 * report false. Triggering once the block's TOP edge has
				 * gone above the viewport (rather than waiting for the
				 * bottom edge too) means the bar appears as soon as the
				 * main Add to Cart starts leaving the screen, which also
				 * stays reachable on shorter pages that don't have enough
				 * content below it to ever scroll the whole block away.
				 */
				const scrolledPast = entry.boundingClientRect.top < 0;
				bar.classList.toggle( 'is-visible', scrolledPast );
			},
			{ threshold: 0 }
		);

		observer.observe( addToCartBlock );
		return;
	}

	const toggleFromScroll = () => {
		bar.classList.toggle(
			'is-visible',
			window.scrollY > window.innerHeight
		);
	};

	window.addEventListener( 'scroll', toggleFromScroll, { passive: true } );
	toggleFromScroll();
}

/*
 * Keeps the bar's price/thumbnail in sync with WooCommerce's own variation
 * selection, mirroring the same jQuery event handling already used by
 * product-gallery-carousel/view.js — WC's add-to-cart-variation.js
 * triggers 'found_variation'/'reset_data' as jQuery custom events on the
 * form, not native DOM CustomEvents, so a plain addEventListener can't see
 * them.
 */
function watchVariationSync( bar, form ) {
	if (
		! window.jQuery ||
		! form ||
		! form.classList.contains( 'variations_form' )
	) {
		return;
	}

	const priceEl = bar.querySelector(
		'.noorifa-core-sticky-add-to-cart__price'
	);
	const imageEl = bar.querySelector(
		'.noorifa-core-sticky-add-to-cart__media img'
	);

	const defaultPriceHtml = priceEl ? priceEl.innerHTML : '';
	const defaultImageSrc = imageEl ? imageEl.src : '';

	window
		.jQuery( document )
		.on(
			'found_variation',
			'form.variations_form',
			( event, variation ) => {
				if ( priceEl && variation.price_html ) {
					priceEl.innerHTML = variation.price_html;
				}

				if ( imageEl && variation.image ) {
					imageEl.src =
						variation.image.gallery_thumbnail_src ||
						variation.image.thumb_src ||
						variation.image.src ||
						defaultImageSrc;
				}
			}
		);

	window.jQuery( document ).on( 'reset_data', 'form.variations_form', () => {
		if ( priceEl ) {
			priceEl.innerHTML = defaultPriceHtml;
		}

		if ( imageEl ) {
			imageEl.src = defaultImageSrc;
		}
	} );
}

document
	.querySelectorAll( '.wp-block-noorifa-core-sticky-add-to-cart' )
	.forEach( ( bar ) => {
		const form = findExternalProductForm( bar );

		wireBuyNowButton( bar, form );
		watchVisibility( bar );
		watchVariationSync( bar, form );
	} );
