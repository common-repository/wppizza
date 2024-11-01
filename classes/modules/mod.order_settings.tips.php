<?php
/**
* WPPIZZA_MODULE_ORDERSETTINGS_TIPS Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_MODULE_ORDERSETTINGS_TIPS
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.12.13
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/


/************************************************************************************************************************
*
*
*
*
*
*
************************************************************************************************************************/
class WPPIZZA_MODULE_ORDERSETTINGS_TIPS{

	private $settings_page = 'order_settings';/* which admin subpage (identified there by this->class_key) are we adding this to */

	private $section_key = 'tips';/* must be unique */


	function __construct() {
		/**********************************************************
			[add settings to admin]
		***********************************************************/
		if(is_admin()){
			/* add admin options settings page*/
			add_filter('wppizza_filter_settings_sections_'.$this->settings_page.'', array($this, 'admin_options_settings'), 45, 5);
			/* add admin options settings page fields */
			add_action('wppizza_admin_settings_section_fields_'.$this->settings_page.'', array($this, 'admin_options_fields_settings'), 10, 5);
			/**add default options **/
			add_filter('wppizza_filter_setup_default_options', array( $this, 'options_default'));
			/**validate options**/
			add_filter('wppizza_filter_options_validate', array( $this, 'options_validate'), 10, 2 );
		}

	}


	/*******************************************************************************************************************************************************
	*
	*
	*
	* 	[add admin page options]
	*
	*
	*
	********************************************************************************************************************************************************/

	/*------------------------------------------------------------------------------
	#
	#
	#	[settings page]
	#
	#
	------------------------------------------------------------------------------*/

	/*------------------------------------------------------------------------------
	#	[settings section - setting page]
	#	@since 3.12.13
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_settings($settings, $sections, $fields, $inputs, $help){

		/*section*/
		if($sections){
			$settings['sections'][$this->section_key] = __('Tips', 'wppizza-admin');
		}
		/*help*/
		if($help){
			$settings['help'][$this->section_key][] = array(
				'label'=>__('Tips', 'wppizza-admin'),
				'description'=>array(
					sprintf(__('Please note that tips must be enabled in "%s -> Order Form" first for the settings here to have any effect.', 'wppizza-admin'), WPPIZZA_NAME),
					__('Adjust settings as appropriate according to the information provided next to each individual option.', 'wppizza-admin'),
				)
			);
		}
		/*fields*/
		if($fields){

			$field = 'tips_display';
			$settings['fields'][$this->section_key][$field] = array( __('Tips Display', 'wppizza-admin'), array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=>'',
				'description'=>array()
			));

			$field = 'tips_percentage_options';
			$settings['fields'][$this->section_key][$field] = array( __('Percentages selectable', 'wppizza-admin'), array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=>__('Enter comma separated percentages you want to make available for easy selection to the customer (use "." if you need decimal fractions)', 'wppizza-admin'),
				'description'=>array()
			));

			$field = 'tips_percentage_default';
			$settings['fields'][$this->section_key][$field] = array( __('Default percentage', 'wppizza-admin'), array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=>__('Enter a preselected tips percentage value.', 'wppizza-admin').' <span class="wppizza-highlight">'.__('Must be one of the selectable percentages values above (or 0 to ignore).', 'wppizza-admin').'</span>',
				'description'=>array()
			));

		}

		return $settings;
	}
	/*------------------------------------------------------------------------------
	#	[output option fields - setting page]
	#	@since 3.12.13
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_fields_settings($wppizza_options, $options_key, $field, $label, $description){

		if($field=='tips_display'){
			echo "<label>";
				// tips input value shown - default, as normal
				echo "".__('Default', 'wppizza-admin')."<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' type='radio'  ".checked($wppizza_options[$options_key][$field], 1, false)." value='1' /> ";
				// adding percentage dropdown to choose from next to Tips label
				echo "".__('Add Percentage selection', 'wppizza-admin')."<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' type='radio'  ".checked($wppizza_options[$options_key][$field],2,false)." value='2' /> ";

				// tips input value hidden, only percentage dropdown shown - currently not implemented (might not ever get implemented in fact)
				//echo "".__('Percentages Only', 'wppizza-admin')."<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' type='radio'  ".checked($wppizza_options[$options_key][$field],3,false)." value='3' /> ";

				echo "".$label."";
			echo "</label>";
			echo"".$description."";
		}

		if($field=='tips_percentage_options'){
			echo "<label>";
				echo "<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' size='15' type='text' value='".implode(',',$wppizza_options[$options_key][$field])."' />";
				echo "".$label."";
			echo "</label>";
			echo"".$description."";
		}

		if($field=='tips_percentage_default'){
			echo "<label>";
				echo "<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' size='1' type='text' value='".$wppizza_options[$options_key][$field]."' />";
				echo "".$label."";
			echo "</label>";
			echo"".$description."";
		}



	}

	/*------------------------------------------------------------------------------
	#	[insert default option on install]
	#	$parameter $options array() | filter passing on filtered options
	#	@since 3.12.13
	#	@return array()
	------------------------------------------------------------------------------*/
	function options_default($options){

		$options[$this->settings_page]['tips_display'] = 1;
		$options[$this->settings_page]['tips_percentage_options'] = array(5,10,15,20);
		$options[$this->settings_page]['tips_percentage_default'] = 0;




	return $options;
	}

	/*------------------------------------------------------------------------------
	#	[validate options on save/update]
	#
	#	@since 3.12.13
	#	@return array()
	------------------------------------------------------------------------------*/
	function options_validate($options, $input){
		/**make sure we get the full array on install/update**/
		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}
		/*
			settings
		*/
		if(isset($_POST[''.WPPIZZA_SLUG.'_'.$this->settings_page.''])){
			$options[$this->settings_page]['tips_display'] = in_array($input[$this->settings_page]['tips_display'], array(1,2)) ? absint($input[$this->settings_page]['tips_display']) : 1;
			$options[$this->settings_page]['tips_percentage_options']=  array_unique(wppizza_validate_array($input[$this->settings_page]['tips_percentage_options'],'wppizza_validate_float_pc'));
			$options[$this->settings_page]['tips_percentage_default']= ( in_array(wppizza_validate_float_pc($input[$this->settings_page]['tips_percentage_default']), $options[$this->settings_page]['tips_percentage_options'])) ? wppizza_validate_float_pc($input[$this->settings_page]['tips_percentage_default']) : 0 ;
		}

	return $options;
	}
}
/***************************************************************
*
*	[ini]
*
***************************************************************/
$WPPIZZA_MODULE_ORDERSETTINGS_TIPS = new WPPIZZA_MODULE_ORDERSETTINGS_TIPS();
?>