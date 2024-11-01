<?php
/**
* WPPIZZA_MODULE_ALLERGENS Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_MODULE_ALLERGENS
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
class WPPIZZA_MODULE_ALLERGENS{

	private $settings_page = 'additives';/* which admin subpage (identified there by this->class_key) are we adding this to */


	private $section_key = 'allergens';/* must be unique */


	function __construct() {
		/**********************************************************
			[add settings to admin]
		***********************************************************/
		if(is_admin()){
			/* add admin options settings page*/
			add_filter('wppizza_filter_settings_sections_'.$this->settings_page.'', array($this, 'admin_options_settings'), 10, 5);
			/* add admin options settings page fields */
			add_action('wppizza_admin_settings_section_fields_'.$this->settings_page.'', array($this, 'admin_options_fields_settings'), 10, 5);
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
			[adding new allergen]
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
		
			/** create some (albeit empty) default values */
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
			/*->*** which allergens in item ***/
			$wppizza_meta_box[$this->section_key]='';
			$wppizza_meta_box[$this->section_key].="<div class='".WPPIZZA_SLUG."_option_meta ".WPPIZZA_SLUG."_option_meta_".$this->section_key."'>";

			$wppizza_meta_box[$this->section_key].="<label class='".WPPIZZA_SLUG."-meta-label'>".__('Allergens', 'wppizza-admin').": </label>";
			
			
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

    	//**allergens**//
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
			$settings['sections'][$this->section_key] =  __('Allergens', 'wppizza-admin');
		}
		/*help*/
		if($help){
			$settings['help'][$this->section_key][] = array(
				'label'=>__('Manage Allergens', 'wppizza-admin'),
				'description'=>array(
					__('Some meals or beverages may - or may not - contain allergens.', 'wppizza-admin'),
					__('Add any allergens here and select them at any meal / beverage that contains these allergens, or indeed distinctly indicate any menu item that does not contain an allergen.', 'wppizza-admin'),
					__('Allergens will be listed before additives.', 'wppizza-admin'),
					__('If you wish you can additionally add an icon in front of the allergens in the list of additives/allergens added to the footnotes on each page.', 'wppizza-admin'),
					__('If "icon in title" is enabled, associated icons will also be displayed in the title of the menu item instead of its textual ident.', 'wppizza-admin'),
					__('By default, allergens will be sorted alphabetically.', 'wppizza-admin'),
					__('However, you can use the "sort" field to customise the sortorder. If you do, your choosen sort id will be used to identify the allergens in the frontend so you want to make sure to have unique identifiers/sort id\'s', 'wppizza-admin'),
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
				$allergens_in_use = wppizza_options_in_use($field);
				foreach($wppizza_options[$field] as $key=>$values){
					echo"".$this->wppizza_admin_section_options($field, $key, $values, $allergens_in_use[$field]);
				}
			}
			echo"</div>";
			/** add new button **/
			echo"<div id='wppizza-".$field."-add' class='wppizza_admin_add'>";
				echo "<input type='button' id='wppizza_add_".$field."' class='button' value='".__('add allergen', 'wppizza-admin')."' />";
			echo"</div>";
		}
	}


	/**
		[available sizes of meal items or add new via ajax]
	**/
	private function wppizza_admin_section_options($field, $key, $values=null, $allergens_in_use=null){

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
						
							$icon_options['allergens'] = array( 
								'label' => __('Allergens', 'wppizza-admin') ,
								'items' => array(
									 'gluten' => __('Gluten', 'wppizza-admin'),
									 'lupin' => __('Lupin', 'wppizza-admin'),
									 'celery' => __('Celery', 'wppizza-admin'),
									 'crustaceans' => __('Crustaceans', 'wppizza-admin'),
									 'milk' => __('Milk/Lactose', 'wppizza-admin'),
									 'sulphites' => __('Sulphites', 'wppizza-admin'),									 	
									 'sesame' => __('Sesame', 'wppizza-admin'),
									 'molluscs' => __('Molluscs', 'wppizza-admin'),
									 'mustard' => __('Mustard', 'wppizza-admin'),
									 'nuts' => __('Nuts', 'wppizza-admin'),
									 'eggs' => __('Eggs', 'wppizza-admin'),
									 'fish' => __('Fish', 'wppizza-admin'),
									 'soybeans' => __('Soybeans', 'wppizza-admin'),
									 'peanuts' => __('Peanuts', 'wppizza-admin'),
								)
							);
							
							$icon_options['no_allergens'] = array( 
								'label' => __('Excl. Allergens', 'wppizza-admin') ,
								'items' => array(
									'no_gluten' => __('No Gluten', 'wppizza-admin'),
									'no_lupin' => __('No Lupin', 'wppizza-admin'),
									'no_celery' => __('No Celery', 'wppizza-admin'),
									'no_crustaceans' => __('No Crustaceans', 'wppizza-admin'),
									'no_milk' => __('No Milk/Lactose', 'wppizza-admin'),
									'no_sulphites' => __('No Sulphites', 'wppizza-admin'),									 	
									'no_sesame' => __('No Sesame', 'wppizza-admin'),
									'no_molluscs' => __('No Molluscs', 'wppizza-admin'),
									'no_mustard' => __('No Mustard', 'wppizza-admin'),
									'no_nuts' => __('No Nuts', 'wppizza-admin'),
									'no_eggs' => __('No Eggs', 'wppizza-admin'),
									'no_fish' => __('No Fish', 'wppizza-admin'),
									'no_soybeans' => __('No Soybeans', 'wppizza-admin'),
									'no_peanuts' => __('No Peanuts', 'wppizza-admin'),								
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
					
					/*
						show icon in title too as well as in legend 
					*/
					$str.="<span>";
						$str.="<label>";
							$str.="".__('icon in title', 'wppizza-admin').":";
							$str.="<input name='".WPPIZZA_SLUG."[".$field."][".$key."][iconintitle]' type='checkbox' value='1' ".checked(!empty($values['iconintitle']),true,false)." />";
						$str.="</label>";					
					$str.="</span>";					
										
					
					$str.="<span class='".WPPIZZA_SLUG."-option-inuse ".WPPIZZA_SLUG."-".$field."-inuse'>";
						if(!isset($allergens_in_use[$key])){
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
			default allergens
		*/
		$options[$this->section_key] = array(
			0=>array( 'sort'=>'A', 'name'=>esc_html__('Gluten', 'wppizza'), 'icon' => 'gluten'),
			1=>array( 'sort'=>'A*', 'name'=>esc_html__('Gluten Free', 'wppizza'), 'icon' => 'no_gluten'),
			2=>array( 'sort'=>'B', 'name'=>esc_html__('Crustaceans', 'wppizza'), 'icon' => 'crustaceans'),
			3=>array( 'sort'=>'B*', 'name'=>esc_html__('Crustacean Free', 'wppizza'), 'icon' => 'no_crustaceans'),
			4=>array( 'sort'=>'C', 'name'=>esc_html__('Eggs', 'wppizza'), 'icon' => 'eggs'),
			5=>array( 'sort'=>'C*', 'name'=>esc_html__('Egg Free', 'wppizza'), 'icon' => 'no_eggs'),
			6=>array( 'sort'=>'D', 'name'=>esc_html__('Fish', 'wppizza'), 'icon' => 'fish'),
			7=>array( 'sort'=>'D*', 'name'=>esc_html__('Fish Free', 'wppizza'), 'icon' => 'no_fish'),
			8=>array( 'sort'=>'E', 'name'=>esc_html__('Peanuts', 'wppizza'), 'icon' => 'peanuts'),
			9=>array( 'sort'=>'E*', 'name'=>esc_html__('Peanut Free', 'wppizza'), 'icon' => 'no_peanuts'),
			10=>array( 'sort'=>'F', 'name'=>esc_html__('Soybeans', 'wppizza'), 'icon' => 'soybeans'),
			11=>array( 'sort'=>'F*', 'name'=>esc_html__('Soybean Free', 'wppizza'), 'icon' => 'no_soybeans'),
			12=>array( 'sort'=>'G', 'name'=>esc_html__('Milk / Lactose', 'wppizza'), 'icon' => 'milk'),
			13=>array( 'sort'=>'G*', 'name'=>esc_html__('Milk / Lactose Free', 'wppizza'), 'icon' => 'no_milk'),
			14=>array( 'sort'=>'H', 'name'=>esc_html__('Nuts', 'wppizza'), 'icon' => 'nuts'),
			15=>array( 'sort'=>'H*', 'name'=>esc_html__('Free from Nuts', 'wppizza'), 'icon' => 'no_nuts'),
			16=>array( 'sort'=>'L', 'name'=>esc_html__('Celery', 'wppizza'), 'icon' => 'celery'),
			17=>array( 'sort'=>'L*', 'name'=>esc_html__('Free from Celery', 'wppizza'), 'icon' => 'no_celery'),
			18=>array( 'sort'=>'M', 'name'=>esc_html__('Mustard', 'wppizza'), 'icon' => 'mustard'),
			19=>array( 'sort'=>'M*', 'name'=>esc_html__('No Mustard', 'wppizza'), 'icon' => 'no_mustard'),
			20=>array( 'sort'=>'N', 'name'=>esc_html__('Sesame', 'wppizza'), 'icon' => 'sesame'),
			21=>array( 'sort'=>'N*', 'name'=>esc_html__('Sesame Free', 'wppizza'), 'icon' => 'no_sesame'),
			22=>array( 'sort'=>'O', 'name'=>esc_html__('Sulphites', 'wppizza'), 'icon' => 'sulphites'),
			23=>array( 'sort'=>'O*', 'name'=>esc_html__('Sulphite Free', 'wppizza'), 'icon' => 'no_sulphites'),
			24=>array( 'sort'=>'P', 'name'=>esc_html__('Lupin', 'wppizza'), 'icon' => 'lupin'),
			25=>array( 'sort'=>'P*', 'name'=>esc_html__('Lupin Free', 'wppizza'), 'icon' => 'no_lupin'),
			26=>array( 'sort'=>'R', 'name'=>esc_html__('Molluscs', 'wppizza'), 'icon' => 'molluscs'),
			27=>array( 'sort'=>'R*', 'name'=>esc_html__('No Molluscs', 'wppizza'), 'icon' => 'no_molluscs'),		
		);
		/* 
			allow filtering 
		*/
		$options[$this->section_key] = apply_filters('wppizza_filter_install_default_allergens', $options[$this->section_key]);		
		
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
						
						$options[$this->section_key][$key] = array( 
							'sort' => $sort, 
							'name' => wppizza_validate_string($values['name']), 
							'icon' => wppizza_validate_string($values['icon']), 
							'iconintitle' => (!empty($values['iconintitle']) ? true : false ) ,
						);	
						
					}
				
				}
			
			}

			/*
				in case someone does something really daft (editing post pages and sizes at the same time in 2 different windows)....
				make sure we do not delete something that really should be there.
			*/
			global $wppizza_options;
			
			$allergens_in_use = wppizza_options_in_use($this->section_key);
			
			if(!empty($allergens_in_use[$this->section_key])){
			foreach($allergens_in_use[$this->section_key] as $allergen_key){
				
				if(!isset($options[$this->section_key][$allergen_key])){
						$options[$this->section_key][$allergen_key] = $wppizza_options[$this->section_key][$allergen_key];
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
$WPPIZZA_MODULE_ALLERGENS = new WPPIZZA_MODULE_ALLERGENS();
?>