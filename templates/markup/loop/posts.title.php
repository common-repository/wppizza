<?php
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
 /****************************************************************************************
 *
 *
 *
 * filters: wppizza_filter_post_title
 * filters: wppizza_filter_post_title_element
 * filters: wppizza_filter_post_title_class
 * filters: wppizza_filter_post_title_markup
 *
 ****************************************************************************************/
?>
<?php

	$markup['post_title_'] = '<'.$post_title_element.' id="' . $post_title_id . '" class="' . $post_title_class['elm'] . '">';

		$markup['post_title'] = '<span class="' . $post_title_class['title'] . '">' .$post_title . '</span>';


			/*
				add foodtype separately before and separated from additives/allergens - if any 
			*/
			if(!empty($post_foodtype)){
				
				/*
					wrap  in sup element
				*/
				$markup['sup_foodtype_'] = '<sup id="' . $post_foodtype_id .'" class="' . $post_title_class['foodtype'] .'">';				
				
					/*
						foodtype associated with menu item
					*/
					$markup['post_foodtype'] = '';
					foreach($post_foodtype as $key=>$value){
						$markup['post_foodtype'] .= '<span id="'. $value['id'] . '" class="'. $value['class'] . '"  title="' . $value['name'] .'" >' . $value['ident'] . '</span>';
					}
				
				
				$markup['_sup_foodtype'] = '</sup>';
								
			}


			/*
				add combined allergens / additives  - if any 
			*/
			if( !empty($post_allergens) || !empty($post_additives) ){
				

				/*wrap additives in sup element*/
				$markup['sup_'] = '<sup id="' . $post_additives_id .'" class="' . $post_title_class['additives'] .'" title="' . $txt['contains_additives'] . '">';

				
					/*
						allergens associated with menu item - if any 
					*/
					$markup['post_allergens'] = '';
					foreach($post_allergens as $key=>$value){
						$markup['post_allergens'] .= '<span id="'. $value['id'] . '" class="'. $value['class'] . '"  title="' . $value['name'] .'" >' . $value['ident'] . '</span>';
					}				
				
					/*
						additives associated with menu item
					*/
					$markup['post_additives'] = '';
					foreach($post_additives as $key=>$value){
						$markup['post_additives'] .= '<span id="'. $value['id'] . '" class="'. $value['class'] . '"  title="' . $value['name'] .'" >' . $value['ident'] . '</span>';
					}

				
				$markup['_sup'] = '</sup>';

			}

	$markup['_post_title'] = '</'.$post_title_element.'>';
?>