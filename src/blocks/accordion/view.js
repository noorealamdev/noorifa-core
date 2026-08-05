import { store, getContext } from '@wordpress/interactivity';

store( 'noorifa-core/accordion', {
	state: {
		get isOpen() {
			const { uid, open } = getContext();
			return open.ids.includes( uid );
		},
	},
	actions: {
		toggle() {
			const { uid, open, allowMultiple } = getContext();
			const index = open.ids.indexOf( uid );

			if ( index > -1 ) {
				open.ids.splice( index, 1 );
				return;
			}

			if ( ! allowMultiple ) {
				open.ids.splice( 0, open.ids.length );
			}

			open.ids.push( uid );
		},
	},
} );
