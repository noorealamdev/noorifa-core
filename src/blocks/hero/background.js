/**
 * Shared between edit.js and save.js — the saved markup must exactly match
 * what save() produces (down to the same wrapper style), so both derive it
 * from these same two functions instead of duplicating the logic.
 */

export function getBackgroundStyle(
	backgroundType,
	backgroundImage,
	backgroundVideo,
	overlayOpacity
) {
	const showOverlay = hasOverlay( overlayOpacity );

	if ( 'video' === backgroundType && backgroundVideo?.url ) {
		return { position: 'relative', overflow: 'hidden' };
	}

	if ( backgroundImage?.url ) {
		return {
			backgroundImage: `url(${ backgroundImage.url })`,
			backgroundSize: 'cover',
			backgroundPosition: 'center',
			...( showOverlay ? { position: 'relative' } : {} ),
		};
	}

	// No media — a lone overlay still needs a positioned ancestor to tint
	// whatever the wrapper's own background color happens to be.
	return showOverlay ? { position: 'relative' } : {};
}

export function hasBackgroundVideo( backgroundType, backgroundVideo ) {
	return 'video' === backgroundType && !! backgroundVideo?.url;
}

// Falsy for 0/undefined/null — no overlay by default, so existing Hero
// blocks saved before this feature existed keep rendering byte-identical
// markup (see getBackgroundStyle above) instead of silently gaining a
// tint no one asked for.
export function hasOverlay( overlayOpacity ) {
	return !! overlayOpacity;
}
