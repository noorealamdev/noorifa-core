# WordPress.org Plugin Review — Requirements Checklist

Compiled from the WP.org Plugin Review team's actual feedback on plugin submissions (multiple review rounds), plus Plugin Check tool findings. Use as a pre-submission checklist for every future WordPress.org plugin.

---

## 1. No raw `<script>` / `<style>` tags, anywhere

Every script or style must go through WordPress's enqueue APIs — `PluginCheck.CodeAnalysis` / `WordPress.WP.EnqueuedResources.NonEnqueuedScript` flags any literal `<script>...</script>` or `<style>...</style>` in markup, including ones that just carry dynamic inline JS for a single custom page.

- **External file:** `wp_enqueue_script()` / `wp_enqueue_style()`.
- **Inline JS/CSS with no real file:** register a phantom handle with `false` as the src — `wp_register_script( $handle, false, [], $ver, true )` — then enqueue it, then attach content via `wp_add_inline_script()` / `wp_add_inline_style()`.
- If a custom full-screen page doesn't call `wp_footer()`, print enqueued handles explicitly with `wp_print_scripts( [ $handle1, $handle2 ] )` / `wp_print_styles()`.

## 2. No heredoc/nowdoc syntax

`PluginCheck.CodeAnalysis.Heredoc.NotAllowed` — `<<<EOT ... EOT;` and `<<<'EOT' ... EOT;` are both disallowed, even for building inline JS strings. Build multi-line strings via concatenation:

```php
$js = "line one" . "\n"
    . "line two with a " . $php_variable . " inserted" . "\n"
    . "line three";
```

Use double-quoted segments when the JS content contains single quotes (no extra escaping). Escape any literal `$` as `\$` and any literal `\"` as `\\\"` inside double-quoted segments.

## 3. Permitted file types — no `.wasm` or other non-standard binaries

WordPress.org only allows file types that are normal for a plugin: `.php`, `.js`, `.css`, `.txt`, `.md`, common image formats (`.png`, `.svg`, `.jpg`), `.json`, `.xml`, and similar.

**`.wasm` (WebAssembly binaries) are not permitted.** If a bundled library ships a `.wasm` + wrapper pair (e.g. Draco's `draco_decoder.wasm` + `draco_wasm_wrapper.js`), switch to the JS-only decoder instead and delete those files:

```js
// Force JS-only decoder — the .wasm binary is not distributed.
dracoLoader.setDecoderConfig({ type: 'js' });
```

Other file types to watch out for: `.exe`, `.dll`, `.so`, `.bin`, `.pyc`. When in doubt, check the [WP.org plugin review guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

## 4. Vendored / bundled third-party libraries

- **No CDN calls, no remote files.** All assets must be served from the plugin directory. Calling external URLs at runtime (scripts, styles, fonts, images) is disallowed.
- **Comment-only URLs in vendored source also trigger flags.** The reviewer scans the codebase for any `https://` string, including those inside code comments in unmodified third-party files. If a vendored file has an illustrative example URL in a comment, remove or rewrite that comment.
- **Use a `.distignore` file** with `wp dist-archive` to exclude build artifacts, dev dependencies, AI tool directories, and any files that shouldn't ship (see section 12).
- List bundled third-party libraries (name, version, license) in `readme.txt` under `== External services ==` or a dedicated `== Third-party libraries ==` section.

## 5. Nonce + permission checks on every AJAX / data-modifying endpoint

- Every `wp_ajax_*` handler: `check_ajax_referer( $action, $field )` or `wp_verify_nonce()` at the top.
- Every handler that writes data: `current_user_can()` check appropriate to what's being changed (e.g. `edit_product` for product meta).
- Form-based saves (`save_post_*` hooks): nonce field + verify, capability check, and autosave guard (`DOING_AUTOSAVE`).
- Public `nopriv` AJAX actions don't need `current_user_can()`, but still need the nonce check plus their own data validation (valid IDs, purchasable products, etc.).
- **WP_List_Table sort/filter/pagination params** (`orderby`, `order`, `status`, `paged`, `s`) are read-only admin queries — nonce is not required. PHPCS will still flag them. Use `phpcs:ignore WordPress.Security.NonceVerification.Recommended` with a justification comment explaining they are read-only admin list views.

## 6. Sanitize, validate, and escape — the correct pattern for each data type

### Simple scalar values

```php
$id    = absint( $_POST['product_id'] );
$label = sanitize_text_field( wp_unslash( $_POST['label'] ) );
$email = sanitize_email( wp_unslash( $_POST['email'] ) );
```

### GET params used only for comparison

Even when a `$_GET` value is only compared against a string literal (no DB write, no output), PHPCS still requires `sanitize_text_field( wp_unslash() )`. Extract to a variable first:

```php
// Wrong — PHPCS flags this even though it's just a comparison:
if ( 'delete' === ( $_GET['action'] ?? '' ) ) { ... }

// Correct — sanitize before comparing:
$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
if ( 'delete' === $action ) { ... }
```

### JSON POST data

`json_decode()` does **not** sanitize. The Plugin Check tool and reviewers flag any `json_decode()` of input that has not first been passed through a sanitizer. The correct pattern:

```php
// For JSON that contains NO HTML/SVG (label/value pairs, filenames, IDs, etc.)
$fields_json = isset( $_POST['fields'] ) ? sanitize_text_field( wp_unslash( $_POST['fields'] ) ) : '[]';
$decoded     = json_decode( $fields_json, true );
foreach ( $decoded as $f ) {
    $label = sanitize_text_field( (string) $f['label'] );
    $value = sanitize_text_field( (string) $f['value'] );
}
```

For JSON that **intentionally contains HTML/SVG markup** (e.g. icon fields in a config blob), `sanitize_text_field()` would strip tags and corrupt the JSON. The correct approach:

1. Read the raw string with `wp_unslash()` and add a `phpcs:ignore` referencing the downstream sanitizer.
2. Validate the JSON structure with `json_decode()`.
3. Walk the decoded array recursively and apply `wp_kses()` with a tight allowlist to markup strings, `sanitize_text_field()` to plain text strings, and leave non-strings unchanged.
4. Re-encode with `wp_json_encode()` and write the sanitized result to the DB.

### Conditional escape (escape=false pattern)

When a helper function conditionally escapes based on a parameter (caller pre-escapes when passing `false`), PHPCS flags the unescaped branch. Add a comment explaining the contract:

```php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller pre-escapes when $escape=false
echo '<span>' . ( $escape ? esc_html( $value ) : $value ) . '</span>';
```

### `phpcs:ignore` placement rules

`// phpcs:ignore Rule.Name` silences **the immediately following line only**. It does NOT cover the entire statement if the statement spans multiple lines. Rules:

- Put the `phpcs:ignore` comment on the line **directly above** the line that triggers the warning.
- For a multi-line statement, use `phpcs:disable` / `phpcs:enable` blocks instead.
- Every `phpcs:ignore` / `phpcs:disable` **must include a justification comment** explaining why it is safe — reviewers read these.

## 7. Direct database queries — full sniff list

Any direct `$wpdb` call bypassing WordPress meta/query APIs needs suppression for the sniffs that fire. **Plugin Check and PHPCS use different sniff codes** — you often need both sets.

### PHPCS/WPCS sniffs (always needed)

```
WordPress.DB.DirectDatabaseQuery.DirectQuery
WordPress.DB.DirectDatabaseQuery.NoCaching
WordPress.DB.PreparedSQL.InterpolatedNotPrepared
WordPress.DB.PreparedSQL.NotPrepared
WordPress.DB.SlowDBQuery.slow_db_query_meta_key
WordPress.DB.SlowDBQuery.slow_db_query_meta_value
```

### Plugin Check sniffs (additional — different tool, different codes)

```
PluginCheck.Security.DirectDB.UnescapedDBParameter
```

This fires when a `$table` variable (built from `$wpdb->prefix . 'table_name'`) is used in a query — even though the value is a trusted constant, not user input. Add it to every direct-query ignore block.

### PreparedSQL false positives

When `$wpdb->prepare()` output is stored in `$sql` and then passed to `$wpdb->get_results( $sql )`, PHPCS flags `$sql` as `NotPrepared`. Include `WordPress.DB.PreparedSQL.NotPrepared` in the disable block that wraps the whole statement:

```php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,
//               WordPress.DB.DirectDatabaseQuery.NoCaching,
//               WordPress.DB.PreparedSQL.InterpolatedNotPrepared,
//               WordPress.DB.PreparedSQL.NotPrepared,
//               PluginCheck.Security.DirectDB.UnescapedDBParameter,
//               WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,
//               WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
//   -- $table is a trusted prefix constant; $sql is the output of $wpdb->prepare();
//      dynamic WHERE clause placeholders account for all $values array entries.
$sql = $wpdb->prepare( "SELECT * FROM `$table` $where_sql ...", $values );
return $wpdb->get_results( $sql );
// phpcs:enable ...same list...
```

### Dynamic IN() bulk queries

```php
$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,
//               WordPress.DB.PreparedSQL.NotPrepared,
//               WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
//   -- $placeholders is only %d tokens, $ids are all absint-filtered.
$wpdb->query( $wpdb->prepare(
    "DELETE FROM `{$wpdb->prefix}my_table` WHERE `id` IN ($placeholders)",
    $ids
) );
// phpcs:enable ...
```

### SlowDBQuery on meta_key / meta_value

When bypassing `get_post_meta()` by writing directly to `$wpdb->postmeta` (valid when the metadata API corrupts data via filters), PHPCS fires slow-query warnings on the lines where `meta_key` and `meta_value` column names appear inside `$wpdb->update()` / `$wpdb->insert()`. Use `phpcs:disable`/`phpcs:enable` around the entire multi-line call:

```php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,
//               WordPress.DB.DirectDatabaseQuery.NoCaching,
//               WordPress.DB.SlowDBQuery.slow_db_query_meta_key,
//               WordPress.DB.SlowDBQuery.slow_db_query_meta_value
//   -- Deliberately bypasses update_post_meta(); metadata filters on this
//      install corrupt SVG strings inside JSON values.
$wpdb->update(
    $wpdb->postmeta,
    [ 'meta_value' => $raw ],
    [ 'post_id' => $id, 'meta_key' => $meta_key ]
);
// phpcs:enable ...
```

## 8. `readme.txt` requirements

- `Tested up to:` **must be the current WordPress stable version, as `major.minor` only — never a patch version.** `7.0.1` is rejected with `invalid_tested_upto_minor` even though `7.0.1` is a real, more-specific release; the field only ever takes two components (`7.0`, not `7.0.1`). Check https://wordpress.org/download/ before every submission and update this field. An outdated value (e.g. `6.8` when `7.x` is current) hides the plugin from search results.
- `Stable tag:` must match the `Version:` header in the main plugin file exactly.
- `== External services ==` section is **required** if the codebase references any third-party domain or service — even a documentation URL in a comment inside bundled library source code can trigger the reviewer. State clearly what is sent where, or state that no external calls are made and explain any reference that might look like one.
- `== Screenshots ==` entries must correspond to actual screenshot files in the `assets/` folder of the SVN repository (not the plugin zip).

## 9. Text domain and translation hygiene

- The text domain in every `__()`/`_e()`/`esc_html__()`/`esc_attr__()` call must exactly match the plugin slug.
- Every translatable string with **any** `%s`/`%d`/`%f` placeholder needs a `/* translators: */` comment immediately above the `printf()`/`sprintf()` call — even single-placeholder strings:

```php
/* translators: %s: site name */
$body = sprintf( __( 'New request via %s.', 'my-plugin' ), $site_name );
```

- When a string has **multiple** placeholders, use **ordered** placeholders (`%1$s`, `%2$s`, ...) instead of bare `%s, %s`. This allows translators to reorder them for their language:

```php
/* translators: 1: room width in metres 2: room height in metres */
$line = sprintf( __( 'Room: %1$sm × %2$sm', 'my-plugin' ), $width, $height );
```

PHPCS sniff: `WordPress.WP.I18n.UnorderedPlaceholdersText` fires on any `%s, %s` pattern in a translatable string.

## 10. Dev-only files — keep them out of the plugin package

The Plugin Check tool scans everything in the plugin directory. Two categories always get flagged:

### AI instruction directories

`.claude`, `.cursor`, `.aider`, or any other AI tool configuration directory triggers:
> `ai_instruction_directory` — These directories should not be included in production plugins.

### Unexpected markdown files

Only `README.md` and `CHANGELOG.md` are expected. Any other `.md` file (e.g. `WORDPRESS_ORG_PLUGIN_GUIDELINES.md`, `NOTES.md`) triggers:
> `unexpected_markdown_file` — Only specific markdown files are expected in production plugins.

### Solution — `.distignore`

Create a `.distignore` file in the plugin root. The `wp dist-archive` CLI command (and the WP.org deploy tooling) reads this file and excludes the listed items from the production zip:

```
.claude
.cursor
.distignore
.gitignore
WORDPRESS_ORG_PLUGIN_GUIDELINES.md
NOTES.md
camera-tool.html
node_modules
*.test.js
```

Note: `.distignore` suppresses the files in a distribution build, but Plugin Check running locally still scans everything. For a clean local Plugin Check run, temporarily move dev files out of the directory, or accept that these warnings are build-time concerns only.

## 11. No arbitrary custom CSS, JavaScript, or PHP from users

WordPress.org no longer permits a plugin to let users save and have the plugin execute/render **arbitrary** CSS, JavaScript, or PHP — even a plain "Custom CSS" textarea saved to an option and injected as a `<style>` tag is flagged, despite CSS carrying far less risk than JS/PHP.

- The reviewer's stated reasoning: WordPress core already ships a vetted, error-checked CSS editor (Customizer "Additional CSS"); duplicating that surface in a plugin is discouraged, and once a plugin accepts a free-text code field the line to "arbitrary script insertion" is considered crossed regardless of which language it's in.
- **This applies even to a single-purpose, seemingly-harmless field.** Don't assume CSS-only, tag-stripped, sandboxed-in-Shadow-DOM input is an exception — the review flagged exactly that shape of feature.
- **Fix:** remove the free-text field. Replace it with discrete, curated controls instead — color pickers, dropdowns, toggles, numeric inputs — each writing to its own option, with the plugin generating any resulting CSS/behavior programmatically. If a site owner truly needs raw CSS, point them at core's own Additional CSS panel (Appearance → Customize) rather than re-implementing it.
- Never build a feature that lets a user paste JS to be `eval()`'d, injected inline, or saved as a `.js` file the plugin then enqueues; same for any feature that writes user-supplied text into a `.php` file that later gets `include`d/`require`d.

## 12. `permission_callback` — capability must match the actual authority granted

A `permission_callback` returning `true` for *some* logged-in users is not enough; it must be true only for users who should have the specific authority the endpoint grants. Plugin Check's AI reviewer specifically checks whether the chosen capability is broad enough to expose or modify data belonging to other users/other records.

- **`edit_posts` is a common false-safe.** It's granted to Contributors and Authors — roles that normally only manage their own content — but a REST route gating access to *every* record in a custom table (all quizzes, all attempts, all analytics, regardless of owner) with `edit_posts` lets any Author read or modify data that isn't theirs. Flagged pattern: `edit_posts is too broad ... because it lets users view/manage records for any [resource] without ownership or a dedicated capability check`.
- **Fix, when the feature has no per-record ownership model:** require `manage_options` (or a dedicated custom capability you register) instead — i.e., admin-only, matching the actual blast radius of the endpoint.
- **Fix, when the feature is genuinely per-user/per-author:** keep a lower capability, but the callback must *also* check ownership of the specific record being requested (e.g. `$post->post_author === get_current_user_id() || current_user_can( 'edit_others_posts' )`), not just that the user holds some generic capability.
- **`__return_true` is fine — but only for genuinely public, read-only, non-sensitive data** (matching content already visible to anyone, or endpoints designed for anonymous use). Anything that reads private data, or that mutates state, needs a real check.
- A capability check is not a substitute for an ownership/object-level check and vice versa — public write endpoints acting on a specific resource by ID need both: a capability check (if not `__return_true`) **and** proof the caller actually owns/started that specific resource (see §13).

## 13. Object-level authorization on public write endpoints (IDOR)

A `permission_callback` of `__return_true` is appropriate for endpoints an anonymous visitor is meant to use (e.g. starting or submitting a public quiz/form attempt) — but once that endpoint accepts a numeric resource ID and mutates it, a numeric ID alone is not authorization. Sequential integer IDs are guessable/enumerable; without a secondary secret, any visitor can act on any other visitor's in-progress resource (view, complete, overwrite) just by changing the ID in the URL.

**Fix:** issue a per-resource, unguessable token when the resource is created (`wp_generate_password( 32, false )` is sufficient), return it in the creation response, and require the caller to echo it back on every subsequent mutating call against that resource — verify with `hash_equals()`, not `===`, to avoid a timing side-channel:

```php
// On creation:
$token = wp_generate_password( 32, false );
$id    = $repo->create( ..., $token );
return new WP_REST_Response( [ 'id' => $id, 'token' => $token ], 201 );

// On every later mutation of that same $id:
if ( ! hash_equals( (string) $record['token'], (string) $request->get_param( 'token' ) ) ) {
    return new WP_Error( 'invalid_token', 'This resource is not valid.', [ 'status' => 403 ] );
}
```

Register `token` as a `required` REST arg so a missing token 400s before your callback even runs.

## 14. Public, maintained access to source code (Guidelines #1 and #4)

Every plugin must let anyone review, study, modify, and fork its actual source — not just the code that ships. This is checked independently of everything else and fires even on an otherwise-clean submission.

- **The trigger:** any minified/bundled build output (webpack/Rollup/esbuild bundles, e.g. `admin/build/index.js`) that the tool cannot match to a corresponding human-readable, editable source in the same package or a documented public location.
- **Fix — pick one:**
  1. Ship the uncompiled source alongside the build output in the plugin itself (rarely practical for a webpack build), or
  2. **Document in `readme.txt` where the public source repository lives**, plus the exact command(s) needed to regenerate the compiled file(s) from it (e.g. "Full source at `https://github.com/you/plugin`; run `npm install && npm run build` to regenerate `admin/build/index.js`"). This is the normal path for anything built with a JS toolchain.
- The repository linked must actually be public and actually contain the build tooling (`package.json`, webpack/build config) — not just the plugin's PHP.
- Same requirement for vendored third-party libraries: if the library file itself doesn't already carry a header comment with its name/version/repo URL, add that documentation to `readme.txt`.

## 15. The review process itself

- Reviews are done by a volunteer reviewer using a combination of human judgment and automated tooling (Plugin Check / PHPCS).
- After a "pended" submission, replies should be **short, direct, and clear**. Do **not** list every change made — the reviewer re-checks the entire plugin. Do include genuinely useful context or clarifications (e.g. "the flagged URL is a doc-comment citation in bundled, unmodified Three.js source, not a live call").
- Responses that are verbose, AI-styled, or list every line changed read as automated and may be deprioritised.
- Repeated fatal submissions or 3+ months of inactivity on a pended plugin risk permanent rejection.

## 16. Pre-submission checklist

Run through this before every submission or resubmission:

- [ ] **Plugin Check** (`wp plugin check <slug>`) passes with no ERRORs. Investigate every WARNING — suppressions need justification.
- [ ] **PHPCS + WPCS** (`phpcs --standard=WordPress .`) passes or every remaining warning has a justified `phpcs:ignore`/`phpcs:disable`.
- [ ] **No raw `<script>`/`<style>` tags** — grep `<script` and `<style` in all PHP files.
- [ ] **No heredoc/nowdoc** — grep `<<<` confirms zero results.
- [ ] **No non-permitted file types** — no `.wasm`, `.exe`, `.dll`, `.so`, `.bin`. Check `assets/js/` subdirectories too (vendored libraries often ship wasm decoders).
- [ ] **No remote URLs in any file**, including comments in vendored JS/PHP — grep `https://` and inspect every hit.
- [ ] **No arbitrary CSS/JS/PHP input field** — grep for any free-text `textarea`/setting whose saved value is later output as/executed as code (see §11). Curated controls only.
- [ ] **Every `permission_callback`** uses a capability matching the endpoint's actual blast radius — not a broad capability like `edit_posts` gating access to every record in a custom table (see §12). `manage_options` (or a dedicated capability) for admin-only, all-records endpoints; ownership checks for per-author endpoints.
- [ ] **Every public (`__return_true`), ID-addressed, mutating endpoint** requires and verifies a per-resource secret token with `hash_equals()`, not just the numeric ID (see §13).
- [ ] **Every minified/bundled JS asset** (webpack/Rollup output) has its source either shipped alongside it or documented in `readme.txt` with a public repo link and the exact rebuild command (see §14).
- [ ] **Every `$_POST`/`$_GET`/`$_REQUEST` read** has `sanitize_*( wp_unslash( ... ) )` or a justified `phpcs:ignore` on that exact line — including reads used only for comparison, not output.
- [ ] **Every direct `$wpdb` call** has the full matching ignore codes for both WPCS and PluginCheck sniffs, using `phpcs:disable`/`phpcs:enable` for multi-line statements.
- [ ] **Dynamic WHERE / IN() queries** include `PreparedSQLPlaceholders.ReplacementsWrongNumber`, `UnfinishedPrepare`, and `NotPrepared` in the disable block.
- [ ] **`readme.txt` `Tested up to:`** matches the current WordPress stable release, `major.minor` only — no patch component (check wordpress.org/download).
- [ ] **`readme.txt` `Stable tag:`** matches `Version:` in the main plugin file header.
- [ ] **`readme.txt` has `== External services ==`** if any domain appears anywhere in the codebase.
- [ ] **All `sprintf(__(...))` calls** have a `/* translators: */` comment. Multiple `%s` use ordered form (`%1$s`, `%2$s`).
- [ ] **`.distignore`** excludes AI directories (`.claude`, etc.), dev-only `.md` files, `node_modules`, build tools, and any file not needed at runtime.
- [ ] **Test on a clean WordPress install** with `WP_DEBUG` and `WP_DEBUG_LOG` enabled — no PHP notices, warnings, or fatal errors.
