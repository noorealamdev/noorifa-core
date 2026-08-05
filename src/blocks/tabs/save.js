import { InnerBlocks } from '@wordpress/block-editor';

// The wrapper and tab list are rendered server-side in render.php;
// only the panels (inner blocks) are saved.
export default function save() {
	return <InnerBlocks.Content />;
}
