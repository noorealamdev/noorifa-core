/**
 * Whether this site currently has an active Noorifa Core Pro license.
 *
 * Backed by window.noorTemplatesLicensing, localized by
 * Assets\Manager::enqueue_editor_assets() from Licensing\Gate — the same
 * check every Pro-gated REST endpoint and PHP feature goes through.
 *
 * @return {boolean}
 */
export function isPro() {
	return Boolean( window.noorTemplatesLicensing?.isPro );
}

/**
 * Returns the URL to send a user to upgrade to Pro, or '' when unknown.
 *
 * @return {string}
 */
export function getCheckoutUrl() {
	return window.noorTemplatesLicensing?.checkoutUrl || '';
}
