/**
 * Builds the subheading's inline style from its attributes. Shared by
 * edit.js and save.js so the two produce byte-identical markup (a
 * mismatch would trip block validation). Each value only contributes when
 * actually set, so an unstyled subheading serializes with no style attr.
 *
 * @param {Object} attributes Block attributes.
 * @return {Object} Inline style object for the subheading element.
 */
export function getSubheadingStyle( attributes ) {
	const {
		subheadingFontSize,
		subheadingLineHeight,
		subheadingColor,
		subheadingFontWeight,
	} = attributes;

	return {
		...( subheadingFontSize && { fontSize: subheadingFontSize } ),
		...( subheadingLineHeight && { lineHeight: subheadingLineHeight } ),
		...( subheadingColor && { color: subheadingColor } ),
		...( subheadingFontWeight && { fontWeight: subheadingFontWeight } ),
	};
}
