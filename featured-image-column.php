<?php
/**
 * Plugin Name: Featured Image Column
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins
 * Description: Add featured image column to post list with quick edit functionality
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 */

if(!defined('ABSPATH')){
	exit;
}

class Featured_Image_Column{

	/**
	 * Initialize
	 */
	public static function init(){
		// Add column for post types that support thumbnails
		add_action('admin_init', [__CLASS__, 'add_columns']);

		// Enqueue admin assets
		add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

		// AJAX handler for saving featured image
		add_action('wp_ajax_set_featured_image_quick', [__CLASS__, 'ajax_set_featured_image']);
	}

	/**
	 * Add columns for post types that support thumbnails
	 */
	public static function add_columns(){
		$post_types = get_post_types(['public' => true], 'names');

		foreach($post_types as $post_type){
			if(post_type_supports($post_type, 'thumbnail')){
				add_filter("manage_{$post_type}_posts_columns", [__CLASS__, 'add_column']);
				add_action("manage_{$post_type}_posts_custom_column", [__CLASS__, 'render_column'], 10, 2);
			}
		}
	}

	/**
	 * Add featured image column
	 */
	public static function add_column($columns){
		$new_columns = [];

		foreach($columns as $key => $value){
			// Insert after checkbox column
			if($key === 'cb'){
				$new_columns[$key] = $value;
				$new_columns['featured_image'] = 'Image';
			}else{
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	/**
	 * Render featured image column
	 */
	public static function render_column($column, $post_id){
		if($column === 'featured_image'){
			$thumbnail_id = get_post_thumbnail_id($post_id);

			if($thumbnail_id){
				$thumbnail = wp_get_attachment_image_src($thumbnail_id, [60, 60]);
				$thumbnail_url = $thumbnail ? $thumbnail[0] : '';
				?>
                <div class="featured-image-column-wrapper"
                     data-post-id="<?php echo esc_attr($post_id); ?>"
                     data-thumbnail-id="<?php echo esc_attr($thumbnail_id); ?>"
                     style="cursor: pointer; width: 60px; height: 60px; overflow: hidden; border-radius: 4px; background: #f0f0f1; transition: box-shadow 0.2s;">
                    <img src="<?php echo esc_url($thumbnail_url); ?>"
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
				<?php
			}else{
				?>
                <div class="featured-image-column-wrapper"
                     data-post-id="<?php echo esc_attr($post_id); ?>"
                     data-thumbnail-id=""
                     style="cursor: pointer; width: 60px; height: 60px; border-radius: 4px; background: #dcdcde; display: flex; align-items: center; justify-content: center; color: #646970; transition: box-shadow 0.2s;">
                    <span style="font-size: 20px;">📷</span>
                </div>
				<?php
			}
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public static function enqueue_assets($hook){
		// Only on post list pages
		$screen = get_current_screen();
		if(!$screen || $screen->base !== 'edit'){
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script('jquery');

		$nonce = wp_create_nonce('set_featured_image_quick');

		wp_add_inline_script('jquery', "
		jQuery(document).ready(function($){
			let mediaFrame;

			$(document).on('click', '.featured-image-column-wrapper', function(e){
				e.preventDefault();

				const \$wrapper = $(this);
				const postId = \$wrapper.data('post-id');

				if(mediaFrame){
					mediaFrame.off('select');
				}

				mediaFrame = wp.media({
					title: 'Set Featured Image',
					button: { text: 'Set Featured Image' },
					multiple: false
				});

				mediaFrame.on('select', function(){
					const attachment = mediaFrame.state().get('selection').first().toJSON();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'set_featured_image_quick',
							post_id: postId,
							thumbnail_id: attachment.id,
							nonce: '{$nonce}'
						},
						success: function(response){
							if(response.success){
								const imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
								\$wrapper.html('<img src=\"' + imageUrl + '\" style=\"width: 100%; height: 100%; object-fit: cover;\">');
								\$wrapper.data('thumbnail-id', attachment.id);
								\$wrapper.css({ 'background': '#f0f0f1', 'display': 'block' });
							}
						}
					});
				});

				mediaFrame.open();
			});
		});
		");

		wp_add_inline_style('wp-admin', '
		.column-featured_image { width: 60px; }
		.featured-image-column-wrapper:hover { box-shadow: 0 0 0 3px #2271b1; }
		');
	}

	/**
	 * AJAX handler for setting featured image
	 */
	public static function ajax_set_featured_image(){
		check_ajax_referer('set_featured_image_quick', 'nonce');

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		$thumbnail_id = isset($_POST['thumbnail_id']) ? intval($_POST['thumbnail_id']) : 0;

		if(!$post_id || !current_user_can('edit_post', $post_id)){
			wp_send_json_error('Permission denied');
		}

		if($thumbnail_id){
			set_post_thumbnail($post_id, $thumbnail_id);
			wp_send_json_success(['message' => 'Featured image set']);
		}else{
			delete_post_thumbnail($post_id);
			wp_send_json_success(['message' => 'Featured image removed']);
		}
	}
}

Featured_Image_Column::init();
