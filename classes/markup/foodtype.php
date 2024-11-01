<?php
/**
* WPPIZZA_MARKUP_FOODTYPE Class
*
* @package     WPPIZZA
* @subpackage  WPPizza Additives
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.12.14
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/

/* ================================================================================================================================= *
*
*
*
*	CLASS - WPPIZZA_MARKUP_FOODTYPE
*
*
*
* ================================================================================================================================= */

class WPPIZZA_MARKUP_FOODTYPE{

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
		static $unique_id=0;$unique_id++;


		/*********************
			get all foodtype
		*********************/
		$foodtype = wppizza_all_foodtype();


		/*********************
			set unique id
		*********************/
		$id	= WPPIZZA_PREFIX.'-ftypes-'.$unique_id;

		/*********************
			set/add classes using attributes
		*********************/
		$class= array();
		$class[] = WPPIZZA_PREFIX.'-ftypes';
		//add icon class
		$has_icons = array_filter(wppizza_array_column($foodtype, 'icon'));
		if(!empty($has_icons)){
		$class[] = WPPIZZA_PREFIX.'-ftypes-icons';			
		}
		if(!empty($atts['class'])){
			$class[] = esc_html($atts['class']);
		}

		/*
			implode for output
		*/
		$class = trim(implode(' ', $class));

		/*********************
			ini markup array
		*********************/
		$markup = array();

		/*********************
			get markup
		*********************/
		if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/foodtype.php')){
			require(WPPIZZA_TEMPLATE_DIR.'/markup/global/foodtype.php');
		}else{
			require(WPPIZZA_PATH.'templates/markup/global/foodtype.php');
		}

		/*********************
			apply filter if required and implode for output
		*********************/
		$markup = apply_filters('wppizza_filter_foodtype_widget_markup', $markup, $atts, $unique_id, $foodtype);
		$markup = trim(implode('', $markup));


	return $markup;
	}

}
?>