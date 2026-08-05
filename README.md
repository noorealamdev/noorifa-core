# Noorifa Core

The business-logic layer of the Noorifa Commerce Platform. The Noorifa theme handles layout and structure only; this plugin is the WooCommerce product page builder on top of it: blocks that wrap WooCommerce's own single-product rendering (gallery, price, add to cart, tabs, related products, …), a Product Layouts post type for assembling full page arrangements from them, and per-product / per-category / site-wide template assignment — no FSE/block theme required.

## Development setup

```bash
npm install          # JS build tooling (@wordpress/scripts)
composer install     # PHP coding-standards tooling
npm run build        # compile blocks + library app to build/
```

Use `npm start` for watch mode while developing.

## Quality checks

| Command | What it does |
| --- | --- |
| `composer lint` | PHPCS with WordPress Coding Standards (`phpcs.xml.dist`) |
| `composer lint:fix` | Auto-fix PHPCS violations |
| `npm run lint:js` | ESLint via @wordpress/scripts |
| `npm run lint:css` | Stylelint via @wordpress/scripts |

All of these run in CI on every push (`.github/workflows/ci.yml`), which also uploads an installable `noorifa-core.zip` artifact.

## Releasing

```bash
npm run release
```

Builds the assets and produces `noorifa-core.zip` containing only the distributable files (defined in the `files` field of `package.json`) — no sources, tooling, or dev dependencies.

## Architecture

- `noorifa-core.php` — bootstrap: constants, PSR-4 autoloader, boots `Noorifa\Core\Plugin` (requires WooCommerce active)
- `includes/` — all PHP under the `Noorifa\Core\` namespace:
  - `Blocks\Manager` — auto-registers every `build/blocks/*/block.json`; per-block disable option
  - `Layouts\Post_Type` — registers the `noorifa_core_layout` post type (edited in the standard block editor, works on any theme)
  - `Layouts\Resolver` — resolves which Layout applies to a product: product meta → category default → site-wide default → none
  - `Layouts\Meta_Box` — the product/category assignment UI
  - `Layouts\Template_Override` — swaps in `templates/single-product-layout.php` on `template_include` when a Layout resolves
  - `Patterns\Manager` — auto-registers every `templates/library/*.json` file; feeds the template library
  - `Assets\Manager` — enqueues the template library app in the editor
  - `Admin\Dashboard` — block enable/disable settings + site-wide default Layout
- `src/blocks/<name>/` — block sources (block.json apiVersion 3, edit/save, SCSS). Layout primitives (Container, Heading, Button, Accordion, Tabs) are generic; the `product-*` and `related-products` blocks are thin dynamic wrappers around WooCommerce's own `woocommerce_template_single_*()` functions, so variations, stock and reviews keep working exactly as WooCommerce implements them.
- `src/library/` — the editor Template Library app (Noorifa Core/Export as JSON buttons in the top bar), shown only while editing a Product Layout
- `templates/library/` — premade demo template JSON files; layout templates compose sections via `Patterns\Manager::get_section_content()`

### Adding a block

Copy an existing folder in `src/blocks/`, rename the block in its `block.json`, and run `npm run build`. Registration, the dashboard toggle, and the inserter category are automatic.

### Adding a section or layout template

Build it in a Product Layout, click "Export as JSON" in the toolbar to download a `.json` file, then drop that file into `templates/library/` (optionally add a matching thumbnail image at `templates/library/thumbnails/{file name}.png`). It's registered and appears in the template library automatically — no rebuild needed.
