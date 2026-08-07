/**
 * Inline Checkout front-end behaviour: package selection, per-package
 * quantity steppers, a live order summary, and the AJAX order submission
 * that creates a real WooCommerce COD order (handled server-side by
 * \Noorifa\Core\Blocks\Inline_Checkout) then redirects to order-received.
 */

/*
 * Mirrors WooCommerce's own price formatting (symbol position, decimal and
 * thousand separators) so JS-updated totals match the server-rendered ones
 * exactly. The formatting parameters are read from the wrapper's data-*
 * attributes, populated in render.php from WooCommerce's settings.
 */
function formatMoney( amount, opts ) {
	const negative = amount < 0;
	const fixed = Math.abs( amount ).toFixed( opts.decimals );
	const parts = fixed.split( '.' );

	parts[ 0 ] = parts[ 0 ].replace(
		/\B(?=(\d{3})+(?!\d))/g,
		opts.thousandSep
	);

	const number =
		parts.length > 1
			? parts[ 0 ] + opts.decimalSep + parts[ 1 ]
			: parts[ 0 ];

	let out;
	switch ( opts.pos ) {
		case 'right':
			out = number + opts.symbol;
			break;
		case 'left_space':
			out = opts.symbol + ' ' + number;
			break;
		case 'right_space':
			out = number + ' ' + opts.symbol;
			break;
		default:
			out = opts.symbol + number;
	}

	return ( negative ? '-' : '' ) + out;
}

function getMoneyOpts( bar ) {
	return {
		symbol: bar.dataset.symbol || '',
		pos: bar.dataset.pos || 'left',
		decimals: parseInt( bar.dataset.decimals, 10 ) || 0,
		decimalSep: bar.dataset.decimalSep || '.',
		thousandSep: bar.dataset.thousandSep || ',',
	};
}

function getSelectedPackage( bar ) {
	const radio = bar.querySelector(
		'.noorifa-core-inline-checkout__package-radio:checked'
	);

	if ( ! radio ) {
		return null;
	}

	const label = radio.closest( '.noorifa-core-inline-checkout__package' );
	const qtyInput = label
		? label.querySelector( '.noorifa-core-inline-checkout__qty-input' )
		: null;
	const quantity = qtyInput
		? Math.max( 1, parseInt( qtyInput.value, 10 ) || 1 )
		: 1;

	return {
		radio,
		label,
		productId: parseInt( radio.dataset.productId, 10 ),
		price: parseFloat( radio.dataset.price ) || 0,
		name: radio.dataset.name || '',
		image: radio.dataset.image || '',
		quantity,
	};
}

function setText( bar, role, text ) {
	const el = bar.querySelector( `[data-role="${ role }"]` );
	if ( el ) {
		el.textContent = text;
	}
}

function updateSummary( bar ) {
	const pkg = getSelectedPackage( bar );
	if ( ! pkg ) {
		return;
	}

	const opts = getMoneyOpts( bar );
	const line = pkg.price * pkg.quantity;
	const money = formatMoney( line, opts );

	const thumb = bar.querySelector( '[data-role="thumb"]' );
	if ( thumb && pkg.image ) {
		thumb.src = pkg.image;
	}

	setText( bar, 'name', pkg.name );
	setText( bar, 'qty', String( pkg.quantity ) );
	setText( bar, 'line', money );
	setText( bar, 'subtotal', money );
	setText( bar, 'total', money );
	setText( bar, 'button-total', money );
}

function selectPackage( bar, label ) {
	bar.querySelectorAll( '.noorifa-core-inline-checkout__package' ).forEach(
		( el ) => el.classList.toggle( 'is-selected', el === label )
	);

	const radio = label.querySelector(
		'.noorifa-core-inline-checkout__package-radio'
	);
	if ( radio ) {
		radio.checked = true;
	}

	updateSummary( bar );
}

function fieldLabelText( field ) {
	const label = field.querySelector( '.noorifa-core-inline-checkout__label' );
	if ( ! label ) {
		return '';
	}
	// Strip the required asterisk so the message reads "<label> is required".
	return label.textContent.replace( /\*/g, '' ).trim();
}

function setFieldError( field, message ) {
	const box = field.querySelector( '.noorifa-core-inline-checkout__error' );
	field.classList.toggle(
		'noorifa-core-inline-checkout__field--error',
		!! message
	);
	if ( box ) {
		box.textContent = message || '';
	}
}

function validateField( field ) {
	const input = field.querySelector( '.noorifa-core-inline-checkout__input' );
	if ( ! input ) {
		return true;
	}

	const value = input.value.trim();
	const name = fieldLabelText( field );

	if ( ! value ) {
		setFieldError( field, `${ name } is required` );
		return false;
	}

	if ( 'phone' === field.dataset.field && ! /^01\d{9}$/.test( value ) ) {
		setFieldError( field, 'Enter a valid 11 digit phone number' );
		return false;
	}

	setFieldError( field, '' );
	return true;
}

function wireQtySteppers( bar ) {
	bar.querySelectorAll( '.noorifa-core-inline-checkout__qty-btn' ).forEach(
		( btn ) => {
			btn.addEventListener( 'click', ( event ) => {
				// Keeps the click from also toggling the wrapping <label>'s
				// radio in an unexpected way; selection is handled explicitly.
				event.preventDefault();
				event.stopPropagation();

				const label = btn.closest(
					'.noorifa-core-inline-checkout__package'
				);
				const input = label.querySelector(
					'.noorifa-core-inline-checkout__qty-input'
				);
				const step = parseInt( btn.dataset.step, 10 ) || 0;
				const next = Math.max(
					1,
					( parseInt( input.value, 10 ) || 1 ) + step
				);
				input.value = next;

				selectPackage( bar, label );
			} );
		}
	);

	bar.querySelectorAll( '.noorifa-core-inline-checkout__qty-input' ).forEach(
		( input ) => {
			input.addEventListener( 'click', ( event ) =>
				event.stopPropagation()
			);
			input.addEventListener( 'input', () => {
				const label = input.closest(
					'.noorifa-core-inline-checkout__package'
				);
				selectPackage( bar, label );
			} );
			input.addEventListener( 'blur', () => {
				if ( ! input.value || parseInt( input.value, 10 ) < 1 ) {
					input.value = 1;
					updateSummary( bar );
				}
			} );
		}
	);
}

function wirePackageSelection( bar ) {
	bar.querySelectorAll(
		'.noorifa-core-inline-checkout__package-radio'
	).forEach( ( radio ) => {
		radio.addEventListener( 'change', () => {
			const label = radio.closest(
				'.noorifa-core-inline-checkout__package'
			);
			selectPackage( bar, label );
		} );
	} );
}

function wireSubmit( bar ) {
	const form = bar.querySelector( '.noorifa-core-inline-checkout__form' );
	const button = bar.querySelector( '.noorifa-core-inline-checkout__submit' );

	if ( ! form || ! button ) {
		return;
	}

	const formError = bar.querySelector(
		'.noorifa-core-inline-checkout__form-error'
	);

	// Clear a field's error as soon as the shopper starts fixing it.
	form.querySelectorAll( '.noorifa-core-inline-checkout__input' ).forEach(
		( input ) => {
			input.addEventListener( 'input', () => {
				const field = input.closest(
					'.noorifa-core-inline-checkout__field'
				);
				if (
					field &&
					field.classList.contains(
						'noorifa-core-inline-checkout__field--error'
					)
				) {
					validateField( field );
				}
			} );
		}
	);

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		formError.textContent = '';

		const fields = Array.from(
			form.querySelectorAll( '.noorifa-core-inline-checkout__field' )
		);
		let valid = true;
		let firstInvalid = null;

		fields.forEach( ( field ) => {
			if ( ! validateField( field ) ) {
				valid = false;
				if ( ! firstInvalid ) {
					firstInvalid = field;
				}
			}
		} );

		const pkg = getSelectedPackage( bar );
		if ( ! pkg || ! pkg.productId ) {
			formError.textContent =
				'Please select a package before placing your order.';
			return;
		}

		if ( ! valid ) {
			const input = firstInvalid.querySelector(
				'.noorifa-core-inline-checkout__input'
			);
			if ( input ) {
				input.focus();
			}
			return;
		}

		const body = new URLSearchParams();
		body.append( 'nonce', bar.dataset.nonce || '' );
		body.append( 'product_id', String( pkg.productId ) );
		body.append( 'quantity', String( pkg.quantity ) );
		body.append(
			'name',
			form.querySelector( '[name="noorifa_ic_name"]' ).value.trim()
		);
		body.append(
			'address',
			form.querySelector( '[name="noorifa_ic_address"]' ).value.trim()
		);
		body.append(
			'phone',
			form.querySelector( '[name="noorifa_ic_phone"]' ).value.trim()
		);

		button.disabled = true;
		button.classList.add( 'is-loading' );

		fetch( bar.dataset.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: body.toString(),
		} )
			.then( ( response ) => response.json() )
			.then( ( result ) => {
				if ( result && result.success && result.data.redirect ) {
					window.location.href = result.data.redirect;
					return;
				}

				const message =
					( result && result.data && result.data.message ) ||
					'Something went wrong. Please try again.';
				formError.textContent = message;
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			} )
			.catch( () => {
				formError.textContent =
					'Network error. Please check your connection and try again.';
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			} );
	} );
}

document
	.querySelectorAll( '.noorifa-core-inline-checkout' )
	.forEach( ( bar ) => {
		wirePackageSelection( bar );
		wireQtySteppers( bar );
		wireSubmit( bar );
		updateSummary( bar );
	} );
