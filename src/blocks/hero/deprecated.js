import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';
import { getBackgroundStyle, hasBackgroundVideo, hasOverlay } from './background';

/**
 * Pre-"boxed width" shape: heading/subheading/actions were flat direct
 * children of the block's own wrapper, with no shared content container to
 * attach an `is-boxed` class/max-width to — the `boxed`/`boxedWidth`
 * attributes didn't exist yet. Includes every attribute block supports
 * injects at registration (anchor, className, backgroundColor, textColor,
 * gradient) explicitly — a deprecation does not automatically inherit
 * those the way the main block registration does.
 */
const v1 = {
	attributes: {
		heading: {
			type: 'string',
			source: 'html',
			selector: '.noorifa-core-hero__heading',
			default: 'Your Hero Heading',
		},
		subheading: {
			type: 'string',
			source: 'html',
			selector: '.noorifa-core-hero__subheading',
			default: '',
		},
		backgroundType: { type: 'string', default: 'image' },
		backgroundImage: { type: 'object', default: { url: '', id: 0 } },
		backgroundVideo: { type: 'object', default: { url: '', id: 0 } },
		overlayColor: { type: 'string', default: '#000000' },
		overlayOpacity: { type: 'number', default: 0 },
		textAlign: { type: 'string', default: 'center' },
		headingFontSize: { type: 'string', default: '' },
		subheadingFontSize: { type: 'string', default: '' },
		align: { type: 'string', default: 'full' },
		style: {
			type: 'object',
			default: {
				spacing: {
					padding: {
						top: '5rem',
						right: '1.5rem',
						bottom: '5rem',
						left: '1.5rem',
					},
				},
			},
		},
		anchor: { type: 'string' },
		className: { type: 'string' },
		backgroundColor: { type: 'string' },
		textColor: { type: 'string' },
		gradient: { type: 'string' },
	},
	save( { attributes } ) {
		const {
			heading,
			subheading,
			backgroundType,
			backgroundImage,
			backgroundVideo,
			overlayColor,
			overlayOpacity,
			textAlign,
			headingFontSize,
			subheadingFontSize,
		} = attributes;
		const blockProps = useBlockProps.save( {
			className: textAlign ? `has-text-align-${ textAlign }` : undefined,
			style: getBackgroundStyle(
				backgroundType,
				backgroundImage,
				backgroundVideo,
				overlayOpacity
			),
		} );
		const showVideo = hasBackgroundVideo( backgroundType, backgroundVideo );
		const showOverlay = hasOverlay( overlayOpacity );

		return (
			<div { ...blockProps }>
				{ showVideo && (
					<video
						className="noorifa-core-hero__bg-video"
						src={ backgroundVideo.url }
						autoPlay
						muted
						loop
						playsInline
					/>
				) }
				{ showOverlay && (
					<div
						className="noorifa-core-hero__overlay"
						style={ {
							backgroundColor: overlayColor,
							opacity: overlayOpacity / 100,
						} }
					/>
				) }
				<RichText.Content
					tagName="h1"
					className="noorifa-core-hero__heading"
					style={
						headingFontSize
							? { fontSize: headingFontSize }
							: undefined
					}
					value={ heading }
				/>
				<RichText.Content
					tagName="p"
					className="noorifa-core-hero__subheading"
					style={
						subheadingFontSize
							? { fontSize: subheadingFontSize }
							: undefined
					}
					value={ subheading }
				/>
				<div className="noorifa-core-hero__actions">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
};

export default [ v1 ];
