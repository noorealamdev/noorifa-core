import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { title, uid } = attributes;
	const panelId = `noorifa-core-accordion-panel-${ uid }`;

	const blockProps = useBlockProps.save( {
		'data-wp-context': JSON.stringify( { uid } ),
	} );

	return (
		<div { ...blockProps }>
			<h3 className="noorifa-core-accordion__heading">
				<button
					type="button"
					className="noorifa-core-accordion__toggle"
					aria-controls={ panelId }
					aria-expanded="false"
					data-wp-on--click="actions.toggle"
					data-wp-bind--aria-expanded="state.isOpen"
					data-wp-class--is-open="state.isOpen"
				>
					<RichText.Content
						tagName="span"
						className="noorifa-core-accordion__title"
						value={ title }
					/>
					<span
						className="noorifa-core-accordion__icon"
						aria-hidden="true"
					></span>
				</button>
			</h3>
			<div
				id={ panelId }
				className="noorifa-core-accordion__panel"
				hidden
				data-wp-bind--hidden="!state.isOpen"
			>
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
