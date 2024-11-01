<?php
/**
* WPPIZZA_COMPAT Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_COMPAT
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.13
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/


/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
*#
*#
*#
*#	[COMPATIBILITY/LEGACY FILTERS/ACTIONS]
*#	filters / actions to keep certain functionalities intact until
*#	other plugins/gateways (I know about) get updated to take advantage of
*#	newly added functionalities - I/e keep legacy compatibilities for older plugins
*#
*#
*#
*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*/
class WPPIZZA_COMPAT{

	function __construct() {


		/************************************************************************
			[FRONTEND - INCLUDING AJAX as inline wppizza parameters also get updated dynamically]
		************************************************************************/
		if(!is_admin() || (is_admin() && defined('DOING_AJAX') && DOING_AJAX) ){
			/*
				set js flag if we still want to reload the checkout page
				on pickup/delivery switching (as opposed to ajax page/form replace)
			*/
			add_filter( 'wppizza_filter_js_localize', array( $this, 'reload_on_pickup_delivery'));


			/*
				set js flag if we still want to reload the checkout page
				on gateway switching (as opposed to ajax page/form replace)
			*/
			add_filter( 'wppizza_filter_js_localize', array( $this, 'reload_on_gatewaychange'));


			/*
				set js flag for pre-3.13 gateways that need to have wppizza_gateway_init run
				distinctlly on checkout page load
			*/
			add_filter( 'wppizza_filter_js_localize', array( $this, 'legacy_gateways_init'));

		}

		/************************************************************************
			[ADMIN - INCLUDING (frontend) ajax]
		************************************************************************/
		#if(is_admin()){
		#
		#}

		/************************************************************************
			[ADMIN - EXCLUDING ajax]
		************************************************************************/
		#if( is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX) ){
		#
		#}

		/************************************************************************
			[AJAX ONLY]
		************************************************************************/
		#if( is_admin() && defined('DOING_AJAX') && DOING_AJAX ){
		#
		#}

	}


	/*******************************************************************************
	*	set a js flag to still reload checkout page when switching from 
	*	pickup to delivery when other legacy plugins are used that are not (yet)  
	*	compatible with the ajax replacing of the checkout page/form
	*	@since 3.13
	*	@param array
	*	@return array
	*******************************************************************************/
	function reload_on_pickup_delivery($parameters){
			
		global $wppizza_options;
		
		/*
			check versions of installed plugins that are not (yet)
			compatible with ajax page/form reload on pickup/delivery switch
		*/
		//WPPizza DBP : needs v4.1+ if installed to take advantage of non-reloading
		if(defined('WPPIZZA_DBP_CURRENT_VERSION') && version_compare( WPPIZZA_DBP_CURRENT_VERSION, '4.1', '<' ) ){
			$page_reload = 1;
		}
			
		
		/*
			set (legacy/compatability) flag to reload checkout page
			also allow simple use of constant for now to force this
			if we have missed something
		*/		
		if(!empty($page_reload) || !empty($wppizza_options['tools']['compat_legacy_checkout']) ){
			$parameters['compat']['puDel'] = 1;
		}
		
	return $parameters;
	}


	/*******************************************************************************
	*	set a js flag to still reload checkout page when switching GATEWAYS 
	*	when some legacy plugins/gateways are used that are not (yet)  
	*	compatible with the ajax replacing of the checkout page/form
	*	@since 3.13
	*	@param array
	*	@return array
	*******************************************************************************/
	function reload_on_gatewaychange($parameters){
		global $wppizza_options;				
		/*
			check versions of installed GATEWAYS that are not (yet)
			compatible with ajax page/form reload on gateway change
			we can ignore all gateways that are redirect only as this
			really only affects gateways that capture payments 
			inline / on page in some form
		*/
		$gateway_compat = array(
			array('constant' => 'WPPIZZA_GATEWAY_AUTHORIZENET_VERSION', 	'min_version' => '4.2' ),//Authorize.net  : needs v4.2+ if installed to take advantage of non-reloading		
			array('constant' => 'WPPIZZA_GATEWAY_PAYPAL_VERSION', 			'min_version' => '5.3' ),//Paypal : needs v5.3+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_EWAY_VERSION', 			'min_version' => '1.1' ),//eWay : needs v1.1+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_STRIPE_VERSION', 			'min_version' => '4.2.1' ),//Stripe : needs v4.2.1+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_SAGEPAY_CURRENT_VERSION', 	'min_version' => '2.1' ),//Sagepay : needs v2.1+ if installed to take advantage of non-reloading
		);
		
		foreach($gateway_compat as $param){
			if(defined(''.$param['constant'].'') && version_compare( constant($param['constant']), $param['min_version'], '<' ) ){	
				$page_reload = 1;
			break;//any one should trigger this
			}
		}
		
		/*
			set (legacy/compatability) flag to reload checkout page
			also allow simple use of constant for now to force this
			if we have missed something
		*/		
		if(!empty($page_reload) || !empty($wppizza_options['tools']['compat_legacy_checkout']) ){
			$parameters['compat']['gwChg'] = 1;
		}
		
		return $parameters;
	}


	/*******************************************************************************
	*	set a js flag to still reload checkout page when switching GATEWAYS 
	*	when some legacy plugins/gateways are used that are not (yet)  
	*	compatible with the ajax replacing of the checkout page/form
	*	@since 3.13
	*	@param array
	*	@return array
	*******************************************************************************/
	function legacy_gateways_init($parameters){
		
		global $wppizza_options;
						
		/*
			check versions of installed GATEWAYS that are not (yet)
			compatible with ajax page/form reload on gateway change
			we can ignore all gateways that are redirect only as this
			really only affects gateways that capture payments 
			inline / on page in some form
		*/
		$gateway_compat = array(
			array('constant' => 'WPPIZZA_GATEWAY_AUTHORIZENET_VERSION', 	'min_version' => '4.2' , 'ident' => 'AUTHORIZENET'),//Authorize.net  : needs v4.2+ if installed to take advantage of non-reloading		
			array('constant' => 'WPPIZZA_GATEWAY_PAYPAL_VERSION', 			'min_version' => '5.3' , 'ident' => 'PAYPAL'),//Paypal : needs v5.3+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_EWAY_VERSION', 			'min_version' => '1.1' , 'ident' => 'EWAY'),//eWay : needs v1.1+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_STRIPE_VERSION', 			'min_version' => '4.2' , 'ident' => 'STRIPE'),//Stripe : needs v4.2+ if installed to take advantage of non-reloading
			array('constant' => 'WPPIZZA_GATEWAY_SAGEPAY_CURRENT_VERSION', 	'min_version' => '2.1' , 'ident' => 'SAGEPAY'),//Sagepay : needs v2.1+ if installed to take advantage of non-reloading
		);
		
		foreach($gateway_compat as $param){
			if(defined(''.$param['constant'].'') && version_compare( constant($param['constant']), $param['min_version'], '<' ) && wppizza_selected_gateway() == $param['ident'] ){	
				$run_wppizza_gateway_init = true;
			break;//any one should trigger this
			}
		}
		
		/*
			set (legacy/compatability) flag to reload checkout page
			also allow simple use of constant for now to force this
			if we have missed something
		*/		
		if(!empty($run_wppizza_gateway_init) || !empty($wppizza_options['tools']['compat_legacy_checkout']) ){
			$parameters['compat']['gwInit'] = true;
		}
		
		return $parameters;
	}



}
/***************************************************************
*
*	[ini]
*
***************************************************************/
$WPPIZZA_COMPAT = new WPPIZZA_COMPAT();
?>