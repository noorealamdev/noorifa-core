/**
 * Builds the accordion wrapper's inline style from its attributes. Shared
 * by edit.js and save.js so the two produce identical markup (a mismatch
 * would trip block validation).
 *
 * The two background colors are emitted as CSS variables that style.scss
 * already reads (with neutral fallbacks), so they only override the
 * defaults when actually set — an untouched accordion serializes exactly
 * as before.
 *
 * @param {Object} attributes Block attributes.
 * @return {Object|undefined} Inline style object, or undefined when empty.
 */
export function getAccordionStyle( attributes ) {
	const { boxed, boxedWidth, titleBackground, contentBackground } =
		attributes;

	const style = {
		...( boxed && { maxWidth: boxedWidth } ),
		...( titleBackground && {
			'--noorifa-accordion-title-bg': titleBackground,
		} ),
		...( contentBackground && {
			'--noorifa-accordion-content-bg': contentBackground,
		} ),
	};

	return Object.keys( style ).length ? style : undefined;
}
