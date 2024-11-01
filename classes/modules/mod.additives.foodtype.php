<?php
/**
* WPPIZZA_MODULE_FOODTYPE Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_MODULE_FOODTYPE
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.12.14
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
class WPPIZZA_MODULE_FOODTYPE{

	private $settings_page = 'additives';/* which admin subpage (identified there by this->class_key) are we adding this to */


	private $section_key = 'foodtype';/* must be unique */


	function __construct() {
		/**********************************************************
			[add settings to admin]
		***********************************************************/
		if(is_admin()){
			/* add admin options settings page*/
			add_filter('wppizza_filter_settings_sections_'.$this->settings_page.'', array($this, 'admin_options_settings'), 30, 5);
			/* add admin options settings page fields */
			add_action('wppizza_admin_settings_section_fields_'.$this->settings_page.'', array($this, 'admin_options_fields_settings'), 30, 5);
			/**add default options **/
			add_filter('wppizza_filter_setup_default_options', array( $this, 'options_default'));
			/**validate options**/
			add_filter('wppizza_filter_options_validate', array( $this, 'options_validate'), 10, 2 );
			/**metaboxes sizes/prices - priority same as submenu page **/
			add_filter('wppizza_filter_admin_metaboxes', array( $this, 'wppizza_filter_admin_add_metaboxes'), 60, 4);
			add_filter('wppizza_filter_admin_save_metaboxes',array( $this, 'wppizza_filter_admin_save_metaboxes'), 10, 3);
			/** admin ajax **/
			add_action('wppizza_ajax_admin_'.$this->settings_page.'', array( $this, 'admin_ajax'));
		}
	}
	/*******************************************************************************************************************************************************
	*
	* 	[admin ajax]
	*
	********************************************************************************************************************************************************/
	function admin_ajax($wppizza_options){

		//need right caps for access
		if( !current_user_can('wppizza_cap_'.$this->settings_page.'')){
			echo '<div class="wppizza-highlight">'.__('Access Denied', 'wppizza-admin').' ['.$this->section_key.']</div>';
			exit();
		}

		/*****************************************************
			[adding new foodtype]
		*****************************************************/
		if($_POST['vars']['field']==$this->section_key && isset($_POST['vars']['setKeys']) ){
		
			/**get next highest key available**/
			$nextKey=0;
			if(isset($_POST['vars']['setKeys']) && is_array($_POST['vars']['setKeys'])){
				$currentKeys=array();
				foreach($_POST['vars']['setKeys'] as $key_exists){
					$currentKeys[$key_exists['value']]=$key_exists['value'];
				}
				$highestKey=max($currentKeys);
				$nextKey=$highestKey+1;
			}
		
			/** cretae some (albeit empty) default values */
			$default_values['sort'] = '';
			$default_values['name'] = '';
			$default_values['icon'] = '';
		
		
			$output = $this->wppizza_admin_section_options($_POST['vars']['field'], $nextKey, $default_values);		
		
			print"".$output."";
			exit();
		}	
	}


	/*******************************************************************************************************************************************************
	*
	*
	*
	* 	[frontend filters]
	*
	*
	*
	********************************************************************************************************************************************************/



	/*******************************************************************************************************************************************************
	*
	*
	*
	* 	[add admin page options]
	*
	*
	*
	********************************************************************************************************************************************************/
	/*********************************************************
	*
	*	[add metaboxes]
	*	@since 3.0
	*
	*********************************************************/
	function wppizza_filter_admin_add_metaboxes($wppizza_meta_box, $meta_values, $meal_sizes, $wppizza_options){

		if(!empty($wppizza_options[$this->section_key])){
			/*->*** which foodtypes in item ***/
			$wppizza_meta_box[$this->section_key]='';
			$wppizza_meta_box[$this->section_key].="<div class='".WPPIZZA_SLUG."_option_meta ".WPPIZZA_SLUG."_option_meta_".$this->section_key."'>";

			$wppizza_meta_box[$this->section_key].="<label class='".WPPIZZA_SLUG."-meta-label'>".__('Type', 'wppizza-admin').": </label>";
			
			
				$wppizza_meta_box[$this->section_key].="<div>";/* wrapper div needed to make css->display:flex work here */
			
				asort($wppizza_options[$this->section_key]);//sort but keep index
			
				/*
					if we perhaps ever want to use chosen.js instead of the below buttons (but must be enabled in subpage.post and admin.common js too if we do)
					Note:saving of values would need to be checked still 
				*/
			//	$wppizza_meta_box[$this->section_key] .= '<select id="'.WPPIZZA_PREFIX.'-meta-'.$this->section_key.'" name="'.WPPIZZA_SLUG.'['.$this->section_key.']" class="'.WPPIZZA_PREFIX.'-meta-'.$this->section_key.'" multiple="multiple" >';
			//	foreach($wppizza_options[$this->section_key]  as $key=>$value){
			//		$wppizza_meta_box[$this->section_key] .= '<option value="'.$key.'" '.selected(in_array($key, $meta_values[$this->section_key]), true ,false).' >'.$value['name'].'</option>';	
			//	}
			//	$wppizza_meta_box[$this->section_key].="</select>";
			
			
				foreach($wppizza_options[$this->section_key]  as $key=>$value){
					$wppizza_meta_box[$this->section_key].="<label class='button'>";
					$wppizza_meta_box[$this->section_key].="<input name='".WPPIZZA_SLUG."[".$this->section_key."][".$key."]' size='5' type='checkbox' ". checked((isset($meta_values[$this->section_key]) && in_array($key,$meta_values[$this->section_key])),true,false)." value='".$key."' /> ".$value['name']."";
					$wppizza_meta_box[$this->section_key].="</label>";
				}

				$wppizza_meta_box[$this->section_key].="</div>";
			
			$wppizza_meta_box[$this->section_key].="</div>";
		}


		return $wppizza_meta_box;
	}

	/*********************************************************
	*
	*	[save metaboxes values]
	*	@since 3.0
	*
	*********************************************************/
	function wppizza_filter_admin_save_metaboxes($itemMeta, $item_id, $wppizza_options){

    	//**foodtypes**//
    	$itemMeta[$this->section_key]=array();
    	if(isset($_POST[WPPIZZA_SLUG][$this->section_key])){
    	foreach($_POST[WPPIZZA_SLUG][$this->section_key] as $key=>$val){
    		$itemMeta[$this->section_key][$key]				= (int)$_POST[WPPIZZA_SLUG][$this->section_key][$key];
    	}}

		return $itemMeta;
	}


	/*------------------------------------------------------------------------------
	#
	#
	#	[settings page]
	#
	#
	------------------------------------------------------------------------------*/

	/*------------------------------------------------------------------------------
	#	[settings section - setting page]
	#	@since 3.0
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_settings($settings, $sections, $fields, $inputs, $help){

		/*section*/
		if($sections){
			$settings['sections'][$this->section_key] =  __('Food Type', 'wppizza-admin');
		}
		/*help*/
		if($help){
			
			$settings['help'][$this->section_key][] = array(
				'label'=>__('Manage Type', 'wppizza-admin'),
				'description'=>array(
					__('Some meals or beverages may be of a certain type you may wish to indicate alongside the menu item title', 'wppizza-admin'),
					__('Define the types you would like to have available for selection here and select these as applicable in the individual menu item pages.', 'wppizza-admin'),
					__('By default, selected types will be sorted alphabetically, but you can use the "sort" field to customise the sortorder.', 'wppizza-admin'),
					__('If a food type is associated with an icon, the icon will be displayed, otherwise the selected sortid will be used. The full name/type will be displayed on hovering over icon or identification', 'wppizza-admin'),
					__('Note: Food Types will only be shown next to the menu item title unless specifically using shortcodes elsewhere.', 'wppizza-admin'),
				)
			);			
			
		}
		/*fields*/
		if($fields){
			$field = $this->section_key;
			$settings['fields'][$this->section_key][$field] = array( '', array(
				'value_key'=>$field,
				'option_key'=>$this->settings_page,
				'label'=> '',
				'description'=>array()
			));
		}


		return $settings;
	}
	/*------------------------------------------------------------------------------
	#	[output option fields - setting page]
	#	@since 3.0
	#	@return array()
	------------------------------------------------------------------------------*/
	function admin_options_fields_settings($wppizza_options, $options_key, $field, $label, $description){

		if($field==$this->section_key){

			echo"<div id='wppizza_".$field."_options' class='wppizza_admin_options'>";/*new sizes will be appended to this div's id by ajax*/

			if(!empty($wppizza_options[$field])){
				asort($wppizza_options[$field]);//sort but keep index
				/* get allergens that are in use */
				$options_in_use = wppizza_options_in_use($field);
				foreach($wppizza_options[$field] as $key=>$values){
					echo"".$this->wppizza_admin_section_options($field, $key, $values, $options_in_use[$field]);
				}
			}
			echo"</div>";
			/** add new button **/
			echo"<div id='wppizza-".$field."-add' class='wppizza_admin_add'>";
				echo "<input type='button' id='wppizza_add_".$field."' class='button' value='".__('add food type', 'wppizza-admin')."' />";
			echo"</div>";
		}
	}


	/**
		[available sizes of meal items or add new via ajax]
	**/
	private function wppizza_admin_section_options($field, $key, $values=null, $options_in_use=null){

		$str='';

		$str.="<div class='wppizza_option wppizza_".$this->section_key."_option'>";

					/*
						for easy checking for existing keys when adding new
					*/
					$str.="<input id='wppizza_".$field."_".$key."' class='wppizza-getkey' name='wppizza-getkey[".$key."]' type='hidden' value='".$key."'>";
					
					$str.="<span class='wppizza_label_50'>";
						$str.="ID: ".$key."";
					$str.="</span>";

					$str.="<span>";
						$str.="".__('sort', 'wppizza-admin')."/".__('ident', 'wppizza-admin').":";
						$str.="<input name='".WPPIZZA_SLUG."[".$field."][".$key."][sort]' size='3' type='text' value='". $values['sort'] ."' placeholder=''/>";
					$str.="</span>";					
					
					$str.="<span>";
						$str.="".__('name', 'wppizza-admin').":";
						$str.="<input name='".WPPIZZA_SLUG."[".$field."][".$key."][name]' size='30' type='text' value='". $values['name'] ."' placeholder=''/>";
					$str.="</span>";					
					
					$str.="<span>";
						$str.="".__('icon', 'wppizza-admin').":";
						
						
						$str.="<select name='".WPPIZZA_SLUG."[".$field."][".$key."][icon]'>";
							/* default option */
							$str.="<option value=''> === ".__('No Icon', 'wppizza-admin')." === </option>";	
							
							/* icons etc */
							$icon_options = array();
													
							$icon_options['spice'] = array( 
								'label' => __('Spice Level', 'wppizza-admin') ,
								'items' => array(
									'mild' => __('Mild', 'wppizza-admin'),
									'medium' => __('Medium', 'wppizza-admin'),
									'hot' => __('Hot', 'wppizza-admin'),
									'very_hot' => __('Very Hot', 'wppizza-admin'),
								)
							);							
							
							$icon_options['other'] = array( 
								'label' => __('Other', 'wppizza-admin') ,
								'items' => array(
									'kosher' => __('Kosher', 'wppizza-admin'),
									'halal' => __('Halal', 'wppizza-admin'),
									'vegetarian' => __('Vegetarian', 'wppizza-admin'),
									'vegan' => __('Vegan', 'wppizza-admin'),
									'frozen' => __('Frozen', 'wppizza-admin'),
								)
							);							
							
							
							foreach($icon_options as $ident => $aGroup){
								$str.="<optgroup label=' -- ".$aGroup['label']." -- '>";
								
								//sort if it's not a "spice" group  
								if(!in_array($ident , array('spice') )){
									asort($aGroup['items']);
								}
								
								foreach($aGroup['items'] as $k => $v ) {
									$str.="<option value='".$k."' ".selected($values['icon'], $k ,false)." > ".$v." </option>";
								}
									
							}
						
						$str.="</select>";
						
					$str.="</span>";					
					
					$str.="<span class='".WPPIZZA_SLUG."-option-inuse ".WPPIZZA_SLUG."-".$field."-inuse'>";
						if(!isset($options_in_use[$key])){
							$str.="<a href='javascript:void(0);' class='".WPPIZZA_SLUG."-delete ".$field." ".WPPIZZA_SLUG."-dashicons dashicons-trash' title='".__('delete', 'wppizza-admin')."'></a>";
						}else{
							$str.="".__('in use', 'wppizza-admin')."";
						}
					$str.="</span>";										

		$str.="</div>";

	return $str;
	}



	/****************************************************************
	*
	*	[insert default option on install]
	*	$parameter $options array() | filter passing on filtered options
	*	@since 3.0
	*	@return array()
	*
	****************************************************************/
	function options_default($options){
		/*
			default food types
		*/
		$options[$this->section_key] = array(
			
			0=>array( 'sort'=>'+', 'name'=>esc_html__('Mild', 'wppizza'), 'icon' => 'mild' ),	
			1=>array( 'sort'=>'++', 'name'=>esc_html__('Spicy', 'wppizza'), 'icon' => 'medium'),	
			2=>array( 'sort'=>'+++', 'name'=>esc_html__('Hot', 'wppizza'), 'icon' => 'hot'),		
			3=>array( 'sort'=>'++++', 'name'=>esc_html__('Very Hot', 'wppizza'), 'icon' => 'very_hot'),	
			4=>array( 'sort'=>'Fz', 'name'=>esc_html__('Frozen', 'wppizza'), 'icon' => 'frozen'),
			5=>array( 'sort'=>'Hl', 'name'=>esc_html__('Halal', 'wppizza'), 'icon' => 'halal'),	
			6=>array( 'sort'=>'Ks', 'name'=>esc_html__('Kosher', 'wppizza'), 'icon' => 'kosher'),	
			7=>array( 'sort'=>'V', 'name'=>esc_html__('Vegetarian', 'wppizza'), 'icon' => 'vegetarian'),		
			8=>array( 'sort'=>'Vg', 'name'=>esc_html__('Vegan', 'wppizza'), 'icon' => 'vegan'),
			
		);
		/* allow filtering */
		$options[$this->section_key] = apply_filters('wppizza_filter_install_default_foodtype', $options[$this->section_key]);
		
	return $options;
	}

	/*------------------------------------------------------------------------------
	#	[validate options on save/update]
	#
	#	@since 3.0
	#	@return array()
	------------------------------------------------------------------------------*/
	function options_validate($options, $input){
		/**make sure we get the full array on install/update**/
		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}
		/********************************
		*	[validate]
		********************************/
		if(isset($_POST[''.WPPIZZA_SLUG.'_'.$this->settings_page.''])){

			$options[$this->section_key] = array();//initialize as empty array
			if(!empty($input[$this->section_key])){
				
				foreach($input[$this->section_key] as $key=>$values){
					
					if(trim($values['name'])!=''){
						
						$sort = ($values['sort']!='' ) ? preg_replace("/[^a-zA-Z0-9\-_+*:]/","", $values['sort'] ) : '';
						
						$options[$this->section_key][$key] = array( 'sort' => $sort, 'name' => wppizza_validate_string($values['name']), 'icon' => wppizza_validate_string($values['icon']) );	
						
					}
				
				}
			
			}

			/*
				in case someone does something really daft (editing post pages and sizes at the same time in 2 different windows)....
				make sure we do not delete something that really should be there.
			*/
			global $wppizza_options;
			
			$options_in_use = wppizza_options_in_use($this->section_key);
			
			if(!empty($options_in_use[$this->section_key])){
			foreach($options_in_use[$this->section_key] as $foodtype_key){
				
				if(!isset($options[$this->section_key][$foodtype_key])){
						$options[$this->section_key][$foodtype_key] = $wppizza_options[$this->section_key][$foodtype_key];
				}
			
			}}
		}
		
	return $options;
	}
}
/***************************************************************
*
*	[ini]
*
***************************************************************/
$WPPIZZA_MODULE_FOODTYPE = new WPPIZZA_MODULE_FOODTYPE();
?>