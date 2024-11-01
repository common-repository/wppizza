<?php
/**
* WPPIZZA_MODULE_ORDERSETTINGS_SURCHARGES Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_MODULE_ORDERSETTINGS_SURCHARGES
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.15
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
class WPPIZZA_MODULE_ORDERSETTINGS_SURCHARGES{

	private $settings_page = 'order_settings';/* which admin subpage (identified there by this->class_key) are we adding this to */

	private $section_key = 'surcharges';/* must be unique */


	function __construct() {
		/**********************************************************
			[add settings to admin]
		***********************************************************/
		if(is_admin()){
			/* add admin options settings page*/
			add_filter('wppizza_filter_settings_sections_'.$this->settings_page.'', array($this, 'admin_options_settings'), 43, 5);
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
	#	@since 3.15
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_settings($settings, $sections, $fields, $inputs, $help){

		/*section*/
		if($sections){
			$settings['sections'][$this->section_key] = __('Surcharges', 'wppizza-admin');
		}
		/*help*/
		if($help){
			$settings['help'][$this->section_key][] = array(
				'label'=>__('Surcharges', 'wppizza-admin'),
				'description'=>array(
					__('Adjust settings as appropriate according to the information provided next to each individual option.', 'wppizza-admin'),
					sprintf(__('Set labels in "%s -> Localizations -> (Sub)Totals".', 'wppizza-admin'), WPPIZZA_NAME),
					sprintf(__('If used, you want to ensure the display of these surcharges is enabled in your templates in "%s -> Templates".', 'wppizza-admin'), WPPIZZA_NAME),
					sprintf(__('Surcharges to apply depending on payment method selected can be set in "%s -> Gateways".', 'wppizza-admin'), WPPIZZA_NAME),
				)
			);
		}
		/*fields*/
		if($fields){

			$field = 'surcharge_percentage';
			$settings['fields'][$this->section_key][$field] = array( __('Surcharge: Percent (%)', 'wppizza-admin'), array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=>'',
				'description'=>array()
			));
			$field = 'surcharge_fixed';
			$settings['fields'][$this->section_key][$field] = array( __('Surcharge: Fixed amount', 'wppizza-admin'), array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=>'',
				'description'=>array()
			));
		}

		return $settings;
	}
	/*------------------------------------------------------------------------------
	#	[output option fields - setting page]
	#	@since 3.15
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_fields_settings($wppizza_options, $options_key, $field, $label, $description){

		if($field=='surcharge_percentage'){
			echo "<label>";
				echo "<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' size='2' type='text' value='".$wppizza_options[$options_key][$field]."' />";
				echo "".$label."";
				/** add taxrates  **/
				$trField = 'surcharge_percentage_tax';
				echo "<span style='margin:0 10px'>-</span>" . sprintf(__('Add %s percent tax to this surcharge.', 'wppizza-admin'), "<input id='".$trField."' name='".WPPIZZA_SLUG."[".$options_key."][".$trField."]' size='2' type='text' value='".$wppizza_options[$options_key][$trField]."' />")."";
			echo "</label>";
			echo"".$description."";
		}

		if($field=='surcharge_fixed'){
			echo "<label>";
				echo "<input id='".$field."' name='".WPPIZZA_SLUG."[".$options_key."][".$field."]' size='2' type='text' value='".$wppizza_options[$options_key][$field]."' />";
				echo "".$label."";
				/** add taxrates  **/
				$trField = 'surcharge_fixed_tax';
				echo "<span style='margin:0 10px'>-</span>" . sprintf(__('Add %s percent tax to this surcharge.', 'wppizza-admin'), "<input id='".$trField."' name='".WPPIZZA_SLUG."[".$options_key."][".$trField."]' size='2' type='text' value='".$wppizza_options[$options_key][$trField]."' />")."";
			echo "</label>";
			echo"".$description."";
		}

	}

	/*------------------------------------------------------------------------------
	#	[insert default option on install]
	#	$parameter $options array() | filter passing on filtered options
	#	@since 3.15
	#	@return array()
	------------------------------------------------------------------------------*/
	function options_default($options){

		$options[$this->settings_page]['surcharge_percentage'] = 0;
		$options[$this->settings_page]['surcharge_percentage_tax'] = 0;
		$options[$this->settings_page]['surcharge_fixed'] = 0;
		$options[$this->settings_page]['surcharge_fixed_tax'] = 0;

	return $options;
	}

	/*------------------------------------------------------------------------------
	#	[validate options on save/update]
	#
	#	@since 3.15
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
			$options[$this->settings_page]['surcharge_percentage']= wppizza_validate_float_pc($input[$this->settings_page]['surcharge_percentage'], 5);//max 5 fractions - should do one would think
			$options[$this->settings_page]['surcharge_percentage_tax']= wppizza_validate_float_pc($input[$this->settings_page]['surcharge_percentage_tax'], 5);//max 5 fractions - should do one would think
			$options[$this->settings_page]['surcharge_fixed'] =  defined('WPPIZZA_DECIMALS') ? wppizza_validate_float_pc($input[$this->settings_page]['surcharge_fixed'], (int)WPPIZZA_DECIMALS) : wppizza_validate_float_pc($input[$this->settings_page]['surcharge_fixed'], 2);// 2 or defined decimals
			$options[$this->settings_page]['surcharge_fixed_tax']= wppizza_validate_float_pc($input[$this->settings_page]['surcharge_fixed_tax'], 5);//max 5 fractions - should do one would think

		}

	return $options;
	}
}
/***************************************************************
*
*	[ini]
*
***************************************************************/
$WPPIZZA_MODULE_ORDERSETTINGS_SURCHARGES = new WPPIZZA_MODULE_ORDERSETTINGS_SURCHARGES();
?>