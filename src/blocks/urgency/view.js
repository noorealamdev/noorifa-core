/*
 * Ticks every rendered countdown timer client-side. The deadline itself is
 * always computed server-side (render.php) from real WooCommerce/merchant
 * data, so this script only ever formats a fixed timestamp — it never
 * invents or resets a deadline on its own.
 */

function formatUnit( value ) {
	return String( Math.max( 0, value ) ).padStart( 2, '0' );
}

function setUnit( timer, unit, value ) {
	const el = timer.querySelector( `[data-unit="${ unit }"]` );

	if ( el ) {
		el.textContent = formatUnit( value );
	}
}

function showExpired( timer, hideWhenExpired, expiredMessage ) {
	const wrapper = timer.closest( '.wp-block-noorifa-core-urgency' );

	if ( hideWhenExpired ) {
		if ( wrapper ) {
			wrapper.style.display = 'none';
		}
		return;
	}

	// Replace the whole inner wrapper's contents (label + timer), not just
	// the timer itself, so the countdown label doesn't linger next to the
	// expired message (e.g. "Sale ends in: This offer has ended.").
	const message = document.createElement( 'span' );
	message.className = 'noorifa-core-urgency__message';
	message.textContent = expiredMessage;

	if ( wrapper ) {
		wrapper.replaceChildren( message );
	} else {
		timer.replaceWith( message );
	}
}

function startCountdown( timer ) {
	const deadline = parseInt( timer.dataset.deadline, 10 );
	const hideWhenExpired = timer.dataset.hideWhenExpired === '1';
	const expiredMessage = timer.dataset.expiredMessage || '';

	if ( Number.isNaN( deadline ) ) {
		return;
	}

	const update = () => {
		const remaining = deadline - Date.now();

		if ( remaining <= 0 ) {
			clearInterval( intervalId );
			showExpired( timer, hideWhenExpired, expiredMessage );
			return;
		}

		setUnit( timer, 'days', Math.floor( remaining / 86400000 ) );
		setUnit( timer, 'hours', Math.floor( remaining / 3600000 ) % 24 );
		setUnit( timer, 'minutes', Math.floor( remaining / 60000 ) % 60 );
		setUnit( timer, 'seconds', Math.floor( remaining / 1000 ) % 60 );
	};

	const intervalId = setInterval( update, 1000 );
	update();
}

document
	.querySelectorAll( '.noorifa-core-urgency__timer[data-deadline]' )
	.forEach( startCountdown );
