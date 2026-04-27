# wp-mu-plugins

A collection of standalone WordPress must-use plugins. Drop any file into your `mu-plugins/` directory — WordPress loads them automatically, no activation needed.

## Block system (required for ACF Gutenberg block workflow)

These three work together. Install all of them when using the [wp-blocks-dev](https://github.com/phucbm/wp-blocks-dev) workflow.

| File | What it does |
|---|---|
| [`acf-local-json-router.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/acf-local-json-router.php) | Routes ACF field group JSON saves per-block to `blocks/{slug}/fields.json`, other groups to `acf-json/`. Commits all paths to git for team sync. |
| [`wp-blocks-loader.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/wp-blocks-loader.php) | Reads `blocks.json` and registers each block via `register_block_type()`. Adds a project block category in the Gutenberg inserter. Fixes WP 6.3+ defer strategy so `viewScript` files load in the footer. |
| [`tailwind-theme-loader.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/tailwind-theme-loader.php) | Enqueues Tailwind-built `style.generated.css` on the frontend and inside the Gutenberg editor iframe. Supports per-developer CSS files for team environments via `DEV_CSS_MAP`. |

### Quick setup

```php
// In functions.php — map WP user IDs to per-dev CSS keys (optional)
define('PX_PROJECT_NAME', 'My Project');  // sets block category label
define('PX_ASSETS_HANDLE', 'my-project'); // prefix for script handles

define('DEV_CSS_MAP', [
    1 => 'alice',
    2 => 'bob',
]);
```

`blocks.json` at theme root:
```json
{ "blocks": ["hero", "cta", "team-grid"] }
```

---

## General utilities

| File | What it does |
|---|---|
| [`featured-image-column.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/featured-image-column.php) | Adds a clickable featured image column to the WP admin post list. Click to open the media picker and set the image without entering the post. |
| [`featured-posts.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/featured-posts.php) | Adds a featured checkbox to posts via meta key `_featured`. Includes sortable admin column, quick edit support, and a settings page (Settings → Featured Posts) to enable per post type. |
| [`editor-restrictions.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/editor-restrictions.php) | Applies Gutenberg editor restrictions for non-administrator roles: disables code editor, block locking, and unfiltered HTML. Uncomment additional restrictions in the config array as needed. |
| [`admin-page-guard.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/admin-page-guard.php) | Redirects unauthorized users away from specific admin pages. Configure `$allowed_users` and `$restricted_pages` at the top of the file for each project. |
| [`gf-multiple-form-instances.php`](https://github.com/phucbm/wp-mu-plugins/blob/main/gf-multiple-form-instances.php) | Allows multiple instances of the same Gravity Forms form on a single page with AJAX — replaces all form IDs with unique values to prevent conflicts. |

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- ACF Pro (for `acf-local-json-router.php` and `wp-blocks-loader.php`)
- Gravity Forms (for `gf-multiple-form-instances.php`)

## License

MIT — [phucbm](https://github.com/phucbm)
