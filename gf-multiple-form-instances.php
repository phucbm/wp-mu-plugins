<?php
/**
 * Plugin Name: Multiple Form Instances for Gravity Forms
 * Plugin URI:  https://github.com/phucbm/wp-mu-plugins/blob/main/gf-multiple-form-instances.php
 * Description: Run multiple instances of the same Gravity Forms form on a single page (with AJAX) without conflicts.
 * Version: 2.0.0
 * Author: phucbm
 * Author URI: https://phucbm.com
 *
 * Based on original work by Nikunj (https://github.com/nikunj8866/)
 */

if(!defined('ABSPATH')){
	exit;
}

class GF_Multiple_Form_Instances{

	public function __construct(){
		add_filter('gform_get_form_filter', [$this, 'make_unique'], 10, 2);
	}

	/**
	 * Replaces all occurrences of the form ID with a new, unique ID.
	 */
	public function make_unique($form_string, $form){
		// If form has been submitted, use the submitted ID; otherwise generate a new unique ID
		if(isset($_POST['gform_random_id'])){ // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$random_id = absint($_POST['gform_random_id']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}else{
			$random_id = wp_rand();
		}

		$hidden_field = "<input type='hidden' name='gform_field_values'";

		$strings = [
			' gform_wrapper '                                                => ' gform_wrapper gform_wrapper_original_id_' . $form['id'] . ' ',
			"for='choice_"                                                   => "for='choice_" . $random_id . '_',
			"id='choice_"                                                    => "id='choice_" . $random_id . '_',
			"id='gform_target_page_number_"                                  => "id='gform_target_page_number_" . $random_id . '_',
			"id='gform_source_page_number_"                                  => "id='gform_source_page_number_" . $random_id . '_',
			"#gform_target_page_number_"                                     => "#gform_target_page_number_" . $random_id . '_',
			"#gform_source_page_number_"                                     => "#gform_source_page_number_" . $random_id . '_',
			"id='label_"                                                     => "id='label_" . $random_id . '_',
			"'gform_wrapper_" . $form['id'] . "'"                            => "'gform_wrapper_" . $random_id . "'",
			"'gf_" . $form['id'] . "'"                                       => "'gf_" . $random_id . "'",
			"'gform_" . $form['id'] . "'"                                    => "'gform_" . $random_id . "'",
			"'gform_ajax_frame_" . $form['id'] . "'"                         => "'gform_ajax_frame_" . $random_id . "'",
			'#gf_' . $form['id'] . "'"                                       => '#gf_' . $random_id . "'",
			"'gform_fields_" . $form['id'] . "'"                             => "'gform_fields_" . $random_id . "'",
			"id='field_" . $form['id'] . '_'                                 => "id='field_" . $random_id . '_',
			"for='input_" . $form['id'] . '_'                                => "for='input_" . $random_id . '_',
			"id='input_" . $form['id'] . '_'                                 => "id='input_" . $random_id . '_',
			"'gform_submit_button_" . $form['id'] . "'"                      => "'gform_submit_button_" . $random_id . "'",
			'"gf_submitting_' . $form['id'] . '"'                            => '"gf_submitting_' . $random_id . '"',
			"'gf_submitting_" . $form['id'] . "'"                            => "'gf_submitting_" . $random_id . "'",
			'#gform_ajax_frame_' . $form['id']                               => '#gform_ajax_frame_' . $random_id,
			'#gform_wrapper_' . $form['id']                                  => '#gform_wrapper_' . $random_id,
			'#gform_' . $form['id']                                          => '#gform_' . $random_id,
			"trigger('gform_post_render', [" . $form['id']                   => "trigger('gform_post_render', [" . $random_id,
			'gformInitSpinner( ' . $form['id'] . ', '                        => 'gformInitSpinner( ' . $random_id . ', ',
			"trigger('gform_page_loaded', [" . $form['id']                   => "trigger('gform_page_loaded', [" . $random_id,
			"'gform_confirmation_loaded', [" . $form['id'] . ']'             => "'gform_confirmation_loaded', [" . $random_id . ']',
			'gf_apply_rules(' . $form['id'] . ', '                           => 'gf_apply_rules(' . $random_id . ', ',
			'gform_confirmation_wrapper_' . $form['id']                      => 'gform_confirmation_wrapper_' . $random_id,
			'gforms_confirmation_message_' . $form['id']                     => 'gforms_confirmation_message_' . $random_id,
			'gform_confirmation_message_' . $form['id']                      => 'gform_confirmation_message_' . $random_id,
			'if(formId == ' . $form['id'] . ')'                              => 'if(formId == ' . $random_id . ')',
			"window['gf_form_conditional_logic'][" . $form['id'] . ']'       => "window['gf_form_conditional_logic'][" . $random_id . ']',
			"trigger('gform_post_conditional_logic', [" . $form['id'] . ', ' => "trigger('gform_post_conditional_logic', [" . $random_id . ', ',
			'gformShowPasswordStrength("input_' . $form['id'] . '_'          => 'gformShowPasswordStrength("input_' . $random_id . '_',
			"gformInitChosenFields('#input_" . $form['id'] . '_'             => "gformInitChosenFields('#input_" . $random_id . '_',
			"jQuery('#input_" . $form['id'] . '_'                            => "jQuery('#input_" . $random_id . '_',
			'gforms_calendar_icon_input_' . $form['id'] . '_'                => 'gforms_calendar_icon_input_' . $random_id . '_',
			"id='ginput_base_price_" . $form['id'] . '_'                     => "id='ginput_base_price_" . $random_id . '_',
			"id='ginput_quantity_" . $form['id'] . '_'                       => "id='ginput_quantity_" . $random_id . '_',
			'gfield_price_' . $form['id'] . '_'                              => 'gfield_price_' . $random_id . '_',
			'gfield_quantity_' . $form['id'] . '_'                           => 'gfield_quantity_' . $random_id . '_',
			'gfield_product_' . $form['id'] . '_'                            => 'gfield_product_' . $random_id . '_',
			'ginput_total_' . $form['id']                                    => 'ginput_total_' . $random_id,
			'GFCalc(' . $form['id'] . ', '                                   => 'GFCalc(' . $random_id . ', ',
			'gf_global["number_formats"][' . $form['id'] . ']'               => 'gf_global["number_formats"][' . $random_id . ']',
			'gform_next_button_' . $form['id'] . '_'                         => 'gform_next_button_' . $random_id . '_',
			'gform_previous_button_' . $form['id'] . '_'                     => 'gform_previous_button_' . $random_id . '_',
			$hidden_field                                                     => "<input type='hidden' name='gform_random_id' value='" . $random_id . "' />" . $hidden_field,
			// GF 2.9.9+
			"data-formid='" . $form['id']                                    => "data-formid='" . $random_id,
		];

		$strings = apply_filters('gform_multiple_instances_strings', $strings);

		foreach($strings as $find => $replace){
			$form_string = str_replace($find, $replace, $form_string);
		}

		return $form_string;
	}
}

new GF_Multiple_Form_Instances();
