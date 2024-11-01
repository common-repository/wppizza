<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*########################################################################################################################
#
#
#	CLASS - WPPIZZA_DBCOOKIE ~ WORK IN PROGESS TO EVENTUALLY MOVE AWAY FROM PHHP SESSIONS
#	Using cookies and db entries to handle session data instead of php sessions
#	
# 	@package WPPIZZA
# 	@subpackage WPPIZZA_DBCOOKIE 
# 	@copyright Copyright (c) 2022, wp-pizza.com
# 	@since 3.16
#
########################################################################################################################*/
if (!class_exists( 'WPPIZZA' ) || class_exists( 'WPPIZZA_DBCOOKIE' )) {
	return;
}
class WPPIZZA_DBCOOKIE{

    #private $pluginOptions;
    
    public $cookieName;
    public $cookieValue;    
    
	
	/************************************************************
	*
	*	[construct]
	*
	*************************************************************/
	function __construct($cookieName = WPPIZZA_SLUG, $cookieValue = '') {

		/*
			cookie name
		*/			
		$this->cookieName = $cookieName;

		/*
			cookie value
		*/			
		$this->cookieValue = $cookieValue;


	    /*****************************************************
	    *
	    * admin - setup
	    *
	   	******************************************************/
		if(is_admin()){

		}

	    /*****************************************************
	    *
	    * frontend
	    *
	   	******************************************************/
		if(!is_admin()){
		}

	}

/**************************************************************************************************
*
*
*	[INIT]
*
*
**************************************************************************************************/
	/**
	* Set cookie
	*/
	function set_cookie($value) {	
		
		$this -> cookie = array(
			'name' => $this->cookieName,
			'value' => $value ,
			'expiry' => '',
		);
	
	return;
	
	}



/**************************************************************************************************
*
*
*	[SETUP]
*
*
**************************************************************************************************/
### WORK IN PROGRESS ##
	
}
?>