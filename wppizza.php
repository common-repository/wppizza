<?php
#* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
#* Plugin Name: WPPizza
#* Plugin URI: https://wordpress.org/extend/plugins/wppizza/
#* Description: A Restaurant Plugin (not only for Pizza)  
#* Version: 3.19
#* Requires PHP: 5.3+
#* Author: ollybach
#* Author URI: https://www.wp-pizza.com
#* License:     GPL2
#* Text Domain: wppizza
#* Domain Path: lang
#*
#* License:
#*
#* Copyright 2012 ollybach (dev@wp-pizza.com)
#*
#* This program is free software; you can redistribute it and/or modify
#* it under the terms of the GNU General Public License, version 2, as
#* published by the Free Software Foundation.
#*
#* This program is distributed in the hope that it will be useful,
#* but WITHOUT ANY WARRANTY; without even the implied warranty of
#* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#* GNU General Public License for more details.
#*
#* You should have received a copy of the GNU General Public License
#* along with this program; if not, write to the Free Software
#* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#* or see <http://www.gnu.org/licenses/>.
#*
#* @package WPPizza
#* @category Core
#* @author ollybach
#*
#* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * /

/***************************************************************
*
*	Simply exit if accessed directly
*
***************************************************************/
if ( ! defined( 'ABSPATH' ) ) {exit();}

/***************************************************************
*
*	for simplicities sake lets put this here at the top
*	MUST ALWAYS BE SET IN LINE WITH PLUGIN VERSION NUMBER ABOVE
*
*	@since v3
*
*	@since v3.10
*	major version number of plugin
*	[for convenience in plugin/theme development]
*
***************************************************************/
define('WPPIZZA_VERSION', '3.19');
define('WPPIZZA_VERSION_MAJOR', '3');

/***************************************************************
*
*
*	[CLASS]
*	@since v3
*
*
***************************************************************/
if (!class_exists( 'WPPIZZA' )){
	class WPPIZZA {

		/*
		* @var WPPIZZA
	 	* @since 3.0
	 	*/
		private static $instance;

		/*
			setting properties 
			to make php 8.2+ / 9.0 happy...
		*/
		public $dbcookie;
		public $categories;
		public $helpers;
		public $gateways;
		public $email;
		public $admin_helper;
		public $markup_orderinfo;
		public $markup_openingtimes;
		public $markup_minicart;
		public $markup_maincart;
		public $markup_navigation;
		public $markup_search;
		public $markup_additives;
		public $markup_foodtype;
		public $markup_options;
		public $markup_pickup_choice;
		public $markup_totals;
		public $markup_pages;
		public $templates_menu_items;
		public $templates_email_print;
		public $admin_dashboard_widgets;
		public $sales_data;
		public $user;
		public $db;
		public $order;
		public $cron;
		public $register_gateways;
		public $user_caps;


		/*
		* @var session
	 	* @since 3.0
	 	*/
		public $session;



		function __construct() {
		
			/* 
				some - somewhat inconsequentiial - setup actions to provide some useful links perhaps 
				in the WP admin plugin page (wp-admin/plugins.php) for this plugin
				@since 3.16
			*/
			//add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);// FOR REFERENCE CURRENTLY NOT IMPLEMENTED
			add_filter('plugin_row_meta',     array($this, 'plugin_meta_links'), 10, 2);
		}

		/***************************************************************
		* Main WPPIZZA
		*
		* To insures only one instance of WPPIZZA exists in memory
		*
		* @since 3.0
		* @static
		* @staticvar array $instance
		* @uses WPPIZZA::setup_constants() setup constants
		* @uses WPPIZZA::requires() Include all required files
		* @uses WPPIZZA::load_textdomain() load the language files
		*
		* @return WPPIZZA
		****************************************************************/
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPPIZZA ) ) {

				self::$instance = new WPPIZZA;

				/**load text domain**/
				add_action( 'init', array( self::$instance, 'load_plugin_textdomain'));
				/**load custom functions.php - if exists**/
				add_action( 'after_setup_theme', array( self::$instance, 'load_custom_functions'));

				/*setting up some constants*/
				self::$instance->wppizza_constants(__FILE__);
    			/**include all required files **/
				self::$instance->requires();
				/**cookies**/
				self::$instance->dbcookie					= new WPPIZZA_DBCOOKIE();				
				/**sessions**/
				self::$instance->session					= new WPPIZZA_SESSIONS();
				/**categories**/
				self::$instance->categories					= new WPPIZZA_CATEGORIES_SORTED();
				/**global helper functions**/
				self::$instance->helpers					= new WPPIZZA_HELPERS();
				/**gateways**/
				self::$instance->gateways   				= new WPPIZZA_GATEWAYS();
				/**emails**/
				self::$instance->email   					= new WPPIZZA_EMAIL();
				/**admin only**/
				if (is_admin()) {
					/**register gateways**/
					self::$instance->register_gateways   	= new WPPIZZA_REGISTER_GATEWAYS();
					/**admin user caps**/
					self::$instance->user_caps				= new WPPIZZA_USER_CAPS();
				}

				/**
					admin helper functions (order history mainly) - these might also be used outside admin area
					(so moved outside is_admin() conditional since v3.5) !
				**/
				self::$instance->admin_helper			= new WPPIZZA_ADMIN_HELPERS();

				/**
					templates | shortcodes | widget markup | sales data
				**/
				self::$instance->markup_orderinfo			= new WPPIZZA_MARKUP_ORDERINFO();		/* shortcode or enabled in cart widget */
				self::$instance->markup_openingtimes		= new WPPIZZA_MARKUP_OPENINGTIMES();	/* shortcode or enabled in cart widget */
				self::$instance->markup_minicart			= new WPPIZZA_MARKUP_MINICART();		/* shortcode or enabled in cart widget */
				self::$instance->markup_maincart			= new WPPIZZA_MARKUP_MAINCART();		/* shortcode or cart widget */
				self::$instance->markup_navigation			= new WPPIZZA_MARKUP_NAVIGATION();		/* shortcode or navigation widget */
				self::$instance->markup_search				= new WPPIZZA_MARKUP_SEARCH(); 			/* shortcode or search widget */
				self::$instance->markup_additives			= new WPPIZZA_MARKUP_ADDITIVES();		/* shortcode or in menu items, menu items loop */
				self::$instance->markup_foodtype			= new WPPIZZA_MARKUP_FOODTYPE();		/* shortcode or in menu items, menu items loop */
				self::$instance->markup_options				= new WPPIZZA_MARKUP_OPTIONS();			/* shortcode to output some wppizza options */
				self::$instance->markup_pickup_choice		= new WPPIZZA_MARKUP_PICKUP_CHOICE();	/* shortcode and called in cart|orderpage templates */
				self::$instance->markup_totals				= new WPPIZZA_MARKUP_TOTALS(); 			/* shortcode only */
				self::$instance->markup_pages				= new WPPIZZA_MARKUP_PAGES();			/* auto (orderpage|confirmationpage|thankyoupage) and "orderhistory" shortcode */
				self::$instance->templates_menu_items		= new WPPIZZA_MARKUP_MENU_ITEMS();		/* single item, categories loop, add to cart button, bestsellers*/
				self::$instance->templates_email_print		= new WPPIZZA_MARKUP_EMAIL_PRINT();		/* email / print templates */
				self::$instance->admin_dashboard_widgets	= new WPPIZZA_DASHBOARD_WIDGETS();		/* auto dashboard widgets */
				self::$instance->sales_data					= new WPPIZZA_SALES_DATA();				/* auto sales data results */


				/**
					user login / out/ register forms
					upadate details form
					registration emails / redirects
					etc
				**/
				self::$instance->user						= new WPPIZZA_USER();/* login form, registration, emails etc */
				self::$instance->db							= new WPPIZZA_DB();/* query insert update delete etc    */
				self::$instance->order						= new WPPIZZA_ORDER();/*     */
				self::$instance->cron						= new WPPIZZA_CRON();/*  wppizza wp cronjobs   */

			}

		return self::$instance;
		}


		/*************************************************************************************
		* wppizza constants
		*
		* @access private
		* @since 3.0
		* @return void
		*************************************************************************************/
		private function wppizza_constants($PLUGIN_FILE_ABS_PATH) {
			require_once(dirname($PLUGIN_FILE_ABS_PATH) .'/includes/global.constants.inc.php');
		}

		/*************************************************************************************
		*	load wppizzas functions.php
		*	- if exists in theme/childtheme directory as ./wppizza/functions.php
		* @since 3.0
		* @return void
		*************************************************************************************/
		function load_custom_functions(){
			if(WPPIZZA_LOCATE_DIR !='' && file_exists(WPPIZZA_TEMPLATE_DIR . '/functions.php')){
		    	include_once( WPPIZZA_TEMPLATE_DIR . '/functions.php');
		    }
		}

	    /*************************************************************************************
	    * load text domain on init.
		* @since 3.0
		* @return void
	    *************************************************************************************/
	  	public function load_plugin_textdomain(){
	  		/*
	  		NOTE: BOTH only required on admin as frontend strings get added to wppizza->localization (options table) on intall
	  		and are subsequently used from there.
	  		localization is split for convenience to enable frontend localization into more languages
	  		without having to translate the whole backend too (although that would be ideal of course)
	  		*/
	  		if(is_admin()){
	        	// admin localization strings
	        	load_plugin_textdomain('wppizza-admin', false, dirname(plugin_basename( __FILE__ ) ) . '/lang' );
	        	// load after admin to insert default localization strings
	        	load_plugin_textdomain('wppizza', false, dirname(plugin_basename( __FILE__ ) ) . '/lang' );
	  		}else{
	        	// frontend dev constants - not loaded by default (but can be enabled by constant) as it's kind of overkill loading these for very little benefit,
	        	if(WPPIZZA_DEV_LOAD_TEXTDOMAIN){
	        		load_plugin_textdomain('wppizza_dev', false, dirname(plugin_basename( __FILE__ ) ) . '/lang' );
	        	}
	  		}
	    }

		/*************************************************************************************
		 * Include our required files / classes
		 *
		 * @access private
		 * @since 3.0
		 * @return void
		 *************************************************************************************/
		private function requires(){
			require_once(WPPIZZA_PATH .'includes/setup/required.files.inc.php');
		}
		/*************************************************************************************
		 * Set some plugin settings links in the admin plugin page (next to deactivate link)
		 *	CURRENTLY NOT IMPLEMENTED
		 * @access private
		 * @since 3.16
		 * @return array
		 *************************************************************************************/
		function plugin_action_links($links, $file) {
			
			if (($file === plugin_basename(__FILE__) ) && (current_user_can('manage_options'))) {
				$settings = '<a href="'. admin_url('edit.php?post_type=wppizza&page=order_settings') .'">'. esc_html__('Order Settings', 'disable-gutenberg') .'</a>';
				array_unshift($links, $settings);
			}
		
		return $links;
		}

		/*************************************************************************************
		 * Set plugin settings links
		 *
		 * @access private
		 * @since 3.16
		 * @return array
		 *************************************************************************************/		
		function plugin_meta_links($links, $file) {
			
			if ($file === plugin_basename(__FILE__)) {
				$links[] = '<a target="_blank" rel="noopener noreferrer" href="https://docs.wp-pizza.com/getting-started/?section=setup" title="'. esc_attr__('Getting started', 'wppizza-admin') .'">'. esc_html__('Getting started', 'wppizza-admin') .'</a>';
				$links[] = '<a target="_blank" rel="noopener noreferrer" href="https://docs.wp-pizza.com" title="'. esc_attr__('Documentation', 'wppizza-admin') .'">'. esc_html__('Documentation', 'wppizza-admin') .'</a>';
				$links[] = '<a target="_blank" rel="noopener noreferrer" href="https://www.wp-pizza.com" title="'. esc_attr__('Homepage', 'wppizza-admin') .'">'. esc_html__('Homepage', 'wppizza-admin') .'</a>';
				$links[] = '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/plugins/wppizza/#developers" title="'. esc_attr__('Changelog', 'wppizza-admin') .'">'. esc_html__('Changelog', 'wppizza-admin') .'</a>';
				$links[] = '<a target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/wppizza/reviews/?rate=5#new-post" title="'. esc_attr__('Click here to rate and review this plugin on WordPress.org', 'wppizza-admin') .'">'. esc_html__('Rate this plugin', 'wppizza-admin') .'&nbsp;&raquo;</a>';
			}
			
		return $links;
		}		
		
	}
}


/*************************************************************************************
* The main function responsible for returning WPPIZZA to functions everywhere.
*
* Example: $wppizza = WPPIZZA();
*
* @since 3.0
* @return object WPPIZZA Instance
*************************************************************************************/
function WPPIZZA() {
	return WPPIZZA::instance();
}
function wppizza_ini() {
	WPPIZZA();
}
add_action( 'plugins_loaded', 'wppizza_ini');
?>