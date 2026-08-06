import { useBlockProps, InnerBlocks, RichText } from '@wordpress/block-editor';
import {
	getBackgroundStyle,
	hasBackgroundVideo,
	hasOverlay,
} from './background';

export default function save( { attributes } ) {
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
		headingLineHeight,
		subheadingLineHeight,
		boxed,
		boxedWidth,
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
			<div
				className={
					'noorifa-core-hero__content' +
					( boxed ? ' is-boxed' : '' )
				}
				style={ boxed ? { maxWidth: boxedWidth } : undefined }
			>
				<RichText.Content
					tagName="h1"
					className="noorifa-core-hero__heading"
					style={ {
						...( headingFontSize && { fontSize: headingFontSize } ),
						...( headingLineHeight && { lineHeight: headingLineHeight } ),
					} }
					value={ heading }
				/>
				<RichText.Content
					tagName="p"
					className="noorifa-core-hero__subheading"
					style={ {
						...( subheadingFontSize && { fontSize: subheadingFontSize } ),
						...( subheadingLineHeight && { lineHeight: subheadingLineHeight } ),
					} }
					value={ subheading }
				/>
				<div className="noorifa-core-hero__actions">
					<InnerBlocks.Content />
				</div>
			</div>
		</div>
	);
}
