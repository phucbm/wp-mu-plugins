<?php
/**
 * Plugin Name: ACF Local JSON Router
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins
 * Description: Routes ACF field group JSON saves and loads to the correct location.
 *              Block groups → blocks/{slug}/fields.json (one file per block folder).
 *              All other groups (post types, pages, options pages, taxonomies, etc.)
 *              → acf-json/{location-type}-{title-slug}.json.
 *              All paths are committed to git for version control and team sync.
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 */

if(!defined('ABSPATH')){
	exit;
}

// ─── Paths ────────────────────────────────────────────────────────────────────

function aljr_shared_dir(): string{
	$dir = get_stylesheet_directory() . '/acf-json';
	if(!is_dir($dir)){
		wp_mkdir_p($dir);
	}

	return $dir;
}

function aljr_blocks_dir(): string{
	return get_stylesheet_directory() . '/blocks';
}

function aljr_block_slug(string $value): string{
	$slug  = str_replace('acf/', '', $value);
	$parts = explode('/', $slug);

	return end($parts);
}

function aljr_location_type(array $field_group): string{
	foreach(($field_group['location'] ?? []) as $group){
		foreach($group as $rule){
			return $rule['param'] ?? '';
		}
	}

	return '';
}

function aljr_shared_filename(array $field_group): string{
	$type  = str_replace('_', '-', aljr_location_type($field_group));
	$title = sanitize_title($field_group['title'] ?? 'group');

	return ltrim($type . '-' . $title, '-') . '.json';
}

// ─── Load ─────────────────────────────────────────────────────────────────────

add_filter('acf/settings/load_json', function($paths){
	$blocks_dir = aljr_blocks_dir();
	if(is_dir($blocks_dir)){
		foreach(glob($blocks_dir . '/*', GLOB_ONLYDIR) as $block){
			if(!empty(glob($block . '/*.json'))){
				$paths[] = $block;
			}
		}
	}

	$shared = aljr_shared_dir();
	if(is_dir($shared)){
		$paths[] = $shared;
	}

	return $paths;
});

// ─── Save ─────────────────────────────────────────────────────────────────────

add_filter('acf/settings/save_json', function($path){
	if(!isset($_POST['acf_field_group'])){
		return $path;
	}

	$field_group = $_POST['acf_field_group'];

	foreach(($field_group['location'] ?? []) as $group){
		foreach($group as $rule){
			if($rule['param'] === 'block' && $rule['operator'] === '=='){
				$block_slug = aljr_block_slug($rule['value']);
				$block_path = aljr_blocks_dir() . '/' . $block_slug;

				if(is_dir($block_path)){
					set_transient('aljr_saved_' . $field_group['key'], [
						'type'          => 'block',
						'relative_path' => 'blocks/' . $block_slug . '/fields.json',
					], 30);

					return $block_path;
				}
			}
		}
	}

	$shared   = aljr_shared_dir();
	$filename = aljr_shared_filename($field_group);

	set_transient('aljr_saved_' . $field_group['key'], [
		'type'          => 'shared',
		'relative_path' => 'acf-json/' . $filename,
	], 30);

	return $shared;
});

// ─── Filename ─────────────────────────────────────────────────────────────────

add_filter('acf/json/save_file_name', function($filename, $post, $load_path){
	$blocks_dir = aljr_blocks_dir();

	if(str_starts_with(wp_normalize_path($load_path), wp_normalize_path($blocks_dir))){
		return 'fields.json';
	}

	return aljr_shared_filename($post);
}, 10, 3);

// ─── Admin notice ─────────────────────────────────────────────────────────────

add_action('admin_notices', function(){
	$screen = get_current_screen();

	if(!$screen || $screen->id !== 'acf-field-group'){
		return;
	}

	if(!isset($_GET['post'])){
		return;
	}

	$field_group = acf_get_field_group($_GET['post']);
	if(!$field_group){
		return;
	}

	$saved_info = get_transient('aljr_saved_' . $field_group['key']);

	if($saved_info){
		$relative_path = $saved_info['relative_path'];
		?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>JSON file updated</strong><br>
                Location: <code><?php echo esc_html($relative_path); ?></code>
            </p>
            <p style="margin-top:8px;">
                Commit this file to git for version control and team sync.
                To edit manually, update the <code>modified</code> timestamp then sync from the ACF admin.
            </p>
            <p style="margin-top:8px;"><em>ACF Local JSON Router</em></p>
        </div>
		<?php
		delete_transient('aljr_saved_' . $field_group['key']);

		return;
	}

	$relative_path = aljr_resolve_relative_path($field_group);
	if(!$relative_path){
		return;
	}

	$abs_path = get_stylesheet_directory() . '/' . $relative_path;

	if(file_exists($abs_path)){
		?>
        <div class="notice notice-info">
            <p>
                JSON file at <code><?php echo esc_html($relative_path); ?></code>.
                Edit here or directly in the file — sync from ACF admin after manual edits.
            </p>
            <p style="margin-top:8px;"><em>ACF Local JSON Router</em></p>
        </div>
		<?php
	}else{
		?>
        <div class="notice notice-warning">
            <p>
                No JSON file yet. Save this field group to create
                <code><?php echo esc_html($relative_path); ?></code>.
            </p>
            <p style="margin-top:8px;"><em>ACF Local JSON Router</em></p>
        </div>
		<?php
	}
});

function aljr_resolve_relative_path(array $field_group): ?string{
	foreach(($field_group['location'] ?? []) as $group){
		foreach($group as $rule){
			if($rule['param'] === 'block' && $rule['operator'] === '=='){
				$block_slug = aljr_block_slug($rule['value']);

				return 'blocks/' . $block_slug . '/fields.json';
			}
		}
	}

	return 'acf-json/' . aljr_shared_filename($field_group);
}
