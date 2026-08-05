import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
} from '@wordpress/block-editor';

import useUniqueId from '../../utils/use-unique-id';

const TEMPLATE = [ [ 'core/paragraph' ] ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { title, uid } = attributes;

	useUniqueId( clientId, uid, setAttributes );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'noorifa-core-accordion__panel' },
		{ template: TEMPLATE }
	);

	return (
		<div { ...blockProps }>
			<div className="noorifa-core-accordion__heading">
				<div className="noorifa-core-accordion__toggle">
					<RichText
						tagName="span"
						className="noorifa-core-accordion__title"
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __( 'Add a title…', 'noorifa-core' ) }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
					/>
					<span
						className="noorifa-core-accordion__icon"
						aria-hidden="true"
					></span>
				</div>
			</div>
			<div { ...innerBlocksProps } />
		</div>
	);
}
