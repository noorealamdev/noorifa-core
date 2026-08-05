import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<WooPlaceholder
				icon="tag"
				label={ __( 'Product Price', 'noorifa-core' ) }
				instructions={ __(
					"Displays the current product's price. Preview appears on the product page.",
					'noorifa-core'
				) }
			/>
		</div>
	);
}
