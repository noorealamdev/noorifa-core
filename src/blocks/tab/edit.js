import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import useUniqueId from '../../utils/use-unique-id';

const TEMPLATE = [ [ 'core/paragraph' ] ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { uid } = attributes;

	useUniqueId( clientId, uid, setAttributes );

	// A tab is shown when it (or its content) is selected; with no
	// selection anywhere inside the Tabs block, the first tab is shown.
	const isActive = useSelect(
		( select ) => {
			const {
				isBlockSelected,
				hasSelectedInnerBlock,
				getBlockRootClientId,
				getBlockOrder,
			} = select( 'core/block-editor' );

			if (
				isBlockSelected( clientId ) ||
				hasSelectedInnerBlock( clientId, true )
			) {
				return true;
			}

			const parentId = getBlockRootClientId( clientId );
			const siblings = getBlockOrder( parentId );
			const anySelected = siblings.some(
				( id ) =>
					isBlockSelected( id ) || hasSelectedInnerBlock( id, true )
			);

			return ! anySelected && siblings[ 0 ] === clientId;
		},
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: isActive ? 'is-active' : undefined,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	return <div { ...innerBlocksProps } />;
}
