/*
 * Touch already scrolls any overflow-x:auto element natively (with the
 * browser's own momentum/fling physics), so this only wires up mouse
 * click-and-drag — desktop has no touchpad-swipe equivalent by default.
 *
 * Pointer capture is deliberately NOT taken on pointerdown — capturing
 * before any movement retargets the resulting native "click" event away
 * from the actually-pressed element (e.g. a thumbnail <button>), which
 * silently breaks plain taps/clicks that never move. Capture is only
 * grabbed once real dragging past a small threshold is confirmed.
 */
function enablePointerDragScroll( el, onDragEnd ) {
	let isDragging = false;
	let dragMoved = false;
	let dragStartX = 0;
	let dragStartScrollLeft = 0;
	let pointerId = null;

	el.addEventListener( 'pointerdown', ( event ) => {
		if ( 'mouse' !== event.pointerType ) {
			return;
		}

		isDragging = true;
		dragMoved = false;
		dragStartX = event.clientX;
		dragStartScrollLeft = el.scrollLeft;
		pointerId = event.pointerId;
	} );

	el.addEventListener( 'pointermove', ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		const delta = event.clientX - dragStartX;

		if ( ! dragMoved && Math.abs( delta ) > 3 ) {
			dragMoved = true;
			el.classList.add( 'is-dragging' );
			el.setPointerCapture( pointerId );
		}

		if ( dragMoved ) {
			el.scrollLeft = dragStartScrollLeft - delta;
		}
	} );

	const endDrag = ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		isDragging = false;
		el.classList.remove( 'is-dragging' );

		if ( el.hasPointerCapture( event.pointerId ) ) {
			el.releasePointerCapture( event.pointerId );
		}

		if ( dragMoved && onDragEnd ) {
			onDragEnd();
		}
	};

	el.addEventListener( 'pointerup', endDrag );
	el.addEventListener( 'pointercancel', endDrag );

	// Swallows the click a drag gesture ends on, so dragging doesn't also
	// trigger a click (e.g. a thumbnail button, or a lightbox) underneath it.
	el.addEventListener(
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
}

/*
 * No native-scroll/pointer-drag physics — a plain WordPress theme with no
 * carousel library at all. Behaviourally equivalent to the Swiper path
 * below (arrows, thumbs, dots, WC variation sync) just without Swiper's
 * momentum/easing.
 */
function initNativeCarousel(
	carousel,
	viewport,
	thumbsStrip,
	slides,
	navItems,
	prevButton,
	nextButton
) {
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
			const isActive =
				Number( item.getAttribute( 'data-slide-index' ) ) === index;
			item.classList.toggle( 'is-active', isActive );

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

		viewport.scrollTo( {
			left: slidePositions[ clamped ],
			behavior: 'smooth',
		} );
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

	enablePointerDragScroll( viewport, () => {
		updateActiveFromScroll();
		scrollToSlide( activeIndex );
	} );

	if ( thumbsStrip ) {
		enablePointerDragScroll( thumbsStrip );
	}

	measureSlidePositions();

	return {
		goToSlide: scrollToSlide,
		getSlideIndexForImageId: ( imageId ) =>
			slides.findIndex(
				( slide ) =>
					Number( slide.dataset.imageId ) === Number( imageId )
			),
	};
}

/*
 * Swiper.js is already loaded by this theme (used for the review
 * carousel), so reusing it here costs nothing extra and gives real
 * momentum/easing instead of hand-rolled drag physics — matching the
 * design reference exactly (main + thumbs Swiper linked via the Thumbs
 * module). A different theme without Swiper falls back to
 * initNativeCarousel() above, so the block still works everywhere.
 */
function initSwiperCarousel(
	carousel,
	viewport,
	thumbsStrip,
	slides,
	navItems,
	prevButton,
	nextButton
) {
	let thumbsSwiper = null;

	if ( thumbsStrip ) {
		thumbsSwiper = new window.Swiper( thumbsStrip, {
			slidesPerView: 'auto',
			spaceBetween: 8,
			freeMode: true,
			watchSlidesProgress: true,
			grabCursor: true,
		} );
	}

	const mainSwiper = new window.Swiper( viewport, {
		grabCursor: true,
		navigation: {
			nextEl: nextButton,
			prevEl: prevButton,
		},
		thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
	} );

	const setActiveNav = ( index ) => {
		navItems.forEach( ( item ) => {
			item.classList.toggle(
				'is-active',
				Number( item.getAttribute( 'data-slide-index' ) ) === index
			);
		} );
	};

	mainSwiper.on( 'slideChange', () =>
		setActiveNav( mainSwiper.activeIndex )
	);

	// Dots only render when the thumbnail strip is switched off in the
	// block's settings, so they need their own click-to-navigate — thumbs
	// already get this for free from Swiper's Thumbs module.
	if ( ! thumbsSwiper ) {
		navItems.forEach( ( item ) => {
			item.addEventListener( 'click', () =>
				mainSwiper.slideTo(
					Number( item.getAttribute( 'data-slide-index' ) )
				)
			);
		} );
	}

	return {
		goToSlide: ( index ) => mainSwiper.slideTo( index ),
		getSlideIndexForImageId: ( imageId ) =>
			slides.findIndex(
				( slide ) =>
					Number( slide.dataset.imageId ) === Number( imageId )
			),
	};
}

function initGalleryCarousel( carousel ) {
	const viewport = carousel.querySelector(
		'.noorifa-core-product-gallery-carousel__viewport'
	);
	const slides = Array.from(
		carousel.querySelectorAll(
			'.noorifa-core-product-gallery-carousel__slide'
		)
	);

	if ( ! viewport || ! slides.length ) {
		return;
	}

	const thumbsStrip = carousel.querySelector(
		'.noorifa-core-product-gallery-carousel__thumbs'
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

	const controller = window.Swiper
		? initSwiperCarousel(
				carousel,
				viewport,
				thumbsStrip,
				slides,
				navItems,
				prevButton,
				nextButton
		  )
		: initNativeCarousel(
				carousel,
				viewport,
				thumbsStrip,
				slides,
				navItems,
				prevButton,
				nextButton
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
		window
			.jQuery( document )
			.on(
				'found_variation',
				'form.variations_form',
				( event, variation ) => {
					const imageId = variation && variation.image_id;
					const index = imageId
						? controller.getSlideIndexForImageId( imageId )
						: -1;

					controller.goToSlide( -1 === index ? 0 : index );
				}
			);

		window
			.jQuery( document )
			.on( 'reset_data', 'form.variations_form', () => {
				controller.goToSlide( 0 );
			} );
	}
}

/*
 * The theme enqueues Swiper as a separate, independently-ordered script,
 * so `window.Swiper` isn't guaranteed to exist yet the instant this file
 * runs. A short bounded wait (not a hard dependency) keeps this block
 * portable to themes that don't ship Swiper at all — see
 * initNativeCarousel() above.
 */
function waitForSwiperThenInit( attemptsLeft ) {
	if ( ! window.Swiper && attemptsLeft > 0 ) {
		setTimeout( () => waitForSwiperThenInit( attemptsLeft - 1 ), 50 );
		return;
	}

	document
		.querySelectorAll( '.noorifa-core-product-gallery-carousel' )
		.forEach( initGalleryCarousel );
}

waitForSwiperThenInit( 20 );
