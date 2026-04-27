<?php
/**
 * Plugin Name: Featured Posts
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins/blob/main/featured-posts.php
 * Description: Add a featured checkbox to posts with sortable admin column, quick edit support, and a settings page to enable per post type. Uses _featured meta key.
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 *
 * USAGE
 * -----
 * Check if a post is featured:
 *   $is_featured = get_post_meta($post_id, '_featured', true);
 *
 * Query only featured posts:
 *   new WP_Query(['meta_query' => [['key' => '_featured', 'value' => '1']]]);
 *
 * Order with featured first:
 *   new WP_Query(['meta_key' => '_featured', 'orderby' => ['meta_value_num' => 'DESC', 'date' => 'DESC']]);
 */

if(!defined('ABSPATH')){
	exit;
}

class Featured_Posts{

	private static function get_supported_post_types(): array{
		$enabled_post_types = get_option('featured_posts_enabled_post_types', []);

		return is_array($enabled_post_types) ? $enabled_post_types : [];
	}

	private static function get_available_post_types(): array{
		$post_types = get_post_types(['public' => true], 'objects');
		unset($post_types['attachment']);

		return $post_types;
	}

	private static function get_featured_count($post_type): int{
		$query = new WP_Query([
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [['key' => '_featured', 'value' => '1', 'compare' => '=']],
			'fields'         => 'ids',
		]);

		return (int) $query->found_posts;
	}

	private static function get_featured_info_text($post_type): string{
		$count = self::get_featured_count($post_type);

		if($count === 0){
			return '';
		}

		$pto   = get_post_type_object($post_type);
		$label = $pto ? strtolower($pto->labels->name) : $post_type;
		$url   = admin_url("edit.php?post_type={$post_type}&orderby=_featured&order=desc");

		return sprintf('<a href="%s" target="_blank">Currently featuring %d %s</a>', esc_url($url), $count, esc_html($label));
	}

	public static function init(){
		$disable_sticky = get_option('featured_posts_disable_sticky', false);
		if($disable_sticky){
			remove_post_type_support('post', 'sticky');

			add_action('admin_head-edit.php', function(){
				global $typenow;
				if($typenow === 'post'){
					echo '<style>.inline-edit-row input[name="sticky"],.inline-edit-row input[name="sticky"] + .checkbox-title{display:none !important;}</style>';
				}
			});
		}

		add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
		add_action('save_post', [__CLASS__, 'save_meta_box'], 10, 2);

		add_action('admin_init', function(){
			foreach(self::get_supported_post_types() as $post_type){
				add_filter("manage_{$post_type}_posts_columns", [__CLASS__, 'add_admin_column']);
				add_action("manage_{$post_type}_posts_custom_column", [__CLASS__, 'render_admin_column'], 10, 2);
				add_filter("manage_edit-{$post_type}_sortable_columns", [__CLASS__, 'sortable_column']);
			}
		});

		add_action('admin_head', [__CLASS__, 'column_width_css']);
		add_action('pre_get_posts', [__CLASS__, 'sort_by_featured']);
		add_action('quick_edit_custom_box', [__CLASS__, 'quick_edit_box'], 10, 2);
		add_action('admin_footer-edit.php', [__CLASS__, 'quick_edit_script']);
		add_filter('the_title', [__CLASS__, 'add_star_to_title'], 10, 2);
		add_action('admin_menu', [__CLASS__, 'add_settings_page']);
		add_action('admin_init', [__CLASS__, 'register_settings']);
	}

	public static function add_meta_box(){
		foreach(self::get_supported_post_types() as $post_type){
			add_meta_box('featured_post', 'Featured', [__CLASS__, 'render_meta_box'], $post_type, 'side', 'high');
		}
	}

	public static function render_meta_box($post){
		wp_nonce_field('featured_post_nonce', 'featured_post_nonce');
		$is_featured = get_post_meta($post->ID, '_featured', true);
		$info        = self::get_featured_info_text($post->post_type);
		?>
        <label style="display: block; margin-bottom: 8px;">
            <input type="checkbox" name="featured_post" value="1" <?php checked($is_featured, '1'); ?>>
            Mark as featured
        </label>
		<?php if($info): ?>
            <div style="margin: 0; color: #646970;"><?php echo $info; ?></div>
		<?php endif; ?>
		<?php
	}

	public static function save_meta_box($post_id, $post){
		if(isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce')){
			self::save_quick_edit($post_id);

			return;
		}

		if(!isset($_POST['featured_post_nonce'])){
			return;
		}

		if(!wp_verify_nonce($_POST['featured_post_nonce'], 'featured_post_nonce')){
			return;
		}

		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return;
		}

		if(!current_user_can('edit_post', $post_id)){
			return;
		}

		update_post_meta($post_id, '_featured', !empty($_POST['featured_post']) ? '1' : '0');
	}

	private static function save_quick_edit($post_id){
		if(!current_user_can('edit_post', $post_id)){
			return;
		}

		update_post_meta($post_id, '_featured', isset($_POST['featured_post']) ? '1' : '0');
	}

	public static function add_admin_column($columns){
		$new_columns = [];
		foreach($columns as $key => $value){
			$new_columns[$key] = $value;
			if($key === 'title'){
				$new_columns['featured'] = '⭐';
			}
		}

		return $new_columns;
	}

	public static function render_admin_column($column, $post_id){
		if($column === 'featured'){
			echo get_post_meta($post_id, '_featured', true) ? '⭐' : '—';
		}
	}

	public static function column_width_css(){
		global $typenow;
		if(!in_array($typenow, self::get_supported_post_types())){
			return;
		}
		echo '<style>.column-featured{width:150px;display:none;}</style>';
	}

	public static function sortable_column($columns){
		$columns['featured'] = '_featured';

		return $columns;
	}

	public static function sort_by_featured($query){
		if(!is_admin() || !$query->is_main_query()){
			return;
		}

		if($query->get('orderby') === '_featured'){
			$query->set('meta_key', '_featured');
			$query->set('orderby', 'meta_value_num');
		}
	}

	public static function quick_edit_box($column, $post_type){
		if($column !== 'featured' || !in_array($post_type, self::get_supported_post_types())){
			return;
		}

		$info = self::get_featured_info_text($post_type);
		?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <input type="checkbox" name="featured_post" value="1">
                    <span class="checkbox-title">⭐ Featured <?php echo $info ? '(' . $info . ')' : ''; ?></span>
                </label>
            </div>
        </fieldset>
		<?php
	}

	public static function quick_edit_script(){
		global $typenow;
		if(!in_array($typenow, self::get_supported_post_types())){
			return;
		}
		?>
        <script>
            (function($){
                const wp_inline_edit = inlineEditPost.edit;

                inlineEditPost.edit = function(id){
                    wp_inline_edit.apply(this, arguments);

                    let postId = 0;
                    if(typeof(id) === 'object'){
                        postId = parseInt(this.getId(id));
                    }

                    if(postId > 0){
                        const $row = $('#post-' + postId);
                        const $editRow = $('#edit-' + postId);
                        const isFeatured = $row.find('.column-featured').text().trim() === '⭐';
                        $editRow.find('input[name="featured_post"]').prop('checked', isFeatured);
                    }
                };

                $(document).ajaxComplete(function(event, xhr, settings){
                    if(settings.data && settings.data.indexOf('action=inline-save') !== -1){
                        const match = settings.data.match(/post_ID=(\d+)/);
                        if(match){
                            const postId = match[1];
                            const $row = $('#post-' + postId);
                            setTimeout(function(){
                                const $title = $row.find('.row-title');
                                const isFeatured = $row.find('.column-featured').text().trim() === '⭐';
                                let titleText = $title.text().replace(/\s*⭐\s*$/, '').trim();
                                $title.text(isFeatured ? titleText + ' ⭐' : titleText);
                            }, 100);
                        }
                    }
                });
            })(jQuery);
        </script>
		<?php
	}

	public static function add_star_to_title($title, $post_id){
		if(!is_admin()){
			return $title;
		}

		$screen = get_current_screen();
		if(!$screen || $screen->base !== 'edit'){
			return $title;
		}

		if(!in_array(get_post_type($post_id), self::get_supported_post_types())){
			return $title;
		}

		return get_post_meta($post_id, '_featured', true) ? $title . ' ⭐' : $title;
	}

	public static function add_settings_page(){
		add_options_page('Featured Posts Settings', 'Featured Posts', 'manage_options', 'featured-posts-settings', [__CLASS__, 'render_settings_page']);
	}

	public static function register_settings(){
		register_setting('featured_posts_settings', 'featured_posts_enabled_post_types');
		register_setting('featured_posts_settings', 'featured_posts_disable_sticky');

		add_settings_section('featured_posts_main', 'Post Type Settings', [__CLASS__, 'settings_section_callback'], 'featured-posts-settings');
		add_settings_field('featured_posts_post_types', 'Enable Featured Selection For', [__CLASS__, 'post_types_field_callback'], 'featured-posts-settings', 'featured_posts_main');
		add_settings_field('featured_posts_disable_sticky', 'Sticky Posts', [__CLASS__, 'disable_sticky_field_callback'], 'featured-posts-settings', 'featured_posts_main');
	}

	public static function settings_section_callback(){
		echo '<p>Select which post types should have the featured checkbox available.</p>';
	}

	public static function post_types_field_callback(){
		$enabled_post_types   = get_option('featured_posts_enabled_post_types', []);
		$available_post_types = self::get_available_post_types();

		foreach($available_post_types as $post_type => $post_type_obj){
			$checked   = in_array($post_type, $enabled_post_types) ? 'checked' : '';
			$info_text = self::get_featured_info_text($post_type);
			?>
            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                <label style="min-width: 200px;">
                    <input type="checkbox" name="featured_posts_enabled_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php echo $checked; ?>>
					<?php echo esc_html($post_type_obj->labels->name); ?>
                    <span style="color: #646970;">(<?php echo esc_html($post_type); ?>)</span>
                </label>
				<?php if($info_text): ?>
                    <span style="margin-left: 16px; color: #646970;"><?php echo $info_text; ?></span>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public static function disable_sticky_field_callback(){
		$enabled_post_types = get_option('featured_posts_enabled_post_types', []);

		if(!in_array('post', $enabled_post_types)){
			echo '<p style="color: #646970;">This option is only available when Posts are enabled above.</p>';

			return;
		}

		$disable_sticky = get_option('featured_posts_disable_sticky', false);
		?>
        <label>
            <input type="checkbox" name="featured_posts_disable_sticky" value="1" <?php checked($disable_sticky, '1'); ?>>
            Disable WordPress Sticky Posts checkbox (to avoid confusion with Featured)
        </label>
        <p class="description">When enabled, the "Stick to the top of the blog" checkbox will be removed from the post editor.</p>
		<?php
	}

	public static function render_settings_page(){
		if(!current_user_can('manage_options')){
			return;
		}

		if(isset($_GET['settings-updated'])){
			add_settings_error('featured_posts_messages', 'featured_posts_message', 'Settings Saved', 'updated');
		}
		?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<?php settings_errors('featured_posts_messages'); ?>
            <form action="options.php" method="post">
				<?php
				settings_fields('featured_posts_settings');
				do_settings_sections('featured-posts-settings');
				submit_button('Save Settings');
				?>
            </form>
        </div>
		<?php
	}
}

Featured_Posts::init();
