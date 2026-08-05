import { Placeholder } from '@wordpress/components';

/**
 * Static editor placeholder for blocks that wrap a WooCommerce template
 * function. These blocks have no meaningful preview outside of an actual
 * product page, so the editor shows a label instead of attempting a live
 * render.
 *
 * @param {Object} props              Component props.
 * @param {string} props.icon         Dashicon slug.
 * @param {string} props.label        Placeholder title.
 * @param {string} props.instructions Placeholder description.
 */
export default function WooPlaceholder( { icon, label, instructions } ) {
	return (
		<Placeholder
			icon={ icon }
			label={ label }
			instructions={ instructions }
		/>
	);
}
