<?php
/**
 * Plugin Name: WP Blocks Loader
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins/blob/main/wp-blocks-loader.php
 * Description: Registers ACF Gutenberg blocks from blocks.json, adds a project block category in the Gutenberg inserter, and ensures viewScripts load in the footer without WordPress's default defer strategy (WP 6.3+).
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
// blocks.json  — required at theme root. Lists block folder names to register.
// Example:
//   { "blocks": ["hero", "cta", "team-grid"] }
//
// PX_PROJECT_NAME (optional) — constant defined in functions.php.
// Sets the block category label shown in the Gutenberg inserter.
// Defaults to "Project" if not defined.
//
// PX_ASSETS_HANDLE (optional) — prefix for generated script handles.
// Defaults to "px".
// ---------------------------------------------------------------------------


// ─── Block Registration ───────────────────────────────────────────────────────

function px_register_blocks(): void{
	$blocks_json = file_get_contents(get_stylesheet_directory() . '/blocks.json');
	$blocks_data = json_decode($blocks_json, true);

	if(!isset($blocks_data['blocks']) || !is_array($blocks_data['blocks'])){
		return;
	}

	foreach($blocks_data['blocks'] as $block_name){
		$block_path = get_stylesheet_directory() . '/blocks/' . $block_name;

		if(file_exists($block_path . '/block.json')){
			register_block_type($block_path);
		}
	}
}
add_action('init', 'px_register_blocks');

function px_block_categories(array $categories): array{
	$project_name  = defined('PX_PROJECT_NAME') ? PX_PROJECT_NAME : 'Project';
	$category_slug = sanitize_title($project_name);

	array_unshift($categories, [
		'slug'  => $category_slug,
		'title' => sprintf(__('%s Blocks'), $project_name),
	]);

	return $categories;
}
add_filter('block_categories_all', 'px_block_categories', 99999999);


// ─── Block Scripts ────────────────────────────────────────────────────────────

/**
 * Intercept block metadata before registration.
 *
 * Replaces file-based viewScript paths with manually registered handles
 * that load in the footer without defer. Preserves already-registered
 * handles (e.g. 'px-video-slider') and their dependency order.
 *
 * Example block.json:
 *   "viewScript": ["px-video-slider", "file:./view.js"]
 * Result: both scripts load in footer, in order, without defer.
 */
function px_override_block_view_scripts($metadata){
	if(empty($metadata['viewScript'])){
		return $metadata;
	}

	$block_dir = isset($metadata['file']) ? dirname($metadata['file']) : '';

	if(!$block_dir){
		return $metadata;
	}

	$view_scripts = is_array($metadata['viewScript']) ? $metadata['viewScript'] : [$metadata['viewScript']];
	$handles      = [];

	foreach($view_scripts as $script){
		if(!str_starts_with($script, 'file:')){
			$handles[] = $script;
			continue;
		}

		$script_path = ltrim(str_replace('file:', '', $script), './');
		$script_file = $block_dir . '/' . $script_path;

		if(file_exists($script_file)){
			$block_name    = basename($block_dir);
			$handle_prefix = defined('PX_ASSETS_HANDLE') ? PX_ASSETS_HANDLE : 'px';
			$handle        = $handle_prefix . '-' . $block_name . '-view';

			wp_register_script(
				$handle,
				get_stylesheet_directory_uri() . '/blocks/' . $block_name . '/' . $script_path,
				$handles, // previously collected handles as deps — preserves load order
				filemtime($script_file),
				true // footer
			);

			$handles[] = $handle;
		}
	}

	$metadata['viewScript'] = $handles;

	return $metadata;
}
add_filter('block_type_metadata', 'px_override_block_view_scripts');

/**
 * Strip defer/async strategy from all enqueued scripts.
 *
 * WordPress 6.3+ adds defer by default. This removes it globally
 * to prevent race conditions and execution order issues.
 * Runs at priority 999 to fire after all scripts are enqueued.
 */
function px_remove_block_defer_strategy(): void{
	global $wp_scripts;

	if(!$wp_scripts){
		return;
	}

	foreach($wp_scripts->registered as $handle => $script){
		if(isset($script->extra['strategy'])){
			unset($wp_scripts->registered[$handle]->extra['strategy']);
		}
	}
}
add_action('wp_enqueue_scripts', 'px_remove_block_defer_strategy', 999);
