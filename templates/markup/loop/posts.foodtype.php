<?php
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
 /****************************************************************************************
 *
 * this template is only used when added by shortcode i.e something like
 * [wppizza category="desserts" noheader="1" elements='title, thumbnail, content, additives, foodtype, prices']
 *
 * filters: wppizza_filter_post_foodtype_element
 * filters: wppizza_filter_post_foodtype_class
 * filters: wppizza_filter_post_foodtype_markup
 *
 ****************************************************************************************/
?>
<?php

	/*
		add foodtype  - if any 
	*/
	if( !empty($post_foodtype) ){

		/* wrap additives in span */
		$markup['element_foodtype_'] = '<'.$foodtype_loop_element.' id="' . $foodtype_loop_id .'" class="' . $foodtype_loop_class['additives'] .'" title="' . $txt['contains_additives'] . '">';

				/*
					foodtype associated with menu item
				*/
				$markup['element_foodtype'] = '';
				foreach($post_foodtype as $key=>$value){
					$markup['element_foodtype'] .= '<span id="'. $value['id'] . '" class="'. $value['class'] . '"  title="' . $value['name'] .'" >' . $value['ident'] . '</span>';
				}


		$markup['_element_foodtype'] = '</'.$foodtype_loop_element.'>';
	}

?>