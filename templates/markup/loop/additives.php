<?php
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
 /****************************************************************************************
 *
 *
 *
 *
 *
 * filter after : wppizza_filter_menu_additives_markup 
 ****************************************************************************************/
?>
<?php
	$markup['additives_'] = '<div class="' . $class . '">';


		foreach($allergens as $key=>$allergen){
			$markup['allergen_'.$key] = '<span class="' . $allergen['class'] . '"><sup>' . $allergen['ident'] . '</sup>' . $allergen['name'] . '</span>';
		}

		foreach($additives as $key=>$additive){
			$markup['additive_'.$key] = '<span class="' . $additive['class'] . '"><sup>' . $additive['ident'] . '</sup>' . $additive['name'] . '</span>';
		}

	$markup['_additives'] = '</div>';
?>