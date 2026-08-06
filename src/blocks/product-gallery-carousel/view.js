function initGalleryCarousel( carousel ) {
	const viewport = carousel.querySelector(
		'.noorifa-core-product-gallery-carousel__viewport'
	);
	const slides = Array.from(
		carousel.querySelectorAll( '.noorifa-core-product-gallery-carousel__slide' )
	);
	const navItems = Array.from(
		carousel.querySelectorAll(
			'.noorifa-core-product-gallery-carousel__dot, .noorifa-core-product-gallery-carousel__thumb'
		)
	);
	const prevButton = carousel.querySelector(
		'.noorifa-core-product-gallery-carousel__arrow--prev'
	);
	const nextButton = carousel.querySelector(
		'.noorifa-core-product-gallery-carousel__arrow--next'
	);

	if ( ! viewport || ! slides.length ) {
		return;
	}

	let activeIndex = 0;
	// The scrollLeft value that puts each slide flush against the
	// viewport's left edge.
	let slidePositions = [];

	const measureSlidePositions = () => {
		const viewportRect = viewport.getBoundingClientRect();

		slidePositions = slides.map( ( slide ) => {
			const slideRect = slide.getBoundingClientRect();
			return slideRect.left - viewportRect.left + viewport.scrollLeft;
		} );
	};

	const setActiveNav = ( index ) => {
		navItems.forEach( ( item ) => {
			const isActive = Number( item.getAttribute( 'data-slide-index' ) ) === index;
			item.classList.toggle( 'is-active', isActive );

			// Keeps the thumbnail strip in sync with the main gallery: as the
			// active image changes (swipe, arrows, or a WC variation swap),
			// its thumbnail scrolls into view on its own — nobody has to
			// manually drag the strip to reach a thumbnail further along.
			if ( isActive ) {
				item.scrollIntoView( {
					behavior: 'smooth',
					block: 'nearest',
					inline: 'nearest',
				} );
			}
		} );
	};

	// Suppresses the scroll-position sync below while a scrollToSlide()
	// animation is in flight — near the end of the track there isn't
	// enough scrollable range left for the last slide to reach its own
	// exact snap position, so the browser just clamps to the max, which
	// could otherwise resolve the "active" state back to an earlier slide.
	let suppressScrollSync = false;
	let suppressTimeout;

	const scrollToSlide = ( index ) => {
		const clamped = Math.max( 0, Math.min( slides.length - 1, index ) );
		activeIndex = clamped;
		setActiveNav( clamped );

		suppressScrollSync = true;
		clearTimeout( suppressTimeout );
		suppressTimeout = setTimeout( () => {
			suppressScrollSync = false;
		}, 600 );

		viewport.scrollTo( { left: slidePositions[ clamped ], behavior: 'smooth' } );
	};

	if ( prevButton ) {
		prevButton.addEventListener( 'click', () =>
			scrollToSlide( activeIndex - 1 )
		);
	}

	if ( nextButton ) {
		nextButton.addEventListener( 'click', () =>
			scrollToSlide( activeIndex + 1 )
		);
	}

	navItems.forEach( ( item ) => {
		item.addEventListener( 'click', () =>
			scrollToSlide( Number( item.getAttribute( 'data-slide-index' ) ) )
		);
	} );

	const updateActiveFromScroll = () => {
		const scrollLeft = viewport.scrollLeft;
		let closestIndex = 0;
		let closestDistance = Infinity;

		slidePositions.forEach( ( position, index ) => {
			const distance = Math.abs( position - scrollLeft );

			if ( distance < closestDistance ) {
				closestDistance = distance;
				closestIndex = index;
			}
		} );

		if ( closestIndex !== activeIndex ) {
			activeIndex = closestIndex;
			setActiveNav( closestIndex );
		}
	};

	let scrollTimeout;
	viewport.addEventListener( 'scroll', () => {
		clearTimeout( scrollTimeout );
		scrollTimeout = setTimeout( () => {
			if ( ! suppressScrollSync ) {
				updateActiveFromScroll();
			}
		}, 100 );
	} );

	let resizeTimeout;
	window.addEventListener( 'resize', () => {
		clearTimeout( resizeTimeout );
		resizeTimeout = setTimeout( measureSlidePositions, 150 );
	} );

	/*
	 * Touch already scrolls the viewport natively (with the browser's own
	 * momentum/fling physics), so only mouse input gets a manual
	 * click-and-drag handler — desktop has no touchpad-swipe equivalent by
	 * default.
	 */
	let isDragging = false;
	let dragMoved = false;
	let dragStartX = 0;
	let dragStartScrollLeft = 0;

	viewport.addEventListener( 'pointerdown', ( event ) => {
		if ( 'mouse' !== event.pointerType ) {
			return;
		}

		isDragging = true;
		dragMoved = false;
		dragStartX = event.clientX;
		dragStartScrollLeft = viewport.scrollLeft;
		viewport.classList.add( 'is-dragging' );
		viewport.setPointerCapture( event.pointerId );
	} );

	viewport.addEventListener( 'pointermove', ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		const delta = event.clientX - dragStartX;

		if ( Math.abs( delta ) > 3 ) {
			dragMoved = true;
		}

		viewport.scrollLeft = dragStartScrollLeft - delta;
	} );

	const endDrag = ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		isDragging = false;
		viewport.classList.remove( 'is-dragging' );

		if ( viewport.hasPointerCapture( event.pointerId ) ) {
			viewport.releasePointerCapture( event.pointerId );
		}

		updateActiveFromScroll();
		scrollToSlide( activeIndex );
	};

	viewport.addEventListener( 'pointerup', endDrag );
	viewport.addEventListener( 'pointercancel', endDrag );

	// Swallows the click a drag gesture ends on, so dragging the carousel
	// doesn't also trigger a click (e.g. a lightbox) underneath it.
	viewport.addEventListener(
		'click',
		( event ) => {
			if ( dragMoved ) {
				event.preventDefault();
				event.stopPropagation();
				dragMoved = false;
			}
		},
		true
	);

	/*
	 * Keeps the carousel in sync with WooCommerce's own variation
	 * selection. WC's add-to-cart-variation.js triggers 'found_variation'
	 * (with the matched variation's data) and 'reset_data' as jQuery
	 * custom events on the form — not native DOM CustomEvents — so a plain
	 * addEventListener can't see them; jQuery (always present alongside
	 * WooCommerce) is required here.
	 */
	if ( window.jQuery ) {
		window.jQuery( document ).on(
			'found_variation',
			'form.variations_form',
			( event, variation ) => {
				const imageId = variation && variation.image_id;
				const index = imageId
					? slides.findIndex(
							( slide ) =>
								Number( slide.dataset.imageId ) === Number( imageId )
					  )
					: -1;

				scrollToSlide( -1 === index ? 0 : index );
			}
		);

		window.jQuery( document ).on( 'reset_data', 'form.variations_form', () => {
			scrollToSlide( 0 );
		} );
	}

	measureSlidePositions();
}

document
	.querySelectorAll( '.noorifa-core-product-gallery-carousel' )
	.forEach( initGalleryCarousel );
