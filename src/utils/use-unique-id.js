import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Keeps a block's `uid` attribute set and unique among its siblings.
 *
 * The uid is persisted in the saved markup (for element ids and
 * Interactivity API context), so it must survive block duplication:
 * when two siblings share a uid, each resets it to its own clientId.
 *
 * @param {string}   clientId      Block client id.
 * @param {string}   uid           Current uid attribute.
 * @param {Function} setAttributes Block setAttributes.
 */
export default function useUniqueId( clientId, uid, setAttributes ) {
	const isDuplicate = useSelect(
		( select ) => {
			const { getBlocks, getBlockRootClientId } =
				select( 'core/block-editor' );

			return getBlocks( getBlockRootClientId( clientId ) ).some(
				( sibling ) =>
					sibling.clientId !== clientId &&
					sibling.attributes.uid === uid
			);
		},
		[ clientId, uid ]
	);

	useEffect( () => {
		if ( ! uid || isDuplicate ) {
			setAttributes( { uid: clientId } );
		}
	}, [ uid, isDuplicate, clientId, setAttributes ] );
}
