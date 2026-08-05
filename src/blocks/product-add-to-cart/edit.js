import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<WooPlaceholder
				icon="cart"
				label={ __( 'Product Add to Cart', 'noorifa-core' ) }
				instructions={ __(
					"Displays the current product's add-to-cart form (quantity, variations, stock). Preview appears on the product page.",
					'noorifa-core'
				) }
			/>
		</div>
	);
}
