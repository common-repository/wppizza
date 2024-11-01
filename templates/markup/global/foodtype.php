<?php
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
 /****************************************************************************************
 *
 *	 [before]
 *	class :  set in shortcode attributes
 *
 *	[after]
 *	('wppizza_filter_foodtype_widget_markup', $markup, $atts): filters markup ($markup = array(),$atts = array(), $foodtype=array())
 ****************************************************************************************/
?>
<?php
	$markup['div_'] = '<div id="' . $id . '" class="' . $class . '">';

		foreach($foodtype as $key=>$ftype){
			$markup['foodtype_'.$key] = '<span class="' . $ftype['class'] . '"><sup>' . $ftype['ident'] . '</sup>' . $ftype['name'] . '</span> ';
		}
	$markup['_div'] = '</div>';
?>