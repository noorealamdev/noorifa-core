module.exports = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	// Every script in src/ ultimately runs in a browser (either the
	// wp-admin block editor or the front end) — wp-scripts' own default
	// config doesn't declare this, which was producing false-positive
	// no-undef errors for standard browser APIs (IntersectionObserver,
	// MutationObserver, getComputedStyle) in the front-end view scripts.
	env: {
		browser: true,
	},
	// One-off Node.js dev tooling (not part of the plugin build or the
	// shipped zip) — a plain CLI script, not editor/front-end code, so
	// the block-editor-oriented rule set doesn't apply to it.
	ignorePatterns: [ 'scripts/**' ],
};
