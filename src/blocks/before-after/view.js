/**
 * Before / After front-end behaviour: drag (mouse/touch/pen, via Pointer
 * Events) or arrow-key the divider to reveal more/less of the "after"
 * image. The "after" image is a full-size layer clipped with `clip-path`
 * (see style.scss) — dragging just updates one CSS custom property.
 */

const BLOCK = 'noorifa-core-before-after';

/**
 * @param {HTMLElement} block    The `.noorifa-core-before-after` element.
 * @param {number}      position 0–100.
 */
function setPosition( block, position ) {
	const clamped = Math.max( 0, Math.min( 100, position ) );
	block.style.setProperty(
		'--noorifa-before-after-position',
		`${ clamped }%`
	);

	const handle = block.querySelector( `.${ BLOCK }__handle-wrap` );
	if ( handle ) {
		handle.setAttribute( 'aria-valuenow', String( Math.round( clamped ) ) );
	}
}

/**
 * @param {HTMLElement} block   The `.noorifa-core-before-after` element.
 * @param {number}      clientX Pointer X, viewport coordinates.
 * @return {number} Position as a 0–100 percentage of the block's width.
 */
function positionFromPointer( block, clientX ) {
	const rect = block.getBoundingClientRect();
	if ( ! rect.width ) {
		return 50;
	}
	return ( ( clientX - rect.left ) / rect.width ) * 100;
}

document.querySelectorAll( `.${ BLOCK }` ).forEach( ( block ) => {
	let dragging = false;

	block.addEventListener( 'pointerdown', ( event ) => {
		dragging = true;
		block.setPointerCapture( event.pointerId );
		setPosition( block, positionFromPointer( block, event.clientX ) );
	} );

	block.addEventListener( 'pointermove', ( event ) => {
		if ( ! dragging ) {
			return;
		}
		setPosition( block, positionFromPointer( block, event.clientX ) );
	} );

	const endDrag = ( event ) => {
		dragging = false;
		if ( block.hasPointerCapture( event.pointerId ) ) {
			block.releasePointerCapture( event.pointerId );
		}
	};
	block.addEventListener( 'pointerup', endDrag );
	block.addEventListener( 'pointercancel', endDrag );

	const handle = block.querySelector( `.${ BLOCK }__handle-wrap` );
	if ( handle ) {
		handle.addEventListener( 'keydown', ( event ) => {
			const current =
				parseFloat( handle.getAttribute( 'aria-valuenow' ) ) || 50;
			let next = current;

			if ( 'ArrowLeft' === event.key || 'ArrowDown' === event.key ) {
				next = current - 5;
			} else if (
				'ArrowRight' === event.key ||
				'ArrowUp' === event.key
			) {
				next = current + 5;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = 100;
			} else {
				return;
			}

			event.preventDefault();
			setPosition( block, next );
		} );
	}
} );
