import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'noorifa-core/tabs', {
	state: {
		get isSelected() {
			const { tabId, active } = getContext();
			return active.id === tabId;
		},
		get tabIndex() {
			const { tabId, active } = getContext();
			return active.id === tabId ? 0 : -1;
		},
	},
	actions: {
		activate() {
			const { tabId, active } = getContext();
			active.id = tabId;
		},
		handleKeydown( event ) {
			const { key } = event;

			if ( key !== 'ArrowLeft' && key !== 'ArrowRight' ) {
				return;
			}

			event.preventDefault();

			const { ref } = getElement();
			const tabs = Array.from( ref.querySelectorAll( '[role="tab"]' ) );
			const current = tabs.indexOf(
				event.target.closest( '[role="tab"]' )
			);

			if ( current === -1 ) {
				return;
			}

			const delta = key === 'ArrowRight' ? 1 : -1;
			const next =
				tabs[ ( current + delta + tabs.length ) % tabs.length ];

			next.focus();
			next.click();
		},
	},
} );
