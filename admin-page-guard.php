<?php
/**
 * Plugin Name: Admin Page Guard
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins/blob/main/admin-page-guard.php
 * Description: Restricts access to specific admin pages — redirects unauthorized users to dashboard. Configure $allowed_users and $restricted_pages for each project.
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 */

if(!defined('ABSPATH')){
	exit;
}

class Admin_Page_Guard{

	/**
	 * WP user logins allowed to access restricted pages.
	 * Edit this list per project.
	 */
	private static $allowed_users = [
		'admin',
	];

	/**
	 * Restricted pages — paste the wp-admin URL as-is.
	 * Edit this list per project.
	 */
	private static $restricted_pages = [
		'/wp-admin/plugins.php',
	];

	public static function init(){
		add_action('admin_init', [__CLASS__, 'guard']);
		add_action('admin_menu', [__CLASS__, 'hide_menus'], 999);
	}

	/**
	 * Derive the WP menu slug from a restricted page URL
	 */
	private static function get_menu_slug($url){
		$parsed = parse_url($url);
		$file   = isset($parsed['path']) ? basename($parsed['path']) : '';
		$params = [];
		if(isset($parsed['query'])){
			parse_str($parsed['query'], $params);
		}

		// admin.php?page=slug → slug is the menu slug
		if($file === 'admin.php' && isset($params['page'])){
			return $params['page'];
		}

		// edit.php?post_type=foo → menu slug is "edit.php?post_type=foo"
		if($file === 'edit.php' && isset($params['post_type'])){
			return 'edit.php?post_type=' . $params['post_type'];
		}

		return null;
	}

	public static function hide_menus(){
		$current_user = wp_get_current_user();

		if(in_array($current_user->user_login, self::$allowed_users, true)){
			return;
		}

		foreach(self::$restricted_pages as $url){
			$slug = self::get_menu_slug($url);
			if($slug){
				remove_menu_page($slug);
			}
		}
	}

	public static function guard(){
		$current_user = wp_get_current_user();

		if(in_array($current_user->user_login, self::$allowed_users, true)){
			return;
		}

		global $pagenow;

		foreach(self::$restricted_pages as $url){
			$parsed = parse_url($url);
			$file   = isset($parsed['path']) ? basename($parsed['path']) : '';

			if($pagenow !== $file){
				continue;
			}

			$params = [];
			if(isset($parsed['query'])){
				parse_str($parsed['query'], $params);
			}

			$match = true;
			foreach($params as $key => $value){
				if(!isset($_GET[$key]) || $_GET[$key] !== $value){
					$match = false;
					break;
				}
			}

			if($match){
				wp_safe_redirect(admin_url());
				exit;
			}
		}
	}
}

Admin_Page_Guard::init();
