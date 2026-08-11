import { useBlockProps, RichText } from '@wordpress/block-editor';
import { getSubheadingStyle } from './shared';

/**
 * v1 — line height used to be a custom `lineHeight` attribute rendered as an
 * inline style on the block wrapper. It is now the native
 * `typography.lineHeight` support (Styles > Typography), which serializes to
 * the very same `style="line-height:…"` on the wrapper. So a heading saved
 * with the old control would otherwise fail validation against the new save
 * output; this deprecation reproduces the old markup so those headings still
 * parse, and `migrate()` moves the value into `style.typography.lineHeight`
 * so they upgrade to the native control with no visual change.
 */
const v1 = {
	attributes: {
		content: {
			type: 'string',
			source: 'html',
			selector: 'h1,h2,h3,h4,h5,h6',
			default: '',
		},
		level: { type: 'number', default: 2 },
		textAlign: { type: 'string' },
		lineHeight: { type: 'string', default: '' },
		showSubheading: { type: 'boolean', default: false },
		subheading: { type: 'string', default: '' },
		subheadingPosition: { type: 'string', default: 'below' },
		subheadingFontSize: { type: 'string', default: '' },
		subheadingLineHeight: { type: 'string', default: '' },
		subheadingColor: { type: 'string', default: '' },
		subheadingFontWeight: { type: 'string', default: '' },
	},
	supports: {
		anchor: true,
		html: false,
		color: { background: true, text: true, gradients: true },
		typography: {
			fontSize: true,
			__experimentalFontFamily: true,
			__experimentalFontWeight: true,
			__experimentalLetterSpacing: true,
			__experimentalTextTransform: true,
		},
		spacing: { margin: true, padding: true },
		__experimentalBorder: { radius: true, width: true, color: true },
		shadow: true,
	},
	// Force migration for any heading that still carries the old attribute,
	// even if it would otherwise validate.
	isEligible( attributes ) {
		return !! attributes.lineHeight;
	},
	save( { attributes } ) {
		const {
			content,
			level,
			textAlign,
			lineHeight,
			showSubheading,
			subheading,
			subheadingPosition,
		} = attributes;
		const TagName = `h${ level }`;

		const blockProps = useBlockProps.save( {
			className: textAlign ? `has-text-align-${ textAlign }` : undefined,
			style: lineHeight ? { lineHeight } : undefined,
		} );

		if ( ! showSubheading ) {
			return (
				<TagName { ...blockProps }>
					<RichText.Content value={ content } />
				</TagName>
			);
		}

		const subheadingEl = (
			<RichText.Content
				tagName="p"
				className="noorifa-core-heading__subheading"
				style={ getSubheadingStyle( attributes ) }
				value={ subheading }
			/>
		);

		return (
			<div { ...blockProps }>
				{ 'above' === subheadingPosition && subheadingEl }
				<TagName>
					<RichText.Content value={ content } />
				</TagName>
				{ 'below' === subheadingPosition && subheadingEl }
			</div>
		);
	},
	migrate( attributes ) {
		const { lineHeight, ...rest } = attributes;
		return {
			...rest,
			style: {
				...rest.style,
				typography: {
					...( rest.style && rest.style.typography ),
					lineHeight,
				},
			},
		};
	},
};

export default [ v1 ];
