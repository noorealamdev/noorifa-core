import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';
import metadata from './block.json';

/**
 * Pre-"boxed width" shape: the accordion wrapper had no `is-boxed` class or
 * inline max-width, since the `boxed`/`boxedWidth` attributes didn't exist
 * yet. Without this, every FAQ block saved before that change would show as
 * invalid content the next time its post is opened in the editor.
 *
 * A deprecation entry does NOT automatically inherit the attributes block
 * supports injects at registration time (textColor, backgroundColor,
 * gradient, fontSize, anchor) — those only get added to the block's own
 * top-level `attributes` when the real block registers, never to a
 * deprecation's schema, whether declared explicitly or left off entirely.
 * Omitting `attributes` here previously behaved as if none of these
 * existed at all, which is exactly as broken as the hand-picked list this
 * replaced (both silently failed to parse a real textColor value off of
 * already-saved content). They have to be listed explicitly.
 */
const v1 = {
	attributes: {
		...metadata.attributes,
		textColor: { type: 'string' },
		backgroundColor: { type: 'string' },
		gradient: { type: 'string' },
		fontSize: { type: 'string' },
		anchor: { type: 'string' },
	},
	save( { attributes } ) {
		const { title } = attributes;
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<RichText.Content
					tagName="h2"
					className="noorifa-core-faq__title"
					value={ title }
				/>
				<div className="noorifa-core-faq__accordion">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
};

export default [ v1 ];
