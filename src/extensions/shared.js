/**
 * Attribute defaults shared between the editor filters (index.js) and
 * anything else that needs to reason about visibility/animation values.
 */

export const VISIBILITY_DEFAULT = {
	hideOnMobile: false,
	hideOnTablet: false,
	hideOnDesktop: false,
};

export const ANIMATION_DEFAULT = {
	type: 'none',
	duration: 600,
	delay: 0,
};

export const ANIMATION_TYPES = [ 'fade-in', 'slide-up', 'zoom-in' ];

/**
 * Whether a block name is extended by the Noorifa Core block engine.
 *
 * @param {string} name Block name, e.g. "noorifa-core/container".
 * @return {boolean} Whether the block is extendable.
 */
export function isExtendable( name ) {
	return typeof name === 'string' && name.startsWith( 'noorifa-core/' );
}
