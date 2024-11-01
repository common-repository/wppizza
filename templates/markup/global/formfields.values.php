<?php
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
 /****************************************************************************************
 *
 *
 *
 *
 *	filters available:
 *	[after]
 *	('wppizza_filter_formfields_values', $markup, $formfields): filters markup ($markup = array(),$formfields = array())
 ****************************************************************************************/
?>
<?php

	do_action('wppizza_formfields_values', $formfields, $page);

	foreach($formfields as $key => $formfield){

		do_action('wppizza_formfield_value_'.$key.'', $formfield, $page);		

		/*
			div wrap open
		*/
		$markup['div_'.$key.'_'] = '<div class="' .$formfield['class'] .'">';

			/*
				label output
			*/
			$markup['label_'.$key.'']= '<label>'. $formfield['label'] .' </label>';

			/*
				value output - since 3.16.2: wrap in label too to have a better chance of the value in orderform and confirmation page to have the same style 
			*/
			$markup['value_'.$key.'']= '<label>'.$formfield['value'].'</label>';

		/*
			div wrap close
		*/
		$markup['_div_'.$key.'']= '</div>';

	}

?>