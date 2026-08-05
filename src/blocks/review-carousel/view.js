function initReviewCarousel( carousel ) {
	const viewport = carousel.querySelector(
		'.noorifa-core-review-carousel__viewport'
	);
	const slides = Array.from(
		carousel.querySelectorAll( '.noorifa-core-review-carousel__slide' )
	);
	const dots = Array.from(
		carousel.querySelectorAll( '.noorifa-core-review-carousel__dot' )
	);
	const prevButton = carousel.querySelector(
		'.noorifa-core-review-carousel__arrow--prev'
	);
	const nextButton = carousel.querySelector(
		'.noorifa-core-review-carousel__arrow--next'
	);

	if ( ! viewport || ! slides.length ) {
		return;
	}

	let activeIndex = 0;
	// The scrollLeft value that would settle each slide into its own
	// scroll-snap-align position (start on wider screens, center on
	// mobile, matching the CSS) — used instead of IntersectionObserver,
	// since several slides are visible at once here and picking "whichever
	// one last reported as intersecting" doesn't reliably mean "the active one".
	let slidePositions = [];

	const measureSlidePositions = () => {
		const viewportRect = viewport.getBoundingClientRect();

		slidePositions = slides.map( ( slide ) => {
			const slideRect = slide.getBoundingClientRect();
			const start = slideRect.left - viewportRect.left + viewport.scrollLeft;

			if ( 'center' === getComputedStyle( slide ).scrollSnapAlign ) {
				return start + slideRect.width / 2 - viewportRect.width / 2;
			}

			return start;
		} );
	};

	const setActiveDot = ( index ) => {
		dots.forEach( ( dot, dotIndex ) => {
			dot.classList.toggle( 'is-active', dotIndex === index );
		} );
	};

	// While a scroll animation triggered by scrollToSlide() is in flight,
	// the scroll-position sync below is suppressed. Near the end of the
	// track there isn't enough scrollable range left for every slide to
	// reach its own exact snap position — the browser just clamps to the
	// max — so without this, syncing from the clamped scroll position
	// could resolve back to an earlier slide than the one actually
	// clicked, making the last dot/arrow look unresponsive.
	let suppressScrollSync = false;
	let suppressTimeout;

	const scrollToSlide = ( index ) => {
		const clamped = Math.max( 0, Math.min( slides.length - 1, index ) );
		activeIndex = clamped;
		setActiveDot( clamped );

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

	dots.forEach( ( dot, index ) => {
		dot.addEventListener( 'click', () => scrollToSlide( index ) );
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
			setActiveDot( closestIndex );
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
	 * momentum/fling physics, which a hand-rolled version can't match), so
	 * only mouse input gets a manual click-and-drag handler here — desktop
	 * has no touchpad-swipe equivalent by default, so without this the
	 * carousel is only reachable via the arrow buttons and dots.
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

	// Swallows the click a drag gesture ends on (e.g. releasing over a
	// card), so dragging the carousel doesn't also trigger it.
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

	measureSlidePositions();
}

document
	.querySelectorAll( '.noorifa-core-review-carousel' )
	.forEach( initReviewCarousel );
