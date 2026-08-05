import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<WooPlaceholder
				icon="arrow-right-alt"
				label={ __( 'Product Breadcrumbs', 'noorifa-core' ) }
				instructions={ __(
					'Displays the breadcrumb trail for the current product. Preview appears on the product page.',
					'noorifa-core'
				) }
			/>
		</div>
	);
}
