/**
 * Video Testimonials front-end behaviour.
 *
 * Two responsibilities:
 *  1. Lazy video: each card renders as a poster + play button (a "facade").
 *     The actual YouTube/Vimeo iframe or <video> element is only created on
 *     click, so no video player JS/network loads on page load — this keeps
 *     the block cheap even with many testimonials on the page.
 *  2. Carousel: when the block is in carousel mode, a self-contained
 *     scroll-snap carousel (arrows, dots, mouse drag) — no external library.
 */

const BLOCK = 'noorifa-core-video-testimonials';

/**
 * Builds an embeddable, autoplaying URL from a YouTube/Vimeo watch URL.
 *
 * @param {string} url Raw video URL.
 * @return {string|null} Embed URL, or null if unrecognised.
 */
function toEmbedUrl( url ) {
	if ( ! url ) {
		return null;
	}

	const yt = url.match(
		/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/
	);
	if ( yt ) {
		return `https://www.youtube.com/embed/${ yt[ 1 ] }?autoplay=1&rel=0`;
	}

	const vimeo = url.match( /vimeo\.com\/(?:video\/)?(\d+)/ );
	if ( vimeo ) {
		return `https://player.vimeo.com/video/${ vimeo[ 1 ] }?autoplay=1`;
	}

	return null;
}

/**
 * Swaps a poster facade for the real, playing video.
 *
 * @param {HTMLElement} button The `.__media` facade button that was clicked.
 */
function playVideo( button ) {
	const type = button.getAttribute( 'data-video-type' );
	let player;

	if ( 'upload' === type ) {
		const src = button.getAttribute( 'data-video-src' );
		if ( ! src ) {
			return;
		}
		player = document.createElement( 'video' );
		player.src = src;
		player.controls = true;
		player.autoplay = true;
		player.playsInline = true;
		player.className = `${ BLOCK }__player`;
	} else {
		const embed = toEmbedUrl( button.getAttribute( 'data-video-url' ) );
		if ( ! embed ) {
			// Unrecognised URL — open it in a new tab rather than fail silently.
			const href = button.getAttribute( 'data-video-url' );
			if ( href ) {
				window.open( href, '_blank', 'noopener' );
			}
			return;
		}
		player = document.createElement( 'iframe' );
		player.src = embed;
		player.className = `${ BLOCK }__player`;
		player.setAttribute( 'frameborder', '0' );
		player.setAttribute(
			'allow',
			'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
		);
		player.allowFullscreen = true;
	}

	const holder = document.createElement( 'div' );
	holder.className = `${ BLOCK }__media is-playing`;
	holder.appendChild( player );
	button.replaceWith( holder );
}

/**
 * Wires the scroll-snap carousel controls for one block instance.
 *
 * @param {HTMLElement} block The `.noorifa-core-video-testimonials.is-carousel` element.
 */
function initCarousel( block ) {
	const viewport = block.querySelector( `.${ BLOCK }__viewport` );
	const slides = Array.from( block.querySelectorAll( `.${ BLOCK }__item` ) );

	if ( ! viewport || slides.length < 2 ) {
		return;
	}

	const prevButton = block.querySelector( `.${ BLOCK }__arrow--prev` );
	const nextButton = block.querySelector( `.${ BLOCK }__arrow--next` );

	const track = viewport.querySelector( `.${ BLOCK }__track` );

	const scrollByCard = ( direction ) => {
		const first = slides[ 0 ];
		const gap = track
			? parseFloat( getComputedStyle( track ).columnGap ) || 24
			: 24;
		const step = first.getBoundingClientRect().width + gap;
		viewport.scrollBy( { left: step * direction, behavior: 'smooth' } );
	};

	if ( prevButton ) {
		prevButton.addEventListener( 'click', () => scrollByCard( -1 ) );
	}
	if ( nextButton ) {
		nextButton.addEventListener( 'click', () => scrollByCard( 1 ) );
	}

	// Mouse click-and-drag (touch already scrolls natively with momentum).
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
	};

	viewport.addEventListener( 'pointerup', endDrag );
	viewport.addEventListener( 'pointercancel', endDrag );

	// Swallow the click that ends a drag, so dragging doesn't also trigger
	// a card's play button.
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
}

document.querySelectorAll( `.${ BLOCK }` ).forEach( ( block ) => {
	block.querySelectorAll( `.${ BLOCK }__media` ).forEach( ( button ) => {
		button.addEventListener( 'click', () => playVideo( button ) );
	} );

	if ( block.classList.contains( 'is-carousel' ) ) {
		initCarousel( block );
	}
} );
