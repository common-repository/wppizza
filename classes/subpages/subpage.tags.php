<?php
/**
* WPPIZZA_TAGS Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_TAGS
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.15
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/


/************************************************************************************************************************
*
*
*	WPPIZZA_TAGS filters
*
*
************************************************************************************************************************/
class WPPIZZA_TAGS{
	/*
	* class ident
	* @var str
	* @since 3.15
	*/
	private $class_key = 'tags';/*to help consistency throughout class in various places*/
	private $submenu_caps_title ;
	private $submenu_priority = 0;
	/******************************************************************************************************************
	*
	*	[CONSTRUCTOR]
	*
	*
	*	@since 3.15
	*
	******************************************************************************************************************/
	function __construct() {

		$this->submenu_caps_title=__('Tags','wppizza-admin');

		/*register capabilities for this page*/
		add_filter('wppizza_filter_define_caps', array( $this, 'wppizza_filter_define_caps'), $this->submenu_priority);

	}
	/*********************************************************
	*
	*	[define caps]
	*	@since 3.15
	*
	*********************************************************/
	function wppizza_filter_define_caps($caps){
		/**
			add editing capability for this page
		**/
		$caps[$this->class_key]=array('name'=>$this->submenu_caps_title ,'cap'=>''.WPPIZZA_SLUG.'_cap_'.$this->class_key.'');

	return $caps;
	}
}
/***************************************************************
*
*	[ini]
*
***************************************************************/
$WPPIZZA_TAGS = new WPPIZZA_TAGS();
?>