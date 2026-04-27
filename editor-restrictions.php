<?php
/**
 * Plugin Name: Editor Restrictions
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins/blob/main/editor-restrictions.php
 * Description: Applies block editor restrictions for non-administrator roles. Disables code editor, block locking, and unfiltered HTML for editors/authors/contributors.
 * Version: 1.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 */

if(!defined('ABSPATH')){
	exit;
}

class Editor_Restrictions{

	/**
	 * Roles to apply restrictions to.
	 * All roles EXCEPT 'administrator' are listed here.
	 */
	private static $restricted_roles = [
		'editor',
		'author',
		'contributor',
		'subscriber',
	];

	/**
	 * Block editor settings to override for restricted roles.
	 * Uncomment a line to activate it.
	 */
	private static function get_restrictions(){
		return [

			// --- Editing capabilities ---

			// Hides the "Lock" option in block toolbar. Non-admins cannot lock or unlock blocks.
			'canLockBlocks'                          => false,

			// Removes "Code editor" view (Edit > Code editor). Users cannot switch to raw HTML mode.
			'codeEditingEnabled'                     => false,

			// Blocks raw HTML in Custom HTML blocks. Recommended for contributor/subscriber (security).
			'__experimentalCanUserUseUnfilteredHTML' => false,

			// --- UI / toolbar ---

			// Pins the block toolbar to the top of the editor.
//			'hasFixedToolbar' => true,

			// Forces distraction-free writing mode on load.
//			'focusMode' => true,

			// --- Meta boxes ---

			// Hides the "Custom Fields" meta box below the editor.
//			'enableCustomFields' => false,

			// --- Color & typography ---

			// Removes free-form color picker — palette only.
//			'disableCustomColors' => true,

			// Removes custom gradient editor — presets only.
//			'disableCustomGradients' => true,

			// Removes custom font-size input — presets only.
//			'disableCustomFontSizes' => true,

			// Hides the line-height control in Typography.
//			'enableCustomLineHeight' => false,

			// Hides padding/margin controls in Dimensions.
//			'enableCustomSpacing' => false,

			// Locks dimension inputs to preset units only.
//			'enableCustomUnits' => false,

		];
	}

	public static function init(){
		add_filter('block_editor_settings_all', [__CLASS__, 'apply'], 10, 2);
	}

	public static function apply($settings, $context){
		if(!self::is_restricted_user()){
			return $settings;
		}

		foreach(self::get_restrictions() as $key => $value){
			$settings[$key] = $value;
		}

		return $settings;
	}

	private static function is_restricted_user(){
		$user = wp_get_current_user();
		if(!$user->exists()){
			return false;
		}
		foreach(self::$restricted_roles as $role){
			if(in_array($role, (array) $user->roles, true)){
				return true;
			}
		}

		return false;
	}
}

Editor_Restrictions::init();
