=== Noorifa Core ===
Contributors: noorealam
Tags: woocommerce, product page, page builder, blocks, gutenberg
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The business-logic layer of the Noorifa Commerce Platform — build WooCommerce single product pages from premade templates, using blocks that wrap WooCommerce's own rendering.

== Description ==

Noorifa Core is the business-logic half of the Noorifa Commerce Platform: the Noorifa theme handles layout and structure only, and Noorifa Core lets you redesign the WooCommerce single product page on top of it without touching a theme file:

* Product Title, Rating, Price, Short Description, Add to Cart, Meta, Gallery, Tabs and Breadcrumbs blocks — each a thin wrapper around WooCommerce's own `woocommerce_template_single_*()` functions, so variations, stock handling and reviews keep working exactly as WooCommerce implements them
* Product Grid and Related Products blocks for latest/related/upsell product listings
* Container, Heading, Button, Accordion and Tabs layout blocks for arranging everything
* Product Layouts — full page arrangements built in the standard block editor, so it works on any theme, not just block themes
* Assign a Layout per product, per product category, or as the site-wide default; unconfigured products keep rendering exactly as vanilla WooCommerce always has
* An editor Template Library with premade full layouts and drop-in sections (FAQ accordion, trust badges, feature table)

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Upload the plugin to `/wp-content/plugins/noorifa-core`.
3. Run `npm install && npm run build` inside the plugin directory (development installs only).
4. Activate the plugin through the Plugins screen.
5. Go to Products → Add New Product Layout, build a layout, then assign it to a product from the "Product Page Template" box on the product edit screen.

== External services ==

This plugin includes an optional "Cloud Templates" source that can fetch additional premade templates from a remote API. It is **disabled by default** and only activates if a site or another plugin explicitly defines the `NOORIFA_CORE_CLOUD_URL` constant (or hooks the `noorifa-core/cloud_api_url` filter) with a real endpoint URL — out of the box, this plugin makes no external requests at all.

When enabled, the plugin sends a single `GET` request to the configured URL (no personal or site-identifying data included beyond standard HTTP headers) to retrieve a JSON list of templates, and caches the response in a transient for 12 hours.

== Source Code ==

The JavaScript in the `build/` directory is compiled from the human-readable source in `src/`. Full source, including the build tooling, is available at https://github.com/noorealamdev/noorifa-core. To regenerate the compiled files:

`npm install && npm run build`

== Changelog ==

= 1.0.0 =
* Initial release: WooCommerce product blocks, Product Layouts, per-product/category/site-wide template assignment, starter layouts and sections.
