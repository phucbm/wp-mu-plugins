<?php
/**
 * Plugin Name: Tailwind Theme Loader
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins
 * Description: Automatically enqueue a Tailwind-built CSS file from the active theme on the frontend and inside the Gutenberg editor iframe. Supports per-developer CSS files for team environments.
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 */

if(!defined('ABSPATH')){
	exit;
}

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------
//
// DEV_CSS_MAP (optional) — map WordPress user IDs to Tailwind output keys.
//
// Define this constant in your theme's functions.php to enable per-developer
// CSS loading. Each developer runs `pnpm tw-watch` with their own
// TAILWIND_USER set in .env.local, which outputs a file named:
//   assets/css/style.{key}.generated.css
//
// Example:
//   define('DEV_CSS_MAP', [
//       1 => 'alice',   // loads style.alice.generated.css for WP user ID 1
//       2 => 'bob',     // loads style.bob.generated.css for WP user ID 2
//   ]);
//
// When no map is defined, or the current user is not in the map,
// the shared style.generated.css is loaded instead.
//
// The shared file is built by running `pnpm tw-build` (no TAILWIND_USER)
// and should be committed to the repo as the production stylesheet.
// ---------------------------------------------------------------------------


// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Sanitize a raw string to a safe CSS filename key.
 * Strips everything except lowercase letters, numbers, hyphens, underscores.
 */
function ttl_sanitize_css_key(string $value): string{
	return (string) preg_replace('/[^a-z0-9_-]+/', '', strtolower($value));
}

/**
 * Return the CSS key for the currently logged-in developer.
 *
 * Resolution order:
 *   1. DEV_CSS_MAP constant   — explicit user ID → key mapping
 *   2. WP user login          — fallback, sanitized to a safe filename key
 *   3. Empty string           — visitor is not logged in or not an admin
 */
function ttl_get_current_user_css_key(): string{
	// Only apply per-dev CSS to logged-in admins.
	// Visitors always receive the shared stylesheet.
	if(!is_user_logged_in() || !current_user_can('manage_options')){
		return '';
	}

	$user    = wp_get_current_user();
	$user_id = (int) ($user->ID ?? 0);

	// Check the explicit map first (preferred — avoids relying on WP login names)
	if(defined('DEV_CSS_MAP') && is_array(DEV_CSS_MAP) && isset(DEV_CSS_MAP[$user_id])){
		$key = ttl_sanitize_css_key((string) DEV_CSS_MAP[$user_id]);
		if($key !== ''){
			return $key;
		}
	}

	// Fall back to the user's WP login name
	return ttl_sanitize_css_key((string) ($user->user_login ?? ''));
}

/**
 * Return the relative path (from theme root) to the CSS file for the current user.
 *
 * Returns an empty string when no per-dev file should be used,
 * meaning the caller should fall back to the shared stylesheet.
 */
function ttl_get_user_css_relpath(): string{
	$key = ttl_get_current_user_css_key();
	if($key === ''){
		return '';
	}

	return 'assets/css/style.' . $key . '.generated.css';
}

/**
 * Resolve which CSS relative path to enqueue.
 *
 * Tries the per-user file first; falls back to the shared file.
 * Returns an empty string if neither file exists on disk.
 */
function ttl_resolve_css_relpath(): string{
	$theme_path = get_stylesheet_directory();

	// Per-developer file (only for logged-in admins with a mapped key)
	$user_rel = ttl_get_user_css_relpath();
	if($user_rel && file_exists($theme_path . '/' . $user_rel)){
		return $user_rel;
	}

	// Shared production file
	$shared_rel = 'assets/css/style.generated.css';
	if(file_exists($theme_path . '/' . $shared_rel)){
		return $shared_rel;
	}

	// Neither file found — nothing to enqueue
	return '';
}


// ---------------------------------------------------------------------------
// Frontend
// ---------------------------------------------------------------------------

/**
 * Enqueue the Tailwind stylesheet on the frontend.
 *
 * Uses filemtime() as the version string so browsers automatically
 * bust the cache whenever the file changes.
 */
function ttl_enqueue_frontend_styles(): void{
	$rel = ttl_resolve_css_relpath();
	if($rel === ''){
		return;
	}

	$file = get_stylesheet_directory() . '/' . $rel;

	wp_enqueue_style(
		'tailwind-theme',
		get_stylesheet_directory_uri() . '/' . $rel,
		[],
		filemtime($file)
	);
}

add_action('wp_enqueue_scripts', 'ttl_enqueue_frontend_styles');


// ---------------------------------------------------------------------------
// Gutenberg editor
// ---------------------------------------------------------------------------

/**
 * Load the Tailwind stylesheet inside the Gutenberg editor iframe.
 *
 * add_editor_style() accepts a path relative to the theme root.
 * Without this, Tailwind classes won't render in the block editor preview.
 */
function ttl_enqueue_editor_styles(): void{
	add_theme_support('editor-styles');

	$rel = ttl_resolve_css_relpath();
	if($rel === ''){
		return;
	}

	add_editor_style($rel);
}

add_action('after_setup_theme', 'ttl_enqueue_editor_styles');
