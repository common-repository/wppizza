<?php
/**
* WPPIZZA_SALES_DATA Class
*
* @package     WPPIZZA
* @subpackage  Sales Data
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.8
*
*
* Note: this was originally part of subpages.reports.php (v3.0) but has put into a dedicated separate class to allow access from elewhere
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/


/************************************************************************************************************************
*
*
*	WPPIZZA_SALES_DATA
*
*
************************************************************************************************************************/
class WPPIZZA_SALES_DATA{

	/*********************************************************
	*
	*	[CONSTRUCTOR]
	*
	**********************************************************/
	function __construct() {
		/*
			adding "detailed" report to dropdown options
			@since 3.9
		*/
		add_filter('wppizza_filter_csv_export_select', array( $this, 'wppizza_report_detailed') );

		/*
			generating csv according to key set/selected in wppizza_filter_report_export_select
			@since 3.9
		*/
		add_filter('wppizza_filter_csv_export_detailed', array( $this, 'wppizza_report_detailed_csv'), 10 , 3);

	}

	/*********************************************************
	*
	*	[adding "detailed" report to dropdown options]
	*
	*	@param array
	*	@since 3.9
	*
	*	@return array
	*********************************************************/
	function wppizza_report_detailed($array){

		$array['detailed'] = __('Detailed','wppizza_admin');

	return $array;
	}
	/*********************************************************
	*
	*	[generating the csv]
	*
	*	@param str
	*	@param array
	*	@param str
	*	@since 3.9
	*
	*	@return str
	*********************************************************/
	function wppizza_report_detailed_csv($csv, $data, $type){

		/*******************
		#	order data will always be grouped by blogs, even if only one blog and/or it's not a multisite setup
		#
		#	(in a multisite setup orders from multiple blogs might be returned depending on settings
		#	and additionally have different order form settings labels and/or counts)
		#
		#	so we need to always loop through each blog (though there might only be one of course)
		*******************/

		/*****************
			ini an empty array
			we implode later
			to the csv string
		*****************/
		$csvData = array();

		/*****************
			ini an empty array
			that will contain
			the csv rows of each blog
		*****************/
		$csvBlogRows = array();

		/*****************
			columns used
			per blog to add
			more empty separators
			if necessary when putting it
			all into a single csv
		*****************/
		$columnCount = array();

		/****************
		#	first of all, make sure there's actually some data returned
		****************/
		if(!empty($data['blogs'])){
			foreach($data['blogs'] as $blogID => $blogInfo){



				/* * * * * * * *
				#	set top row  - blog info
				* * * * * * * */

				$blog_info = array();
				$blog_info['blog_id'] = '"(Blog #'.$blogInfo['blog_id'].')"';
				$blog_info['blogname'] = '"'.substr($blogInfo['blogname'], 0, 18 ).'"';//lets not have massive strings here
				//$blog_info['siteurl'] = substr($blogInfo['siteurl'], 0, 16 );// skip this for now. might mess up column widths and blogname should do really

				/* ---------------------------
					allow blog info filtering
				--------------------------- */
				$blog_info = apply_filters('wppizza_filter_csv_bloginfo_'.$type.'', $blog_info, $blogInfo, $blogID);

				/*****
					max columns this blog
				*****/
				$columnCountBlogInfo[$blogID] = count($blog_info);

				/*****
					convert bloginfo comma separated (to not mess up column widths too much) string
				*****/
				$csvBlogRows[$blogID]['blog_info'] = implode(',', $blog_info);

				/* * * * * * * *
				<!-- end blog info -->
				* * * * * * * */




				/* * * * * * * *
				#	set second row - column labels
				* * * * * * * */
				$columns = array();

				/* order transaction details */
				$columns['order_id'] = __('Order ID', 'wppizza_admin');
				$columns['order_date_formatted'] = __('Date', 'wppizza_admin');
				$columns['order_paid_by'] = __('Paid By', 'wppizza_admin');
				$columns['payment_status'] = __('Status', 'wppizza_admin');
				$columns['transaction_id'] = __('Transaction ID', 'wppizza_admin');
				$columns['self_pickup'] = __('Pickup', 'wppizza_admin');

				/* WP (un)-registered users */
				$columns['user_registered'] = __('Registered User', 'wppizza_admin');
				$columns['user_username'] = __('Username', 'wppizza_admin');
				$columns['user_fullname'] = __('Full Name', 'wppizza_admin');
				$columns['user_email'] = __('eMail', 'wppizza_admin');

				/* customer info labels - entered in checkout page - related to each order looping through each formfield */
				foreach($data['formfields'][$blogID] as $ffID => $ffValues){
					$columns['customer_'.$ffID] = $ffValues['label'];
				}
				$columns['customer_ip_address'] = __('IP Address', 'wppizza_admin');

				/* order items */
				$columns['order_verbose'] = __('Order Verbose', 'wppizza_admin');
				$columns['number_of_items'] = __('Products Count', 'wppizza_admin');
				$columns['products_verbose'] = __('Products Verbose', 'wppizza_admin');

				/* financials */
				$columns['currencyiso'] = __('Currency', 'wppizza_admin');
				$columns['total_price_items'] = __('Price Items', 'wppizza_admin');
				$columns['total_discounts'] = __('Discounts', 'wppizza_admin');
				$columns['delivery_charges'] = __('Delivery Charges', 'wppizza_admin');
				$columns['handling_charges'] = __('Handling Charges', 'wppizza_admin');
				$columns['tips'] = __('Tips', 'wppizza_admin');

				//taxes verbose
				$columns['tax_rates_verbose'] = __('Tax Rates Verbose', 'wppizza_admin');

				//taxes details
				$columns['tax_rate_main'] = __('Tax Rate (Main)', 'wppizza_admin');
				$columns['tax_total_main'] = __('Tax Total (Main)', 'wppizza_admin');
				$columns['tax_rate_alt'] = __('Tax Rate (Alt)', 'wppizza_admin');
				$columns['tax_total_alt'] = __('Tax Total (Alt)', 'wppizza_admin');
				$columns['tax_rate_shipping'] = __('Tax Rate (Shipping)', 'wppizza_admin');
				$columns['tax_total_shipping'] = __('Tax Total (Shipping)', 'wppizza_admin');

				//taxes summary
				$columns['taxes_included'] = __('Taxes Included', 'wppizza_admin');
				$columns['taxes'] = __('Tax Total', 'wppizza_admin');

				//total
				$columns['total'] = __('Total', 'wppizza_admin');


				/* ---------------------------
					allow column name filtering
				--------------------------- */
				$columns = apply_filters('wppizza_filter_csv_columns_'.$type.'', $columns );


				/*****
					max columns this blog
				*****/
				$columnCount[$blogID] = count($columns);

				/*****
					convert column names to comma separated string and add linebreak
				*****/
				$csvBlogRows[$blogID]['columns'] = implode(',', $columns);

				/* * * * * * * *
				<!-- end column labels -->
				* * * * * * * */



				/* * * * * * * *
					loop through orders - matching values to column
				* * * * * * * */

				$orders = array();

				foreach($data['orders'][$blogID] as $oId => $values){

					$order = array();

					/*
						order transaction details
					*/
					$order['order_id'] = $oId ;

					$order['order_date_formatted'] = date('Y-m-d H:i:s', $values['order']['order_date_timestamp']);

					$order['order_paid_by'] = $values['order']['initiator'];

					$order['payment_status'] = $values['order']['payment_status'];

					$order['transaction_id'] = $values['order']['transaction_id'];

					$order['self_pickup'] = !empty($values['order']['self_pickup']) ? 'Y' : 'N';


					/*
						WP (un)-registered users
					*/
					$user_id = $values['order']['wp_user_id'];//user id associated with this order
					$user_data = $data['users'][$user_id];//user date associated with this user id (if any)

					$order['user_registered'] = !empty($user_id) ? 'Y' : 'N';
					$order['user_username'] = !empty($user_data) ? $user_data['user_login'] : '';
					$order['user_fullname'] = !empty($user_data) ? $user_data['first_name'] . ' ' . $user_data['last_name'] : '' ;
					$order['user_email'] = !empty($user_data) ? $user_data['user_email']  : '' ;


					/*
						customer info labels - entered in checkout page -
						related to each order looping through each formfield
						make sure to wrap each line in quotes
					*/
					foreach($data['formfields'][$blogID] as $ffID => $ffValues){
						$order['customer_'.$ffID] = $values['customer'][$ffID];
					}
					$order['customer_ip_address'] = $values['customer']['ip_address'];


					/*
						order items
					*/
					$order['order_verbose'] = $values['order']['verbose'];

					$order['number_of_items'] = $values['order']['number_of_items'];

					$productsVerbose = array();
					foreach($values['items'] as $iId => $item){
						//remove empty sizes brackets
						if(empty($item['price_label'])){
							$productsVerbose[$iId] = $item['quantity'].'x '.$item['title'].' - '.$item['pricetotal_formatted'] ;
						}else{
							$productsVerbose[$iId] = $item['quantity'].'x '.$item['title'].' ['.$item['price_label'].'] - '.$item['pricetotal_formatted'] ;
						}
					}
					$order['products_verbose'] = implode(PHP_EOL, $productsVerbose);


					/*
						financials
					*/
					$order['currencyiso'] = $values['order']['currencyiso'];

					$order['total_price_items'] = $values['order']['total_price_items'];

					$order['total_discounts'] = (0 - $values['order']['total_discounts']);//make negative

					$order['delivery_charges'] = $values['order']['delivery_charges'];

					$order['handling_charges'] = $values['order']['handling_charges'];

					$order['tips'] = $values['order']['tips'];

					//taxes verbose
					$taxRatesVerbose = array();
					if(!empty($values['order']['tax_by_rate'])){
						foreach($values['order']['tax_by_rate'] as $taxType=>$rates){
							$taxRatesVerbose[] = $taxType . ' @' . $rates['rate'].'%';
						}
					}
					$order['tax_rates_verbose'] =  implode(PHP_EOL, $taxRatesVerbose);//wrap in quotes to make sure linebreaks work

					//taxes details
					$order['tax_rate_main'] = $values['order']['taxrate'].'%';
					$order['tax_total_main'] = !empty($values['order']['tax_by_rate']['main']['total']) ? $values['order']['tax_by_rate']['main']['total'] : 0 ;
					$order['tax_rate_alt'] = $values['order']['taxrate_alt'].'%';
					$order['tax_total_alt'] =  !empty($values['order']['tax_by_rate']['alt']['total']) ? $values['order']['tax_by_rate']['alt']['total'] : 0 ;
					$order['tax_rate_shipping'] = $values['order']['taxrate_shipping'].'%';
					$order['tax_total_shipping'] = !empty($values['order']['tax_by_rate']['shipping']['total']) ? $values['order']['tax_by_rate']['shipping']['total'] : 0 ;

					//taxes totals
					$order['taxes_included'] = !empty($values['order']['tax_included']) ? 'Y' : 'N';
					$order['taxes'] = $values['order']['taxes'];


					//totals
					$order['total'] = $values['order']['total'];


					/* ---------------------------
						allow row data filtering
					--------------------------- */
					$order = apply_filters('wppizza_filter_csv_rows_'.$type.'', $order, $values, $user_data, $oId );


					/*****
						convert to comma separated string
						each value enclosed by quotes
						to make sure linebreaks are recognised
					*****/
					$order = array_map(array($this, 'sanitize_csv_values'), $order);
					$orders[$oId] = '"'.implode('","', $order).'"';
					/* clear parameter */
					unset($order);

				}
				/*****
					 join all orders adding linebreaks in between
				*****/
				$csvBlogRows[$blogID]['orders'] = $orders;
				/* clear parameter */
				unset($orders);


				/* * * * * * * *
				<!-- end orders -->
				* * * * * * * */
			}
		}

		/************************************************************
			create csv for each blog
			accounting for maximum columns per blog
			adding comma separators if  necessary
		************************************************************/
		//if there's no data anyway simply set maxColumns to 1
		$maxColumns = !empty($columnCount) ? max($columnCount) : 1;

		if(!empty($data['blogs'])){
			foreach($data['blogs'] as $blogID => $blogInfo){
				/*
					determine how many additional separators we need for each line of data of this blog
				*/
				$additionalSeparatorsBlogInfo = ($maxColumns - $columnCountBlogInfo[$blogID]);
				$additionalSeparatorsData = ($maxColumns - $columnCount[$blogID]);


				/* blog info */
				$csvBlogRows[$blogID]['blog_info'] = $csvBlogRows[$blogID]['blog_info']. str_repeat(",", $additionalSeparatorsBlogInfo);

				/* columns */
				$csvBlogRows[$blogID]['columns'] = $csvBlogRows[$blogID]['columns']. str_repeat(",", $additionalSeparatorsData);

				/* order data */
				$orders = array();
				foreach($csvBlogRows[$blogID]['orders'] as $oId => $order){
					$orders[$oId] = $order . str_repeat(",", $additionalSeparatorsData);
				}
				$csvBlogRows[$blogID]['orders'] = implode(PHP_EOL, $orders);

				/*******************************
					implode data for this blogid
				*******************************/
				$csvData[$blogID] = implode(PHP_EOL, $csvBlogRows[$blogID]);

			}
		}


		/* # * # * # * # * # * # * # * # * # * # * # * # * # * #
		#
		#	implode data for all blogids to a single string,
		#	for otput adding some linebreaks after each blog data
		#
		* # * # * # * # * # * # * # * # * # * # * # * # * # * # */
		$lineBreaks =  PHP_EOL . str_repeat(",", $maxColumns) . PHP_EOL . str_repeat(",", $maxColumns). PHP_EOL  ;
		$csv =  implode($lineBreaks, $csvData) . $lineBreaks;


	return $csv;
	}

	/*********************************************************
	*
	*	[sanitise csv values. just to be sure
	*	converting entities / removing tags, removing commas and quotes
	*	called before imploding order data to csv string ]
	*
	*	@since 3.9
	*	@param str
	*	@return str
	*
	*********************************************************/
	function sanitize_csv_values($value){

		/* a - serialize if needed */
		$value = maybe_serialize($value);
		/* b - strip tags */
		$value = wp_strip_all_tags($value);
		/* c - convert commas */
		$value = str_replace(',',";", $value);
		/* d - convert quotes */
		$value = str_replace('"',"`", $value);

	return $value;
	}

	/*********************************************************
	*
	*	[reports dataset]
	*
	*	@param bool
	*	@param mixed
	*	@param bool
	*
	*	@return array
	*********************************************************/
	function wppizza_report_dataset($export = false, $transient_expiry = false, $dashboard_widget = false, $args = false){


		global $wppizza_options;

		if( version_compare( PHP_VERSION, '5.3', '<' )) {
			print"<div id='wppizza-report-error'>".__('Sorry, reporting is only available with php >=5.3','wppizza-admin')."</div>";
			return;
		}

		global $wpdb,$blog_id;

			/*******
				passing on distinct arguments to
				simulate dropdown report _GET parameters
				when using functions outside reports screen
				(adding some very basic sanitation here too)
			******/
			if(!empty($args['range']['from'])){
				$_GET['from'] = wppizza_validate_alpha_only($args['range']['from']);
			}
			if(!empty($args['range']['to'])){
				$_GET['to'] = wppizza_validate_alpha_only($args['range']['to']);
			}
			if(!empty($args['type'])){
				$_GET['type'] = wppizza_latin_lowercase($args['type']);
			}


			/*
				using transients
			*/
			if($transient_expiry){
				if (false !== ( $transient_dataset_results = get_transient( WPPIZZA_TRANSIENT_REPORTS_NAME.'_'.$transient_expiry.'' ) ) ) {
					return $transient_dataset_results;
				}
			}


			$wpTime = WPPIZZA_WP_TIME;
			$wpYesterday = strtotime('-1 day', $wpTime);
			/*
				get all completed business days (i.e days where closing time is before now)
				within the last week start and end times (might cross midnight)
				omitting days where shop is closed
			*/
			$completedBusinessDays = wppizza_completed_businessdays($wpTime);

			$firstDayCurrentMonth = strtotime('first day of this month 00:00:00', $wpTime);
			$firstDayLastMonth = mktime(0, 0, 0, date("m")-1, 1, date("Y"));

			$reportCurrency=$wppizza_options['order_settings']['currency_symbol'];
			$reportCurrencyIso=$wppizza_options['order_settings']['currency'];
			$dateformat=get_option('date_format');
			$timeformat=get_option('time_format');
			$processOrder=array();

			/************************************************************************
				get all wppizza menu items by id and size
				set initially as unsold menu item and simply unset if bought
			************************************************************************/
			$getWppizzaMenuItems = wppizza_get_menu_items();
			$unsoldMenuItems=array();

			if(count($getWppizzaMenuItems)>0){
				/*loop through items*/
				foreach($getWppizzaMenuItems as $menuItem){

					$meta = get_post_meta($menuItem->ID, WPPIZZA_POST_TYPE, true );

					$sizes = isset($meta['sizes']) && isset($wppizza_options['sizes'][$meta['sizes']]) ? $wppizza_options['sizes'][$meta['sizes']] : array();

					/*loop through sizes*/
					if(is_array($sizes)){
					$noOfSizes = count($sizes);
					foreach($sizes as $sizekey=>$size){

						/*make key from id and size*/
						$miKey = $menuItem->ID.'.'.$sizekey;

						/* omit size(price label) display if only one size and set to be omoitted in layout */
						$priceLabel = $noOfSizes<=1 && !empty($wppizza_options['layout']['hide_single_pricetier']) ? '' : $size['lbl'] ;

						$unsoldMenuItems[$miKey] = array('ID' => $menuItem->ID, 'title' => $menuItem->post_title, 'sizekey' => $sizekey, 'price_label' => $priceLabel );
					}}
				}
			}
			/* quick tidy up of some done with parameter */
			unset($getWppizzaMenuItems);


			/************************************************************************
				overview query. do not limit by date to get totals
				any other query, add date range to query
			************************************************************************/
			$reportTypes=array();
			$reportTypes['today'] = array('lbl'=>__('Today','wppizza-admin'));
			/*
				completed business days in last week
				(omitting closed days)
			*/
			if(!empty($completedBusinessDays)){
				foreach($completedBusinessDays as $bDayKey=>$bDay){
					$reportTypes[$bDay['date']] = array('lbl'=>$bDay['lbl']);
				}
			}
			$reportTypes['7d'] = array('lbl'=>__('Last 7 days','wppizza-admin'));
			$reportTypes['14d'] = array('lbl'=>__('Last 14 days','wppizza-admin'));
			$reportTypes['30d'] = array('lbl'=>__('Last 30 days','wppizza-admin'));
			$reportTypes['ytd'] = array('lbl'=>__('Year to date','wppizza-admin'));
			$reportTypes['ly'] = array('lbl'=>__('Last year','wppizza-admin'));
			$reportTypes['tm'] = array('lbl'=>__('This month','wppizza-admin'));
			$reportTypes['lm'] = array('lbl'=>__('Last month','wppizza-admin'));
			$reportTypes['12m'] = array('lbl'=>__('Last 12 month','wppizza-admin'));


			$overview = empty($_GET['report']) || !isset($reportTypes[$_GET['report']]) ? true : false;
			$customrange=!empty($_GET['from']) && !empty($_GET['to'])  ? true : false;



			/******************************
			*
			*	[dashboard widget - last 2 months]
			*	adding spearate sum query to get totals
			*	in widget to keep memory consumption down somewhat
			*
			******************************/
			if($dashboard_widget){
				/* 1st of last month */
				$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime)-1, 1, date('Y',$wpTime));
				$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
				/* last of this month (right now would also do, but let's not worry about that) */
				$lastDateReport="".date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime)+1, 0, date('Y',$wpTime)))."";
				$aQuery = array(
					array(
						'AND' => array(
							array(
								'column' => 'order_date',
								'comparison' => '>=',
								'value' => $firstDateReport,
							),
							array(
								'column' => 'order_date',
								'comparison' => '<=',
								'value' => $lastDateReport,
							),
						),
					),
				);
				$oQuery = "AND ".$this->array2querystring($aQuery);
				/***graph label**/
				$graphLabel="".__('Dashboard Widget','wppizza-admin')." : ";


			}

			/******************************
			*
			*	[overview]
			*
			******************************/
			elseif($overview && !$customrange){

				$granularity='Y-m-d';/*days*/
				$daysSelected=30;
				$xaxisFormat='D, d M';
				$serieslines='true';
				$seriesbars='false';
				$seriespoints='true';
				$hoverOffsetLeft=5;
				$hoverOffsetTop=15;
				$firstDateTimestamp=mktime(date('H',$wpTime),date('i',$wpTime),date('s',$wpTime),date('m',$wpTime),date('d',$wpTime)-$daysSelected+1,date('Y',$wpTime));
				$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
				$lastDateReport="".date('Y',$wpTime)."-".date('m',$wpTime)."-".date('d',$wpTime)." 23:59:59";
				$oQuery='';
				$aQuery = array();//to pass onto filters for easier management
				/***graph label**/
				$graphLabel="".__('Details last 30 days','wppizza-admin')." : ";
			}

			/******************************
			*
			*	[custom range]
			*
			******************************/
			elseif($customrange){

					$selectedReport='customrange';
					$from=explode('-',$_GET['from']);
					$to=explode('-',$_GET['to']);

					$firstDateTs=mktime(0, 0, 0, $from[1], $from[2], $from[0]);
					$lastDateTs=mktime(23, 59, 59, $to[1], $to[2], $to[0]);
					/*invert dates if end<start**/
					if($firstDateTs>$lastDateTs){
						$firstDateTimestamp=$lastDateTs;
						$lastDateTimestamp=$firstDateTs;
					}else{
						$firstDateTimestamp=$firstDateTs;
						$lastDateTimestamp=$lastDateTs;
					}

					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport="".date('Y-m-d H:i:s',$lastDateTimestamp)."";
					/*override get vars**/
					$_GET['from']=$firstDateReport;
					$_GET['to']=date('Y-m-d',$lastDateTimestamp);
					/**from/to formatted**/
					$fromFormatted=date($dateformat,$firstDateTimestamp);
					$toFormatted=date($dateformat,$lastDateTimestamp);
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".$fromFormatted." - ".$toFormatted." : ";
			}
			/******************************
			*
			*	[predefined reports]
			*
			******************************/
			elseif(!$overview){

				$selectedReport=$_GET['report'];
				$oQuery = '';
				$aQuery = array();//to pass onto filters for easier management

				/************************
					year to date
				************************/
				if($selectedReport=='ytd'){
					$firstDateTimestamp=mktime(0, 0, 0, 1, 1, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport="".date('Y',$wpTime)."-".date('m',$wpTime)."-".date('d',$wpTime)." 23:59:59";
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Year to date','wppizza-admin')." : ";
				}
				/************************
					last year
				************************/
				if($selectedReport=='ly'){
					$firstDateTimestamp=mktime(0, 0, 0, 1, 1, date('Y',$wpTime)-1);
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23,59,59,12,31,date('Y',$wpTime)-1));
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Last Year','wppizza-admin')." : ";
				}
				/************************
					this month
				************************/
				if($selectedReport=='tm'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), 1, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport="".date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime)+1, 0, date('Y',$wpTime)))."";
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('This Month','wppizza-admin')." : ";
				}
				/************************
					last month
				************************/
				if($selectedReport=='lm'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime)-1, 1, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23,59,59,date('m',$wpTime),0,date('Y',$wpTime)));
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Last Month','wppizza-admin')." : ";
				}

				/************************
					last 12month
				************************/
				if($selectedReport=='12m'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime)-12, date('d',$wpTime)+1, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime)));
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Last 12 Month','wppizza-admin')." : ";
				}

				/************************
					today
				************************/
				if($selectedReport=='today'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime)));
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Today','wppizza-admin')." : ";
				}

				/************************
					completed business days
					in last week (omitting closed days)
				************************/
				if(!empty($completedBusinessDays)){
					foreach($completedBusinessDays as $bDayKey=>$bDay){
						if($selectedReport == $bDay['date']){
							$firstDateTimestamp = $bDay['open'];
							$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
							$lastDateReport=date('Y-m-d H:i:s',$bDay['close']);
							/* set query */
							$aQuery = array(
								array(
									'AND' => array(
										array(
											'column' => 'order_date',
											'comparison' => '>=',
											'value' => $bDay['open_formatted'],
										),
										array(
											'column' => 'order_date',
											'comparison' => '<=',
											'value' => $bDay['close_formatted'],
										),
									),
								),
							);
							$oQuery = "AND ".$this->array2querystring($aQuery);
							/***graph label**/
							$graphLabel="".$bDay['lbl']." : ";
						}
					}
				}


//				/************************
//					yesterday
//				************************/
//				if($selectedReport=='yesterday'){
//					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), date('d',$wpTime)-1, date('Y',$wpTime));
//					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
//					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime)-1, date('Y',$wpTime)));
//					$oQuery="AND order_date >='".$firstDateReport."'  AND order_date <= '".$lastDateReport."' ";
//					/***graph label**/
//					$graphLabel="".__('Yesterday','wppizza-admin')." : ";
//				}

				/************************
					last 7 days
				************************/
				if($selectedReport=='7d'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), date('d',$wpTime)-6, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime)));
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					/***graph label**/
					$graphLabel="".__('Last 7 days','wppizza-admin')." : ";
				}
				/************************
					last 14 days
				************************/
				if($selectedReport=='14d'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), date('d',$wpTime)-13, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime)));
					/***graph label**/
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					$graphLabel="".__('Last 14 days','wppizza-admin')." : ";
				}
				/************************
					last 30 days
				************************/
				if($selectedReport=='30d'){
					$firstDateTimestamp=mktime(0, 0, 0, date('m',$wpTime), date('d',$wpTime)-29, date('Y',$wpTime));
					$firstDateReport="".date('Y-m-d',$firstDateTimestamp)."";
					$lastDateReport=date('Y-m-d H:i:s',mktime(23, 59, 59, date('m',$wpTime), date('d',$wpTime), date('Y',$wpTime)));
					/***graph label**/
					$aQuery = array(
						array(
							'AND' => array(
								array(
									'column' => 'order_date',
									'comparison' => '>=',
									'value' => $firstDateReport,
								),
								array(
									'column' => 'order_date',
									'comparison' => '<=',
									'value' => $lastDateReport,
								),
							),
						),
					);
					$oQuery = "AND ".$this->array2querystring($aQuery);
					$graphLabel="".__('Last 30 days','wppizza-admin')." : ";
				}

			}



			if(!$overview || $customrange || $dashboard_widget){

				$firstDate = new DateTime($firstDateReport);
				$firstDateFormatted = $firstDate->format($dateformat);
				$lastDate = new DateTime($lastDateReport);
				$lastDateFormatted = $lastDate->format($dateformat);
				$dateDifference = $firstDate->diff($lastDate);
				$daysSelected=($dateDifference->days)+1;
				$monthAvgDivider=($dateDifference->m)+1;
				$monthsSelected=$dateDifference->m;
				$yearsSelected=$dateDifference->y;
				/*
					set granularity to months if months>0 or years>0 && not dashboard widget (as we want to extract todasy orders too there, but also need the last 2 months )
				*/
				if( ($monthsSelected > 0 || $yearsSelected > 0) && !$dashboard_widget){
					$granularity='Y-m';/*months*/
					$xaxisFormat='M Y';
					$serieslines='false';
					$seriesbars='true';
					$seriespoints='false';
					$hoverOffsetLeft=-22;
					$hoverOffsetTop=2;
				}else{
					$granularity='Y-m-d';/*days*/
					$xaxisFormat='D, d M';
					$serieslines='true';
					$seriesbars='false';
					$seriespoints='true';
					$hoverOffsetLeft=5;
					$hoverOffsetTop=15;
				}
			}


			/************************************************************************
				multisite install
				all orders of all sites (blogs)
				but only for master blog and if enabled (settings)
			************************************************************************/
			$menu_items_and_categories = array();
			$menu_items_and_categories['posts'] = 0;
			$menu_items_and_categories['categories'] = 0;
			$category_names = array();

			if(apply_filters('wppizza_filter_reports_all_sites',false)){
				$ordersQueryRes=array();
				$orderTotalsRes=array();
				$orderTotalsUsersRes=array();
		 	   	$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
		 	   		if ($blogs) {
		        	foreach($blogs as $blog) {
		        		switch_to_blog($blog['blog_id']);
		        		/*make sure plugin is active*/
	        			if(is_plugin_active(WPPIZZA_PLUGIN_INDEX)){

	        				/************************
	        					number of wppizza posts
	        				************************/
							$menu_items_and_categories['posts'] += wp_count_posts(WPPIZZA_POST_TYPE)->publish;
	        				/************************
	        					number of wppizza categories
	        				************************/
							$terms = get_terms(WPPIZZA_TAXONOMY);
							if ( $terms && !is_wp_error( $terms ) ){
								$menu_items_and_categories['categories'] += count($terms);
								foreach($terms as $t){
									$category_names[$t->term_id] = array('name'=>$t->name, 'slug'=>$t->slug, 'description'=>$t->description);
								}
							}
							/************************
								[make and run query]
							*************************/
							$ordersQuery = $this->wppizza_report_mkquery($wpdb->prefix, $oQuery, $export, $dashboard_widget);
							$ordersQuery = $wpdb->get_results($ordersQuery);

							/**merge array**/
							$ordersQueryRes = array_merge($ordersQuery,$ordersQueryRes);


							/************************
								[get totals/sums - currently only used for dashboard widget]
							*************************/
							if($dashboard_widget){

								/*
									order totals by gateway
								*/
								$orderTotalsQuery = $this->wppizza_report_sums_by_gateway($wpdb->prefix);
								$orderTotalsQuery = $wpdb->get_results($orderTotalsQuery);
								/**merge array**/
								$orderTotalsRes = array_merge($orderTotalsQuery, $orderTotalsRes);

								/*
									order totals by ([non]registered) users
								*/
								$orderTotalsUserQuery = $this->wppizza_report_sums_by_users($wpdb->prefix);
								$orderTotalsUserQuery = $wpdb->get_results($orderTotalsUserQuery);

								/**merge array**/
								$orderTotalsUsersRes = array_merge($orderTotalsUserQuery, $orderTotalsUsersRes);


							}
	        			}
						restore_current_blog();
		        	}}
			}else{

	        	/************************
	        		number of wppizza posts
	        	************************/
	        	$menu_items_and_categories['posts'] += wp_count_posts(WPPIZZA_POST_TYPE)->publish;
				/************************
					number of wppizza categories
				************************/
				$terms = get_terms(WPPIZZA_TAXONOMY);
				if ( $terms && !is_wp_error( $terms ) ){
					$menu_items_and_categories['categories'] += count($terms);
					foreach($terms as $t){
						$category_names[$t->term_id] = array('name'=>$t->name, 'slug'=>$t->slug, 'description'=>$t->description);
					}
				}
				/************************
					[make and run query]
				*************************/
				$ordersQuery=$this->wppizza_report_mkquery($wpdb->prefix, $oQuery, $export, $dashboard_widget);
				$ordersQueryRes = $wpdb->get_results($ordersQuery);

				/************************
					[allow filtering of results set: @since 3.18.4]
				*************************/
				$ordersQueryRes = apply_filters('wppizza_filter_report_query_results', $ordersQueryRes, $wpdb->prefix, $aQuery, $export, $dashboard_widget);

				/************************
					[get total sums - currently only used for dashboard widget]
				*************************/
				if($dashboard_widget){
					/*
						order totals by gateway
					*/
					$orderTotalsQuery = $this->wppizza_report_sums_by_gateway($wpdb->prefix);
					$orderTotalsRes = $wpdb->get_results($orderTotalsQuery);

					/*
						order totals by ([non]registered) users
					*/
					$orderTotalsUserQuery = $this->wppizza_report_sums_by_users($wpdb->prefix);
					$orderTotalsUsersRes = $wpdb->get_results($orderTotalsUserQuery);

				}

			}


			/**************************
				ini dates
			**************************/
			$graphDates=array();
			for($i=0;$i<$daysSelected;$i++){
				$dayFormatted=mktime(date('H',$firstDateTimestamp),date('i',$firstDateTimestamp),date('s',$firstDateTimestamp),date('m',$firstDateTimestamp),date('d',$firstDateTimestamp)+$i,date('Y',$firstDateTimestamp));
				$graphDates[]=date($granularity,$dayFormatted);
			}

			/******************************************************************************************************************************************************
			*
			*
			*
			*	[create dataset from orders]
			*
			*
			*
			******************************************************************************************************************************************************/
					/*********************************************
						only when exporting to file
						sum/count of the same item in period
					*********************************************/
					if($export){
						$exportCustom = array();/* full order data for any custom exports */
						$itemsSummed=array();
						$gatewaysSummed=array();
						$orderStatusSummed=array();
						$orderCustomStatusSummed=array();
					}

					/*ini tax*/
					$orderTaxTotals=array();
					$orderTaxTotals['included']=0;
					$orderTaxTotals['added']=0;

					$orderTaxByRate = array();
					$orderTaxByRate['included'] = array();
					$orderTaxByRate['added'] = array();
					$orderTaxByRate['total'] = array();


					/**********************************************
					*
					*	[get and tidy up order first]
					*
					**********************************************/
					foreach($ordersQueryRes as $qKey=>$order){
						/*
							skip if there's an empty order_ini or order_no_of_items
						*/
						if(empty($order->order_ini) || empty($order->order_no_of_items)){
							continue;
						}

						/*
							lets try and fix any serialization errors
						*/
						if(empty(maybe_unserialize($order->order_ini))){
							$order->order_ini = wppizza_fix_serialization($order->order_ini) ;
							/* if it's still empty, just give up */
							if(empty($order->order_ini)){
								continue;
							}
						}

						//always unserialize if required and cast to array to allow for multiple gateways used for one order
						$order -> initiator = (array) maybe_unserialize($order -> initiator);
						$orderGateways = array();
						foreach($order -> initiator as $oIni => $oIniVal){
							$oIniId = !empty($oIniVal['id']) ? $oIniVal['id'] : $oIniVal;
							$oIniPaid = !empty($oIniVal['paid']) ? $oIniVal['paid'] : $order->order_total;
							$orderGateways[] = array('id' => $oIniId , 'paid' => $oIniPaid);
						}

						/**
							get order details per order and sum / format for later output
							as long as at least one item was ordered
							Note: we should not check for totals here as with discounts applied the total may be 0
							but it is of course still a valid order
						**/

						/**
							un-serialize the items
						**/
						$orderItems = maybe_unserialize($order->order_ini);/**unserialize order details**/
						unset($orderItems['info']);//remove some overkill data

						/*
							(most useful) db columns
						*/
						$orderDetails = array();/* ini array */
						$orderDetails['blog_id']=$order->blog_id;/* add blog id too as that might come in useful */
						$orderDetails['order_id']=$order->id;
						$orderDetails['order_date']=substr($order->oDate,0,10);
						$orderDetails['order_date_formatted']=date($granularity,$order->order_date);
						$orderDetails['order_date_timestamp']=$order->order_date;
						$orderDetails['order_update']=$order->order_update;
						$orderDetails['order_delivered']=$order->order_delivered;
						$orderDetails['wp_user_id']=$order->wp_user_id;
						$orderDetails['total_price_items']=$order->order_items_total;
						$orderDetails['order_total']=$order->order_total;
						$orderDetails['order_tips']=!empty($order->order_tips) ? $order->order_tips : 0 ;
						$orderDetails['order_items_count']=$order->order_no_of_items;
						$orderDetails['initiator']= maybe_unserialize($order->initiator);
						$orderDetails['order_status']=$order->order_status;
						$orderDetails['payment_status']=$order->payment_status;
						$orderDetails['transaction_id']=$order->transaction_id;

						/* taxes this order */
						$taxes_included = ($order->order_taxes_included == 'Y') ? $order->order_taxes : 0;
						$taxes_added = ($order->order_taxes_included == 'N') ? $order->order_taxes : 0;

						$orderDetails['taxes_included']=$taxes_included;
						$orderDetails['taxes_added']=$taxes_added;

						/**add up taxes totals*/
						$orderTaxTotals['included'] += $taxes_included;
						$orderTaxTotals['added'] += $taxes_added;

						/**taxes by rates */
						if(!empty($orderItems['summary']['tax_by_rate'])){
						foreach($orderItems['summary']['tax_by_rate'] as $rates){
							/* included taxes */
							if($order->order_taxes_included == 'Y'){
								if(!isset($orderTaxByRate['included'][''.$rates['rate'].'%'])){
									$orderTaxByRate['included'][''.$rates['rate'].'%'] = !empty($rates['total']) ? $rates['total'] : 0 ;
								}else{
									$orderTaxByRate['included'][''.$rates['rate'].'%'] += !empty($rates['total']) ? $rates['total'] : 0 ;
								}
							}

							/* added taxes */
							if($order->order_taxes_included == 'N'){
								if(!isset($orderTaxByRate['added'][''.$rates['rate'].'%'])){
									$orderTaxByRate['added'][''.$rates['rate'].'%'] = !empty($rates['total']) ? $rates['total'] : 0 ;
								}else{
									$orderTaxByRate['added'][''.$rates['rate'].'%'] += !empty($rates['total']) ? $rates['total'] : 0 ;
								}
							}

							/* total taxes */
							if(!isset($orderTaxByRate['total'][''.$rates['rate'].'%'])){
								$orderTaxByRate['total'][''.$rates['rate'].'%'] = !empty($rates['total']) ? $rates['total'] : 0 ;
							}else{
								$orderTaxByRate['total'][''.$rates['rate'].'%'] += !empty($rates['total']) ? $rates['total'] : 0 ;
							}

						}}

						/** account for orders pre 3.0 ([item])  post 3.0 its [items] - if we dont have that either just then it's really old stuff and we just give up */
						$orderDetailsItems = !empty($orderItems['items']) ? $orderItems['items'] : ( !empty($orderItems['item']) ? $orderItems['item'] : array() );

						$itemDetails=array();
						foreach($orderDetailsItems as $k=>$uniqueItems){

							/* account for pre 3.0 where post_id was postId */
							$uniqueItems['post_id'] = isset($uniqueItems['post_id']) ? $uniqueItems['post_id'] : (isset($uniqueItems['postId']) ? $uniqueItems['postId'] : '' );
							/* if there's still no post id, explode key by . and use first -  very very old wppizza versions */
							if(empty($uniqueItems['post_id'])){
								$key_vals = explode('.',$k);
								/*
									if there's no post id here either
									we just give up right here for this iteration
								*/
								if(empty($key_vals[0])){
									continue;
								}

								$uniqueItems['post_id'] = $key_vals[0];
							}

							/* account for pre 3.0 where we use name instead of title */
							$uniqueItems['title'] = isset($uniqueItems['title']) ? $uniqueItems['title'] : $uniqueItems['name'] ;
							/* account for pre 3.0 where we use size instead of price_label */
							$uniqueItems['price_label'] = isset($uniqueItems['price_label']) ? $uniqueItems['price_label'] : $uniqueItems['size'] ;

							$itemDetails[$k]['post_id']=$uniqueItems['post_id'];
							$itemDetails[$k]['title'] = $uniqueItems['title'] ;
							$itemDetails[$k]['price_label'] =  $uniqueItems['price_label'] ;
							$itemDetails[$k]['quantity'] = $uniqueItems['quantity'];
							$itemDetails[$k]['price'] = $uniqueItems['price'];
							$itemDetails[$k]['price_formatted'] = isset($uniqueItems['price_formatted']) ? $uniqueItems['price_formatted'] : wppizza_format_price($uniqueItems['price'], $orderItems['currency']) ;//eliminate php notices for old installs
							$itemDetails[$k]['pricetotal'] = $uniqueItems['pricetotal'];
							$itemDetails[$k]['pricetotal_formatted'] = isset($uniqueItems['pricetotal_formatted']) ? $uniqueItems['pricetotal_formatted'] : wppizza_format_price($uniqueItems['pricetotal'], $orderItems['currency']) ;//eliminate php notices for old installs
							$itemDetails[$k]['tax_rate'] = $uniqueItems['tax_rate'];
							$itemDetails[$k]['tax_rate_formatted'] = isset($uniqueItems['tax_rate_formatted']) ?  $uniqueItems['tax_rate_formatted'] : wppizza_output_format_percent(wppizza_format_price_float($uniqueItems['tax_rate'], false)).'%';
							$itemDetails[$k]['category_id'] = $uniqueItems['cat_id_selected'];
							$itemDetails[$k]['category_name'] = !empty($uniqueItems['cat_id_selected']) ? $uniqueItems['item_in_categories'][$uniqueItems['cat_id_selected']]['name'] : '' ;
							$itemDetails[$k]['category_slug'] = !empty($uniqueItems['cat_id_selected']) ? $uniqueItems['item_in_categories'][$uniqueItems['cat_id_selected']]['slug'] : '' ;
							$itemDetails[$k]['custom_data'] = empty($uniqueItems['custom_data']) ? array() : $uniqueItems['custom_data'] ;// @since 3.8.6
							$itemDetails[$k]['extend_data'] = empty($uniqueItems['extend_data']) ? array() : $uniqueItems['extend_data'] ;// @since 3.9

							/*sum/count of the same item in period . export only*/
							/*make unique by name too as it may have changed over time*/
							if($export){
								/* make a key consisting of id and size and md5 of name (as it may have changed over time) to sum it up*/
								$mkKey=''.$uniqueItems['post_id'].'.'.$uniqueItems['price_label'].'.'.MD5($uniqueItems['title']);
								if(!isset($itemsSummed[$mkKey])){
									if(empty($uniqueItems['price_label'])){
										$itemsSummed[$mkKey]=array('quantity'=>$uniqueItems['quantity'], 'title'=>$uniqueItems['title'].'', 'pricetotal'=>$uniqueItems['pricetotal']);
									}else{
										$itemsSummed[$mkKey]=array('quantity'=>$uniqueItems['quantity'], 'title'=>$uniqueItems['title'].' ['.$uniqueItems['price_label'].']', 'pricetotal'=>$uniqueItems['pricetotal']);
									}
								}else{
									$itemsSummed[$mkKey]['quantity']+=$uniqueItems['quantity'];
									$itemsSummed[$mkKey]['pricetotal']+=$uniqueItems['pricetotal'];
								}
							}
						}
						/**add relevant item info to array**/
						$orderDetails['items'] = $itemDetails;

						$processOrder[] = $orderDetails;

						/*sum by gateway and order status*/
						if($export){

							/*per gateway*/
							foreach($orderGateways as $orderGateway){
							if(!isset($gatewaysSummed[$orderGateway['id']])){
								$gatewaysSummed[$orderGateway['id']] = $orderGateway['paid'];
							}else{
								$gatewaysSummed[$orderGateway['id']] += $orderGateway['paid'];
							}}

							/* per order status */
							if(!isset($orderStatusSummed[$order->order_status])){
								$count[$order->order_status] = 1;
								$value[$order->order_status] = $order -> order_total;
								$orderStatusSummed[$order->order_status] = array('count' => $count[$order->order_status], 'value' => $value[$order->order_status]);
							}else{
								$count[$order->order_status] ++;
								$value[$order->order_status] += $order -> order_total;
								$orderStatusSummed[$order->order_status] = array('count' => $count[$order->order_status], 'value' => $value[$order->order_status]);
							}

							/* per custom order status */
							if(!empty($order->order_status_user_defined)){
							if(!isset($orderCustomStatusSummed[$order->order_status_user_defined])){
								$count[$order->order_status_user_defined] = 1;
								$value[$order->order_status_user_defined] = $order -> order_total;
								$orderCustomStatusSummed[$order->order_status_user_defined] = array('count' => $count[$order->order_status_user_defined], 'value' => $value[$order->order_status_user_defined]);
							}else{
								$count[$order->order_status_user_defined] ++;
								$value[$order->order_status_user_defined] += $order -> order_total;
								$orderCustomStatusSummed[$order->order_status_user_defined] = array('count' => $count[$order->order_status_user_defined], 'value' => $value[$order->order_status_user_defined]);
							}}

							/*******************************************
							#
							#	export full details for each order dataset custom reports
							#
							*******************************************/
							if($export && !empty($_GET['type']) && $_GET['type'] != 'default'){
								//unique user id's
								$exportCustom['users'][$order->wp_user_id] = array();

								//unique blog id's
								$exportCustom['blogs'][$order->blog_id] = array();

								//formfields
								$exportCustom['formfields'][$order->blog_id] = array();

								//selected db columns added to order data
								$orderColumns = array();
								$orderColumns['wp_user_id'] = $orderDetails['wp_user_id'];
								$orderColumns['blog_id'] = $orderDetails['blog_id'];
								$orderColumns['order_id'] = $orderDetails['order_id'];
								$orderColumns['order_date'] = $orderDetails['order_date'];
								$orderColumns['order_date_formatted'] = $orderDetails['order_date_formatted'];
								$orderColumns['order_date_timestamp'] = $orderDetails['order_date_timestamp'];
								$orderColumns['order_update'] = $orderDetails['order_update'];
								$orderColumns['order_delivered'] = $orderDetails['order_delivered'];
								$orderColumns['initiator'] = $orderDetails['initiator'];
								$orderColumns['payment_status'] = $orderDetails['payment_status'];
								$orderColumns['transaction_id'] = $orderDetails['transaction_id'];


								/* account for non existing 'total_discounts' key previous to v3.9 */
								if(!isset($orderDetails['total_discounts'])){

									$orderItems['summary']['total_discounts'] = empty($orderItems['summary']['discount']) ? 0 : $orderItems['summary']['discount'];
									/* add additional discounts to sum */
									if(isset($orderItems['summary']['additional_discounts'])){
										foreach($orderItems['summary']['additional_discounts'] as $addDiscounts){
											$orderItems['summary']['total_discounts'] += $addDiscounts['value'];
										}
										/* incase there are some precision issue, make sure we round */
										$orderItems['summary']['total_discounts'] = wppizza_round($orderItems['summary']['total_discounts']);
									}else{
										/* add empty additional discounts array for consistency */
										$orderItems['summary']['additional_discounts'] = array();
									}
								}

								//customer data to be sanitised
								$exportCustom['orders'][$order->blog_id][$order->id]['customer'] = array();
								/*raw customer data - not output directly */
								$exportCustomerDataRaw[$order->blog_id][$order->id] = !empty($order->customer_ini) ? maybe_unserialize($order->customer_ini) : array() ;
								$exportCustomerDataRaw[$order->blog_id][$order->id]['ip_address'] = !empty($order->ip_address) ? $order->ip_address : '' ;//add ip address to customer data

								//order data
								$exportCustom['orders'][$order->blog_id][$order->id]['order'] = array_merge($orderColumns, $orderItems['param'], $orderItems['summary']);
								$exportCustom['orders'][$order->blog_id][$order->id]['order']['verbose'] = !empty($order->order_details) ?  $order->order_details : array() ;//add verbose details

								//per item data
								$exportCustom['orders'][$order->blog_id][$order->id]['items'] = $itemDetails;

							}
						}

					}
					/* unset $ordersQueryRes now we are done with it */
					unset($ordersQueryRes);

					/*******************************************
					#
					#	BEGIN SETTING DATA FOR CUSTOM REPORTS
					#
					*******************************************/
					if($export && !empty($_GET['type']) && $_GET['type'] != 'default'){


						/*******************************************
						#
						#	blog info
						#
						*******************************************/
						if(!empty($exportCustom['blogs'])){
							foreach($exportCustom['blogs'] as $bID => $iniArray){
								$exportCustom['blogs'][$bID] = wppizza_get_blog_details($bID);
							}
						}

						/*******************************************
						#
						#	append registered user data to each unique user id
						#
						*******************************************/
						if(!empty($exportCustom['users'])){
							foreach($exportCustom['users'] as $uID => $iniArray){
								if(!empty($uID)){
									/*
										get registered users' data
									*/
									$userdata = get_userdata($uID);
									if(!empty($userdata)){// in case user was deleted

										/*
											get registered users' meta data
										*/
										$usermeta = get_user_meta($uID);
										/*
											append login, nicename etc etc
											to user data array. plus selectively first/last name
											only (for now) from user meta
										*/
										$exportCustom['users'][$uID] =  (array)$userdata->data ;//main data
										$exportCustom['users'][$uID]['first_name'] =  $usermeta['first_name'][0] ;//meta -> first name
										$exportCustom['users'][$uID]['last_name'] =  $usermeta['last_name'][0] ;//meta -> last name

									}
								}
							}
						}
						/*******************************************
						#	get customer data formfields (labels only for now)
						#	for each blog in multisite or of current blog
						*******************************************/
						if(!empty($exportCustom['blogs'])){

							foreach($exportCustom['blogs'] as $bID => $iniArray){

								$exportCustom['formfields'][$bID] = array();
								/*
									multisite - if getting orders from all sites
									in parent network site
								*/
								if(is_multisite()){
									if($blog_id != $bID){
										switch_to_blog($bID);
									}
								}

								/*
									get ALL registered formfields
									(including disabled ones as they might have been enabled in the past)
								*/
								$ffs = WPPIZZA()-> helpers -> enabled_formfields(false, false, true);
								foreach($ffs as $ffKey => $ffArr){
									/*
										omit empty and cemail keys here
										(preventing extraction of emails for spam purposes)
									*/
									if(!empty($ffKey) && $ffKey!='cemail'){
										$exportCustom['formfields'][$bID][$ffKey]['label'] = $ffArr['lbl'];
									}
								}

								/*
									multisite - revert
								*/
								if(is_multisite()){
									if($blog_id != $bID){
										restore_current_blog();
									}
								}
							}
						}

						/*******************************************
						#	overwrite customer data for each order
						#	returning consistent number of values
						#	according to formfields in this blog
						*******************************************/
						if(!empty($exportCustomerDataRaw)){
							/*
								get blog id from key
							*/
							foreach($exportCustomerDataRaw as $bID => $cDataBlog){
								/*
									get order id from subkey blog
								*/
								foreach($cDataBlog as $oID => $cDataOrder){

									/*
										loop though formfields and get value
									*/
									foreach($exportCustom['formfields'][$bID] as $ffKey => $ffArr){
										$exportCustom['orders'][$bID][$oID]['customer'][$ffKey] = !empty($cDataOrder[$ffKey]) ? $cDataOrder[$ffKey] : '' ;
									}
									/* add ip address distinctly */
									$exportCustom['orders'][$bID][$oID]['customer']['ip_address'] = $exportCustomerDataRaw[$bID][$oID]['ip_address'];
								}
							}
						}



						/*******************************************
						#	only return this data and exit
						#	if exporting $_GET['type'] != 'default'
						*******************************************/

					return $exportCustom;
					}
					/****************************************************************************************************************************************************
					#
					#	END CUSTOM REPORTS DATA
					#
					****************************************************************************************************************************************************/

					/*sort distinct items - export only***/
					if($export && !empty($itemsSummed)){
						arsort($itemsSummed);
					}


					/**********************************************************************************
					*
					*
					*	lets do the calculations, to get the right dataset
					*
					*
					**********************************************************************************/
					/**************************************
						[initialize array and values]
					**************************************/
					$datasets=array();

					/*range*/
					$datasets['report_range']['from'] = $firstDateReport.' 00:00:00' ;//for consistency , lets add the H:i:s here too
					$datasets['report_range']['to'] = $lastDateReport ;


					/*totals*/
					$datasets['sales_value_total']=0;/**total of sales/orders INCLUDING taxes, discounts, charges etc**/
					$datasets['sales_count_total']=0;/**total count of sales**/
					$datasets['sales_order_tax']=0;/**tax on order**/
					$datasets['items_value_total']=0;/**total of items EXLUDING taxes, discounts, charges etc**/
					$datasets['items_count_total']=0;/**total count of items**/
					$datasets['tips_value_total']=0;/**total tips**/


					/*totals this month*/
					$datasets['sales_this_month_value_total']=0;/**total of sales/orders INCLUDING taxes, discounts, charges etc**/
					$datasets['sales_this_month_count_total']=0;/**total count of sales**/
					$datasets['items_this_month_value_total']=0;/**total of items EXLUDING taxes, discounts, charges etc**/
					$datasets['items_this_month_count_total']=0;/**total count of items**/

					/*totals last month*/
					$datasets['sales_last_month_value_total']=0;/**total of sales/orders INCLUDING taxes, discounts, charges etc**/
					$datasets['sales_last_month_count_total']=0;/**total count of sales**/
					$datasets['items_last_month_value_total']=0;/**total of items EXLUDING taxes, discounts, charges etc**/
					$datasets['items_last_month_count_total']=0;/**total count of items**/

					/*per gateway*/
					$datasets['gateway_sales'] = array();

					/*users*/
					$datasets['users_registered']=array();/** unique registered users (i.e wp_user_id!=0) **/
					$datasets['users_registered_count']=0;
					$datasets['users_registered_total_value']=0;
					$datasets['users_registered_total_items']=0;
					$datasets['users_guest_count']=0;
					$datasets['users_guest_total_value']=0;
					$datasets['users_guest_total_items']=0;

					/*taxes*/
					$datasets['tax_total']=($orderTaxTotals['included'] + $orderTaxTotals['added']);/**total tax**/
					$datasets['tax_by_rate'] = !empty($orderTaxByRate) ? $orderTaxByRate : array();/* tax by rate**/

					/*misc*/
					$datasets['sales']=array();/*holds data on a per day/month basis*/
					$datasets['bestsellers']=array('by_volume'=>array(),'by_value'=>array());

					if($export){
						/*per item*/
						$datasets['items_summary'] = $itemsSummed;
						/*per gateway*/
						$datasets['gateways_summary'] = $gatewaysSummed;
						/*per order status*/
						$datasets['order_status_summary'] = $orderStatusSummed;
						/*per custom order status*/
						$datasets['order_custom_status_summary']=$orderCustomStatusSummed;
					}

					/***************************************
						allow additional data to be added to reports dataset - INIT value
						@since3.8.6
					***************************************/
					$datasets = apply_filters('wppizza_filter_reports_dataset_init', $datasets );

					/**************************************
						[loop through orders and do things]
						creating datasets
					**************************************/
					$j=1;
					/**************************************
						[array of orders to be sliced to last 5 only]
					**************************************/
					$recent_orders = array();
					foreach($processOrder as $k=>$order){

						//always cast to array to allow for multiple gateways used for one order
						$order['initiator'] = (array)$order['initiator'];
						$orderGateways = array();
						foreach($order['initiator'] as $oIni => $oIniVal){
							$oIniId = !empty($oIniVal['id']) ? $oIniVal['id'] : $oIniVal;
							$oIniPaid = !empty($oIniVal['paid']) ? $oIniVal['paid'] : $order['order_total'];
							$orderGateways[] = array('id' => $oIniId , 'paid' => $oIniPaid);
						}
						/**************************************
							[array of orders to be sliced to last 5 only]
						**************************************/
						$recent_orders[] = array(
							'timestamp'=> $order['order_date_timestamp'],
							'total'=> $order['order_total'],
							'blog_id'=> $order['blog_id'],
							'order_id'=> $order['order_id'],
							'wp_user_id'=> $order['wp_user_id']
						);
						/****************************************************
							if we are not setting a defined range
							like a whole month, week , or whatever
							(i.e in overview) lets get first and last day
							we have orders for to be able to calc averages
						****************************************************/
						if($j==1){$datasets['first_date']=$order['order_date'];}


						/****************************************************
							set garnularity (i.e by day, month or year)
						****************************************************/
						$dateResolution=$order['order_date_formatted'];/**set garnularity (i.e by day, month or year)**/

						/****************************************************
							[get/set totals]
						****************************************************/
						$datasets['sales_value_total']+=$order['order_total'];
						$datasets['sales_count_total']++;
						$datasets['sales_order_tax']+=$order['taxes_added']+$order['taxes_included'];
						$datasets['items_value_total']+=$order['total_price_items'];
						$datasets['items_count_total']+=$order['order_items_count'];
						$datasets['tips_value_total']+=$order['order_tips'];

						/* per gateway */
						foreach($orderGateways as $orderGateway){
						if(!isset($datasets['gateway_sales'][$orderGateway['id']]['total'])){
							$datasets['gateway_sales'][$orderGateway['id']]['total_count'] = 1;
							$datasets['gateway_sales'][$orderGateway['id']]['total'] = $orderGateway['paid'];
						}else{
							$datasets['gateway_sales'][$orderGateway['id']]['total_count'] ++;
							$datasets['gateway_sales'][$orderGateway['id']]['total'] += $orderGateway['paid'];
						}}


						/****************************************************
							[get/set totals this month]
						****************************************************/
						if( $order['order_date_timestamp'] >= $firstDayCurrentMonth ){
							$datasets['sales_this_month_value_total']+=$order['order_total'];
							$datasets['sales_this_month_count_total']++;
							$datasets['items_this_month_value_total']+=$order['total_price_items'];/*items before any charges*/
							$datasets['items_this_month_count_total']+=$order['order_items_count'];
							/* per gateway */
							foreach($orderGateways as $orderGateway){
							if(!isset($datasets['gateway_sales'][$orderGateway['id']]['total_this_month'])){
								$datasets['gateway_sales'][$orderGateway['id']]['total_this_month'] = $orderGateway['paid'];
							}else{
								$datasets['gateway_sales'][$orderGateway['id']]['total_this_month'] += $orderGateway['paid'];
							}}
						}
						/****************************************************
							[get/set totals last month]
						****************************************************/
						if($order['order_date_timestamp']  < $firstDayCurrentMonth  && $order['order_date_timestamp'] >= $firstDayLastMonth){
							$datasets['sales_last_month_value_total']+=$order['order_total'];
							$datasets['sales_last_month_count_total']++;
							$datasets['items_last_month_value_total']+=$order['total_price_items'];/*items before any charges*/
							$datasets['items_last_month_count_total']+=$order['order_items_count'];
							/* per gateway */
							foreach($orderGateways as $orderGateway){
							if(!isset($datasets['gateway_sales'][$orderGateway['id']]['total_last_month'])){
								$datasets['gateway_sales'][$orderGateway['id']]['total_last_month'] = $orderGateway['paid'];
							}else{
								$datasets['gateway_sales'][$orderGateway['id']]['total_last_month'] += $orderGateway['paid'];
							}}
						}

						/****************************************************
							[get/set totals registere users / guests]
						****************************************************/
						/*unique registere users*/
						if(!empty($order['wp_user_id'])){
							if(!isset($datasets['users_registered'][$order['wp_user_id']])){
								$datasets['users_registered_count']++;/*add unique user*/
								/*set user id*/
								$datasets['users_registered'][$order['wp_user_id']]['id'] = $order['wp_user_id'];
							}

								$datasets['users_registered_total_value'] += $order['order_total'];
								$datasets['users_registered_total_items'] += $order['order_items_count'];
						}else{
								/*guest users*/
								$datasets['users_guest_count']=1;/*set guest user*/
								$datasets['users_guest_total_value'] += $order['order_total'];
								$datasets['users_guest_total_items'] += $order['order_items_count'];
						}
						/****************************************************
							[get/set items to sort for bestsellers]
						****************************************************/
						foreach($order['items'] as $iK=>$oItems){
							$uniqueKeyX=explode('|',$iK);
							$category='';
							/**
								if grouped by category is/was set, $uniqueKeyX will have 4 int, concat by a period, where the 3rd denotes the cat id*/
							$kX=explode('.',$uniqueKeyX[0]);
							/*item id*/
							$menuItemId=$kX[0];
							/*size id*/
							$menuItemSize=$kX[1];

							if(count($kX)>3 && $wppizza_options['layout']['items_group_sort_print_by_category']){
								$category = get_term_by( 'id', $kX[2], WPPIZZA_TAXONOMY);
								if(is_object($category)){
									$category=' - <em style="font-size:80%">'.$category->name.'</em>';
								}else{
									$category='';
								}
							}

							/**unset this bought item from the unsold menu items**/
							if(isset($unsoldMenuItems[$menuItemId.'.'.$menuItemSize])){
								unset($unsoldMenuItems[$menuItemId.'.'.$menuItemSize]);
							}

							/**make a unique key by id and name in case an items name was changed */
							/**note, unique keys will be different when grouped/display by category is set*/
							$uKey=MD5($uniqueKeyX[0].$oItems['title'].$oItems['price_label']);
							if(!isset($datasets['bestsellers']['by_volume'][$uKey])){

								/**lets do by volume and by value at the same time**/

								//account for empty price_label in title
								$strTitle = empty($oItems['price_label']) ? ''.$oItems['title'].' '.$category.'' : ''.$oItems['title'].' ['.$oItems['price_label'].']'.$category.'';

								$datasets['bestsellers']['by_value'][$uKey]=array('price'=>$oItems['pricetotal'], 'single_price'=>$oItems['price'], 'quantity'=>$oItems['quantity'], 'title'=>$strTitle, 'min_price'=>$oItems['price'], 'max_price'=>$oItems['price']  );
								$datasets['bestsellers']['by_volume'][$uKey]=array('quantity'=>$oItems['quantity'], 'price'=>$oItems['pricetotal'], 'single_price'=>$oItems['price'], 'title'=>$strTitle);

							}else{

								/*sum up / set  by value*/
								$datasets['bestsellers']['by_value'][$uKey]['quantity']+=$oItems['quantity'];
								$datasets['bestsellers']['by_value'][$uKey]['price']+=$oItems['pricetotal'];
								/*set min and max price as they may have changed */
								if($oItems['price']>$datasets['bestsellers']['by_value'][$uKey]['max_price']){
									$datasets['bestsellers']['by_value'][$uKey]['max_price']=$oItems['price'];
								}
								if($oItems['price']<$datasets['bestsellers']['by_value'][$uKey]['min_price']){
									$datasets['bestsellers']['by_value'][$uKey]['min_price']=$oItems['price'];
								}

								/*sum up by volume*/
								$datasets['bestsellers']['by_volume'][$uKey]['quantity']+=$oItems['quantity'];
								$datasets['bestsellers']['by_volume'][$uKey]['price']+=$oItems['pricetotal'];
							}
						}

						/****************************************************
							[get/set totals [per granularity]
						****************************************************/
							/**initialize arrays**/
							if(!isset($datasets['sales'][$dateResolution])){
								$datasets['sales'][$dateResolution]['sales_value_total']=0;
								$datasets['sales'][$dateResolution]['sales_count_total']=0;
								$datasets['sales'][$dateResolution]['sales_order_tax']=0;
								$datasets['sales'][$dateResolution]['items_value_total']=0;
								$datasets['sales'][$dateResolution]['items_count_total']=0;
								$datasets['sales'][$dateResolution]['tips_value_total']=0;
								$datasets['sales'][$dateResolution]['categories'] = array();
							}
							$datasets['sales'][$dateResolution]['sales_value_total']+=$order['order_total'];
							$datasets['sales'][$dateResolution]['sales_count_total']++;
							$datasets['sales'][$dateResolution]['sales_order_tax']+=$order['taxes_added']+$order['taxes_included'];
							$datasets['sales'][$dateResolution]['items_value_total']+=$order['total_price_items'];
							$datasets['sales'][$dateResolution]['items_count_total']+=$order['order_items_count'];
							$datasets['sales'][$dateResolution]['tips_value_total']+=$order['order_tips'];

							/* per gateway */
							foreach($orderGateways as $orderGateway){
							if(!isset($datasets['gateway_sales'][$orderGateway['id']][$dateResolution])){
								$datasets['gateway_sales'][$orderGateway['id']][$dateResolution] = $orderGateway['paid'];
							}else{
								$datasets['gateway_sales'][$orderGateway['id']][$dateResolution] += $orderGateway['paid'];
							}}

							foreach($order['items'] as $item_details){
								if(!isset($datasets['sales'][$dateResolution]['categories'][$item_details['category_id']])){
									/* get cat name if we can */
									$cat_name = (!empty($category_names[$item_details['category_id']])) ? $category_names[$item_details['category_id']]['name'] : '';

									$datasets['sales'][$dateResolution]['categories'][$item_details['category_id']] = array('id'=> $item_details['category_id'], 'name' => $cat_name ,'total_sales' => $item_details['pricetotal']);
								}else{
									$datasets['sales'][$dateResolution]['categories'][$item_details['category_id']]['total_sales'] += $item_details['pricetotal'];
								}
							}
					$j++;

					/***************************************
						allow additional data to be added to reports dataset - ADDING values
						@since3.8.6
					***************************************/
					$datasets = apply_filters('wppizza_filter_reports_dataset_order', $datasets, $order);

				}


				/**************************************
					[sort and slice recent orders - 5 only]
				**************************************/
				if(!empty($recent_orders)){
				rsort($recent_orders);
				$recent_orders = array_slice($recent_orders, 0, apply_filters('wppizza_filter_dashboard_widget_no_of_recent_orders', 5));
				foreach($recent_orders as $roKey=>$roVal){
					// currently unused
					//$recent_orders[$roKey]['timestamp_formatted'] = date($dateformat,$roVal['timestamp']). ' ' . date($timeformat,$roVal['timestamp']);
					/* get user meta */
					$user = (!empty($roVal['wp_user_id'])) ? get_userdata($roVal['wp_user_id']) : false;
					/* add user name etc */
					$recent_orders[$roKey]['user'] = array();
					if($user){
						$recent_orders[$roKey]['user']['first_name'] = $user->first_name;
						$recent_orders[$roKey]['user']['last_name'] = $user->last_name;
						$recent_orders[$roKey]['user']['user_login'] = $user->user_login;
						$recent_orders[$roKey]['user']['user_email'] = $user->user_email;
					}
				}}

				/*******************************
					sort and splice bestsellers
				*******************************/
				arsort($datasets['bestsellers']['by_volume']);
				arsort($datasets['bestsellers']['by_value']);

				/*max display, could be made into a dropdown*/
				if(!isset($_GET['b'])){$bCount=10;}else{$bCount=abs((int)$_GET['b']);}

				/*slice worstsellers - currently not displayed*/
				$worstsellers['by_volume']=array_slice($datasets['bestsellers']['by_volume'],-$bCount);
				asort($worstsellers['by_volume']);
				$worstsellers['by_value']=array_slice($datasets['bestsellers']['by_value'],-$bCount);
				asort($worstsellers['by_value']);

				/*splice bestsellers*/
				array_splice($datasets['bestsellers']['by_volume'],$bCount);
				array_splice($datasets['bestsellers']['by_value'],$bCount);


				/************************************************************
					construct bestsellers html
				*************************************************************/
				$htmlBsVol='<ul id="wppizza-report-top10-volume-ul">';/*by volume*/
				foreach($datasets['bestsellers']['by_volume'] as $bsbv){
					$htmlBsVol.='<li>'.$bsbv['quantity'].' x '.$bsbv['title'].'</li>';
				}
				$htmlBsVol.='</ul>';

				$htmlBsVal='<ul id="wppizza-report-top10-value-ul">';/*by value*/
				foreach($datasets['bestsellers']['by_value'] as $bsbv){
					$priceRange=wppizza_output_format_price($bsbv['single_price']);
					/*show price range if prices were changed */
					if($bsbv['min_price']!=$bsbv['max_price']){
					$priceRange=''.wppizza_output_format_price($bsbv['min_price']).'-'.wppizza_output_format_price($bsbv['max_price']);
					}
					$htmlBsVal.='<li>'.$bsbv['title'].' <span>'.$reportCurrency.''.wppizza_output_format_price($bsbv['price']).'</span><br /> ['.$bsbv['quantity'].' x '.$reportCurrency.''.$priceRange.'] <span>'.round($bsbv['price']/$datasets['items_value_total']*100,2).'%</span></li>';
				}
				$htmlBsVal.='</ul>';


				/************************************************************
					construct worstsellers html - currently not displayed
				*************************************************************/
				$htmlWsVol='<ul id="wppizza-report-bottom10-volume-ul">';/*by volume*/
				foreach($worstsellers['by_volume'] as $bsbv){
					$htmlWsVol.='<li>'.$bsbv['quantity'].' x '.$bsbv['title'].'</li>';
				}
				$htmlWsVol.='</ul>';

				$htmlWsVal='<ul id="wppizza-report-bottom10-value-ul">';/*by value*/
				foreach($worstsellers['by_value'] as $bsbv){
					$priceRange=wppizza_output_format_price($bsbv['single_price']);
					/*show price range if prices were changed */
					if($bsbv['min_price']!=$bsbv['max_price']){
					$priceRange=''.wppizza_output_format_price($bsbv['min_price']).'-'.wppizza_output_format_price($bsbv['max_price']);
					}
					$htmlWsVal.='<li>'.$bsbv['title'].' <span>'.$reportCurrency.''.wppizza_output_format_price($bsbv['price']).'</span><br /> ['.$bsbv['quantity'].' x '.$reportCurrency.''.$priceRange.'] <span>'.round($bsbv['price']/$datasets['items_value_total']*100,2).'%</span></li>';
				}
				$htmlWsVal.='</ul>';

				$htmlNoSellers='<ul id="wppizza-report-nosellers-ul">';/*non sellers*/
				/*add unsold items*/
				foreach($unsoldMenuItems as $usKey=>$usVal){
					//remove empty sizes brackets
					if(empty($usVal['price_label'])){
						$htmlNoSellers.='<li>0 x '.$usVal['title'].'</li>';
					}else{
						$htmlNoSellers.='<li>0 x '.$usVal['title'].' ['.$usVal['price_label'].']</li>';
					}
				}
				$htmlNoSellers.='</ul>';


				/**********************************************************
					get number of months and days in results array
				***********************************************************/
				if($overview && !$customrange){
					/**in case we have an empty results set**/
					if(!isset($datasets['first_date'])){
						$datasets['first_date']="".date('Y',$wpTime)."-".date('m',$wpTime)."-".date('d',$wpTime)." 00:00:00";
					}
					$firstDate = new DateTime($datasets['first_date']);
					$firstDateFormatted = $firstDate->format($dateformat);
					$lastDate = new DateTime("".date('Y',$wpTime)."-".date('m',$wpTime)."-".date('d',$wpTime)." 23:59:59");
					$lastDateFormatted = $lastDate->format($dateformat);
					$dateDifference = $firstDate->diff($lastDate);
					$daysSelected=$dateDifference->days+1;
					$monthAvgDivider=($dateDifference->m)+1;
				}

				/*****************************************************************
					averages
				******************************************************************/
				/*per day*/
				$datasets['sales_count_average']=round($datasets['sales_count_total']/$daysSelected,2);
				$datasets['sales_item_average']=round($datasets['items_count_total']/$daysSelected,2);
				$datasets['sales_value_average']=round($datasets['sales_value_total']/$daysSelected,2);
				/*per month*/
				$datasets['sales_count_average_month']=round($datasets['sales_count_total']/$monthAvgDivider,2);
				$datasets['sales_item_average_month']=round($datasets['items_count_total']/$monthAvgDivider,2);
				$datasets['sales_value_average_month']=round($datasets['sales_value_total']/$monthAvgDivider,2);

			/******************************************************************************************************************************************************
			*
			*
			*	[sidebar boxes]
			*
			*
			******************************************************************************************************************************************************/
			$box=array();
			$boxrt=array();
			$range_restricted = null;
			if($overview && !$customrange){

				/* flag for filter */
				$range_restricted = false;

				/*boxes left*/
				$box[]=array('id'=>'wppizza-report-val-total', 'class'=>'', 'lbl'=>__('All Sales: Total','wppizza-admin'),'val'=>'<p>'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_total']).'<br /><span class="description">'.__('incl. taxes, charges and discounts','wppizza-admin').'</span></p>');
				$box[]=array('id'=>'wppizza-report-val-avg', 'class'=>'', 'lbl'=>__('All Sales: Averages','wppizza-admin'),'val'=>'<p>'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_average']).' '.__('per day','wppizza-admin').'<br />'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_average_month']).' '.__('per month','wppizza-admin').'</p>');
				$box[]=array('id'=>'wppizza-report-count-total', 'class'=>'', 'lbl'=>__('All Orders/Items: Total','wppizza-admin'),'val'=>'<p>'.$datasets['sales_count_total'].' '.__('Orders','wppizza-admin').': '.$reportCurrency.' '.$datasets['items_value_total'].'<br />('.$datasets['items_count_total'].' '.__('items','wppizza-admin').')<br /><span class="description">'.__('before taxes, charges and discounts','wppizza-admin').'</span></p>');
				$box[]=array('id'=>'wppizza-report-count-avg', 'class'=>'', 'lbl'=>__('All Orders/Items: Averages','wppizza-admin'),'val'=>'<p>'.$datasets['sales_count_average'].' '.__('Orders','wppizza-admin').' ('.$datasets['sales_item_average'].' '.__('items','wppizza-admin').') '.__('per day','wppizza-admin').'<br />'.$datasets['sales_count_average_month'].' '.__('Orders','wppizza-admin').' ('.$datasets['sales_item_average_month'].' items) '.__('per month','wppizza-admin').'</p>');
				$box[]=array('id'=>'wppizza-report-taxes', 'class'=>'', 'lbl'=>__('Total Tax on Orders','wppizza-admin'),'val'=>'<p>'.wppizza_output_format_price($datasets['tax_total']).'</p>');
				$box[]=array('id'=>'wppizza-report-info', 'class'=>'', 'lbl'=>__('Range','wppizza-admin'),'val'=>'<p>'.$firstDateFormatted.' - '.$lastDateFormatted.'<br />'.$daysSelected.' '.__('days','wppizza-admin').' | '.$monthAvgDivider.' '.__('months','wppizza-admin').'</p>');

				/*boxes right*/
				$boxrt[]=array('id'=>'wppizza-report-top10-volume', 'class'=>'', 'lbl'=>__('Best/Worst sellers by Volume - All','wppizza-admin'),'val'=>$htmlBsVol.$htmlWsVol);
				$boxrt[]=array('id'=>'wppizza-report-top10-value', 'class'=>'', 'lbl'=>sprintf(__('Best/Worst sellers by Value - All [%s of order total]','wppizza-admin'), '%') ,'val'=>$htmlBsVal.$htmlWsVal);
				$boxrt[]=array('id'=>'wppizza-report-nonsellers', 'class'=>'', 'lbl'=>__('Non-Sellers - All','wppizza-admin'),'val'=>$htmlNoSellers);
			}
			if(!$overview || $customrange){

				/* flag for filter */
				$range_restricted = true;

				/*boxes left*/
				$box[]=array('id'=>'wppizza-report-val-total', 'class'=>'', 'lbl'=>__('Sales Total [in range]','wppizza-admin'),'val'=>'<p>'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_total']).'<br /><span class="description">'.__('incl. taxes, charges and discounts','wppizza-admin').'</span></p>');
				$box[]=array('id'=>'wppizza-report-val-avg', 'class'=>'', 'lbl'=>__('Sales Averages [in range]','wppizza-admin'),'val'=>'<p>'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_average']).' '.__('per day','wppizza-admin').'<br />'.$reportCurrency.' '.wppizza_output_format_price($datasets['sales_value_average_month']).' '.__('per month','wppizza-admin').'</p>');
				$box[]=array('id'=>'wppizza-report-count-total', 'class'=>'', 'lbl'=>__('Orders/Items Total [in range]','wppizza-admin'),'val'=>'<p>'.$datasets['sales_count_total'].' '.__('Orders','wppizza-admin').': '.$reportCurrency.' '.$datasets['items_value_total'].'<br /> ('.$datasets['items_count_total'].' '.__('items','wppizza-admin').')<br /><span class="description">'.__('before taxes, charges and discounts','wppizza-admin').'</span></p>');
				$box[]=array('id'=>'wppizza-report-taxes', 'class'=>'', 'lbl'=>__('Total Tax on Orders [in range]','wppizza-admin'),'val'=>'<p>'.wppizza_output_format_price($datasets['tax_total']).'</p>');
				$box[]=array('id'=>'wppizza-report-count-avg', 'class'=>'', 'lbl'=>__('Orders/Items Averages [in range]','wppizza-admin'),'val'=>'<p>'.$datasets['sales_count_average'].' '.__('Orders','wppizza-admin').' ('.$datasets['sales_item_average'].' '.__('items','wppizza-admin').') '.__('per day','wppizza-admin').'<br />'.$datasets['sales_count_average_month'].' '.__('Orders','wppizza-admin').' ('.$datasets['sales_item_average_month'].' items) '.__('per month','wppizza-admin').'</p>');
				$box[]=array('id'=>'wppizza-report-info', 'class'=>'', 'lbl'=>__('Range','wppizza-admin'),'val'=>'<p>'.$firstDateFormatted.' - '.$lastDateFormatted.'<br />'.$daysSelected.' '.__('days','wppizza-admin').'<br />'.$monthAvgDivider.' '.__('months','wppizza-admin').'</p>');

				/*boxes right*/
				$boxrt[]=array('id'=>'wppizza-report-top10-volume', 'class'=>'', 'lbl'=>__('Best/Worst sellers by Volume [in range]','wppizza-admin'),'val'=>$htmlBsVol.$htmlWsVol);
				$boxrt[]=array('id'=>'wppizza-report-top10-value', 'class'=>'', 'lbl'=>sprintf(__('Best/Worst sellers by Value [%s of all orders in range]','wppizza-admin'), '%'),'val'=>$htmlBsVal.$htmlWsVal);
				$boxrt[]=array('id'=>'wppizza-report-nonsellers', 'class'=>'', 'lbl'=>__('Non-Sellers [in range]','wppizza-admin'),'val'=>$htmlNoSellers);

			}

			/**allow order change - or indeed additional boxes - by filter**/
			$box=apply_filters('wppizza_filter_reports_boxes_left',$box, $datasets, $range_restricted);
			$boxrt=apply_filters('wppizza_filter_reports_boxes_right',$boxrt, $datasets, $range_restricted);

			/******************************************************************************************************************************************************
			*
			*
			*	[graph data]
			*
			*
			******************************************************************************************************************************************************/
				/***graph data sales value**/
				$grSalesValue=array();
				foreach($graphDates as $date){
					$str2t=strtotime($date.' 12:00:00');
					$xaxis=date($xaxisFormat,$str2t);
					$val=!empty($datasets['sales'][$date]['sales_value_total']) ? $datasets['sales'][$date]['sales_value_total'] : 0;
					$grSalesValue[]='["'.$xaxis.'",'.$val.']';
				}
				$graph['sales_value']='label:"'.__('sales value','wppizza-admin').'",data:['.implode(',',$grSalesValue).']';

				/***graph data sales count**/
				$grSalesCount=array();
				foreach($graphDates as $date){
					$str2t=strtotime($date.' 12:00:00');
					$xaxis=date($xaxisFormat,$str2t);
					$val=!empty($datasets['sales'][$date]['sales_count_total']) ? $datasets['sales'][$date]['sales_count_total'] : 0;
					$grSalesCount[]='["'.$xaxis.'",'.$val.']';
				}
				$graph['sales_count']='label:"'.__('number of sales','wppizza-admin').'",data:['.implode(',',$grSalesCount).'], yaxis: 2';

				/***graph data items count**/
				$grItemsCount=array();
				foreach($graphDates as $date){
					$str2t=strtotime($date.' 12:00:00');
					$xaxis=date($xaxisFormat,$str2t);
					$val=!empty($datasets['sales'][$date]['items_count_total']) ? $datasets['sales'][$date]['items_count_total'] : 0;
					$grItemsCount[]='["'.$xaxis.'",'.$val.']';
				}
				$graph['items_count']='label:"'.__('items sold','wppizza-admin').'",data:['.implode(',',$grItemsCount).'], yaxis: 3';



		/*
			allow for filtering, perhaps in conjunction with running your own query
			using wppizza_filter_report_query filter
		*/
		$datasets = apply_filters('wppizza_filter_report_datasets', $datasets, $processOrder);

		/************************************
			make array to return
		*************************************/
		$data=array();
		$data['currency']=$reportCurrency;
		$data['counts']=$menu_items_and_categories;
		if($dashboard_widget){
			$data['totals'] = array();
		}
		$data['recent_orders']= $recent_orders ;
		$data['dataset']=$datasets;
		$data['graphs']=array('data'=>$graph,'label'=>$graphLabel,'hoverOffsetTop'=>$hoverOffsetTop,'hoverOffsetLeft'=>$hoverOffsetLeft,'series'=>array('lines'=>$serieslines,'bars'=>$seriesbars,'points'=>$seriespoints));
		$data['boxes']=$box;
		$data['boxesrt']=$boxrt;
		$data['reportTypes']=$reportTypes;
		$data['view']=($overview && !$customrange) ? 'ini' : 'custom';
		/*
			add totals per payment type to the dataset for the dashboard widget
		*/
		if($dashboard_widget){

			/*
				totals by payment type
				split in to each type
			*/
			foreach($orderTotalsRes as $k => $v){
				$data['totals']['payment_type'][$v -> salesPaymentType] = array(
					'total_sales' => $v -> salesCountTotal,
					'total_value' => $v -> salesValueTotal,
					'total_items' => $v -> salesNoOfItemsTotal,
				);
			}

			/*
				totals by users
				split in to registered and non registered users
			*/
			foreach($orderTotalsUsersRes as $k => $v){
				/*
					registered users
				*/
				if($v -> totalsUserType === 'registered'){
					$data['totals']['users']['registered'] = array(
						'no_of_users' => $v -> totalsUserCount,
						'total_value' => $v -> totalsUserValue,
						'total_items' => $v -> totalsUserNoOfItems,
					);
				}
				/*
					guest / non-registered
				*/
				if($v -> totalsUserType === 'guest'){
					$data['totals']['users']['guest'] = array(
						'no_of_users' => $v -> totalsUserCount,
						'total_value' => $v -> totalsUserValue,
						'total_items' => $v -> totalsUserNoOfItems,
					);
				}
			}
		}

		/*
			clean up
		*/
		unset($processOrder);
		unset($reportCurrency);
		unset($menu_items_and_categories);
		unset($recent_orders);
		unset($datasets);
		unset($graph);
		unset($graphLabel);
		unset($hoverOffsetTop);
		unset($hoverOffsetLeft);
		unset($serieslines);
		unset($seriesbars);
		unset($seriespoints);
		unset($box);
		unset($boxrt);
		unset($reportTypes);
		unset($overview);
		unset($customrange);
		unset($orderTotalsRes);
		unset($orderTotalsUsersRes);



		/*set transient*/
		if($transient_expiry){
			$data['transient_set_at_'.$transient_expiry.'']=$wpTime;
			set_transient( WPPIZZA_TRANSIENT_REPORTS_NAME.'_'.$transient_expiry.'' , $data, $transient_expiry );/*one hour*/
		}

	return $data;
	}


	/*********************************************************
	*
	*	[query string of data set - filterable]
	*
	*	@param str
	*	@param str
	*	@param bool (export)
	*	@param bool (dashboard widget)
	*
	*	@return str
	*********************************************************/
	function wppizza_report_mkquery($wpdbPrefix, $oQuery, $is_export = false, $is_dasboard = false){
		global $blog_id;


		/*
			only include columns we actually need
			in standard reports page, but select all
			for exports
		*/
		$select_cols = array();
		$select_cols[] = 'id';
		$select_cols[] = 'wp_user_id';
		$select_cols[] = 'order_update';
		$select_cols[] = 'order_status';
		$select_cols[] = 'order_delivered';
		$select_cols[] = 'order_ini';
		$select_cols[] = 'payment_status';
		$select_cols[] = 'transaction_id';
		$select_cols[] = 'initiator';
		$select_cols[] = 'order_no_of_items';
		$select_cols[] = 'order_items_total';
		$select_cols[] = 'order_taxes';
		$select_cols[] = 'order_taxes_included';
		$select_cols[] = 'order_total';
		$select_cols[] = 'ip_address';
		/*
			implode - or '*' if export
		*/
		$select_cols = 	($is_export) ? '*' : implode(',', $select_cols);



		$ordersQuery='SELECT '.$select_cols.', order_date as oDate ,';
		if(defined('WPPIZZA_REPORT_NO_DB_OFFSET')){/* in case accounting for the mysql timezone offset causes issues leave this here for now*/
			$ordersQuery.="UNIX_TIMESTAMP(order_date) ";
		}else{
			$ordersQuery.="UNIX_TIMESTAMP(order_date)-TIMESTAMPDIFF(SECOND, NOW(), UTC_TIMESTAMP()) ";
		}
		$ordersQuery.='as order_date, "'.$blog_id.'" as blog_id FROM '.$wpdbPrefix . WPPIZZA_TABLE_ORDERS .' WHERE payment_status IN ("COMPLETED") AND order_status != "REJECTED" ';
		$ordersQuery.= $oQuery;
		$ordersQuery.='ORDER BY order_date ASC';

		/*
			allow filtering , passing on additional "where" parameters
			as well as prefix and table to build your own if needs be
		*/
		$ordersQuery = apply_filters('wppizza_filter_report_query', $ordersQuery, $wpdbPrefix, $oQuery, $is_export, $is_dasboard);

	return $ordersQuery;
	}

	/*********************************************************
	*
	*	result set of sum of various columns , per gateway
	*	currently only used to add to dashboard widget result set
	*	which by default only queries the full results for the last
	*	2 months to keep memory consumption in check somewhat
	*
	*	@since 3.12.11
	*	@param str
	*
	*	@return str
	*********************************************************/
	function wppizza_report_sums_by_gateway($wpdbPrefix){
		global $blog_id;

		/*
			getting some totals for all orders
			grouped by each payment type
		*/
		$select_cols = array();
		$select_cols[] = 'COUNT(DISTINCT id) salesCountTotal';
		$select_cols[] = '(initiator) salesPaymentType';
		$select_cols[] = 'SUM(order_total) salesValueTotal';
		$select_cols[] = 'SUM(order_no_of_items) salesNoOfItemsTotal';

		/* implode columns */
		$select_cols = 	implode(',', $select_cols);

		$ordersQuery='SELECT '.$select_cols.' ';
		$ordersQuery.=' FROM '.$wpdbPrefix . WPPIZZA_TABLE_ORDERS .' WHERE payment_status IN ("COMPLETED") AND order_status != "REJECTED" ';
		$ordersQuery.='GROUP BY initiator';


	return $ordersQuery;
	}

	/*********************************************************
	*
	*	result set of sum of various columns , per gateway
	*	currently only used to add to dashboard widget result set
	*	which by default only queries the full results for the last
	*	2 months to keep memory consumption in check somewhat
	*
	*	@since 3.12.11
	*	@param str
	*
	*	@return str
	*********************************************************/
	function wppizza_report_sums_by_users($wpdbPrefix){
		global $blog_id;

		/*
			getting some totals for all orders
			grouped by each payment type
		*/
		$select_cols = array();
		$select_cols[] = 'COUNT(DISTINCT wp_user_id) as totalsUserCount';
		$select_cols[] = 'SUM(order_total) totalsUserValue';
		$select_cols[] = 'SUM(order_no_of_items) totalsUserNoOfItems';

		/* implode columns */
		$select_cols = 	implode(',', $select_cols);

		/* ini query string */
		$ordersQuery ='';
		/*
			totals for non-registered users (i.e wpdbPrefix == 0)
		*/
		$ordersQuery .='SELECT '.$select_cols.', "guest" as totalsUserType ';
		$ordersQuery .='FROM '.$wpdbPrefix . WPPIZZA_TABLE_ORDERS .' WHERE payment_status IN ("COMPLETED") AND order_status != "REJECTED" ';
		$ordersQuery .='AND wp_user_id <= 0 ';

		//union
		$ordersQuery .=' UNION ALL ';

		/*
			totals for registered users (i.e wpdbPrefix > 0)
		*/
		$ordersQuery .='SELECT '.$select_cols.', "registered" as totalsUserType ';
		$ordersQuery .='FROM '.$wpdbPrefix . WPPIZZA_TABLE_ORDERS .' WHERE payment_status IN ("COMPLETED") AND order_status != "REJECTED" ';
		$ordersQuery .='AND wp_user_id > 0 ';



	return $ordersQuery;
	}
	/*********************************************************
	*
	*	helper to
	*	create query string from parameters passed in array
	*
	*	@since 3.18.4
	*	@param array
	*
	*	@return str
	*********************************************************/
	function array2querystring($aQuery){
		global $wpdb;
		/*
			generate query from for pos table
		*/
		$oQuery = array();
		$i=0;
		foreach($aQuery as $parameters){
			$query = array();
			$condition = key($parameters);
			foreach($parameters as $fragments){
				foreach($fragments as $q){
					$query[] = $wpdb->prepare("".$q['column']." ".$q['comparison']." %s", $q['value']);
				}
			}
			$oQuery[$i] = implode(' '.$condition.' ', $query);
		$i++;
		}
		$querystring = implode(' ',$oQuery);
	/* resturn as string */
	return $querystring;
	}
}
?>