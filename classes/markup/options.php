<?php
/**
* WPPIZZA_MARKUP_OPTIONS Class
*
* @package     WPPIZZA
* @subpackage  WPPizza Options
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.12.14
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/

/* ================================================================================================================================= *
*
*
*	SIMPLY OUTPUTS SOME WPPIZZA OPTIONS SET BY SHORTCODE
*	CLASS - WPPIZZA_MARKUP_OPTIONS
*
*
*
* ================================================================================================================================= */

class WPPIZZA_MARKUP_OPTIONS{

	/******************************************************************************
	*
	*
	*	[construct]
	*
	*
	*******************************************************************************/
	function __construct() {
	}

	/******************************************************************************
	*
	*
	*	[methods]
	*
	*
	*******************************************************************************/
	/***************************************
		[apply attributes]
	***************************************/
	function attributes($atts=null){
		/**get markup**/
		$markup = $this->get_markup($atts);
		return $markup;
	}

	/***************************************
		[markup]
	***************************************/
	function get_markup($atts){
		global $wppizza_options;

		if(empty($atts['page']) || empty($atts['parameter'])){
			return '';
		}
		//only non arrays (otherwsie simply using the global $wppizza_options would be more appropriate one would think)
		if(!empty($wppizza_options[$atts['page']][$atts['parameter']]) && !is_array($wppizza_options[$atts['page']][$atts['parameter']])){
			$markup = $wppizza_options[$atts['page']][$atts['parameter']];
			return $markup;
		}else{
			return '';		
		}
	return;
	}

}
?>