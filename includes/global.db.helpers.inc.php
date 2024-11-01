<?php if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/ ?>
<?php
/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
*
*	general helper functions that could be used for 3rd party plugin development
*	or used in custom functions outside wppizza environment (functions.php and watnot)
*
*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\*/

/********************************************************************************
	get orders table  depending on blog id
	@ since 3.16.4
	@ param int/bool
	@ return str
************************************************************************************/
function wppizza_order_table($set_blog_id = false){
	$order_table = WPPIZZA() -> db -> order_table($set_blog_id);
return  $order_table;
}
/********************************************************************************
	get meta table  depending on blog id

	@ since 3.16.4
	@ param int/bool
	@ return str
************************************************************************************/
function wppizza_meta_table($set_blog_id = false){
	$meta_table = WPPIZZA() -> db -> meta_table($set_blog_id);
return  $meta_table;
}
/********************************************************************************
	get completed orders (including failed, unconfirmed, or orders that have subsequently been rejected or refunded)
	from order table(s) depending on arguments set and optionally format
	see documentation at https://docs.wp-pizza.com/developers/?section=function-wppizza_get_orders

	@ since 3.5
	@ param array
	@ return array
************************************************************************************/
function wppizza_get_orders($args = false, $caller = '' ){
	$orders = WPPIZZA() -> db -> get_orders($args, $caller);
return $orders;
}
/********************************************************************************
	update an order by id/blog/hash and optionally selected payment status
	using update_values type/value array
	example:

	$args = array(
		'query' => array(
			'blog_id' => $blog_id,
			'order_id' => $order_id,
			'payment_status' => 'CAPTURED',
		),
		'update_values' => array(
			'order_date' 		=> array('type'=> '%s', 'data' =>date('Y-m-d H:i:s', WPPIZZA_WP_TIME)),
			'order_date_utc' 	=> array('type'=> '%s', 'data' =>date('Y-m-d H:i:s', WPPIZZA_UTC_TIME)),
		),
	);

	@ since 3.8
	@ param array
	@ return bool
************************************************************************************/
function wppizza_update_order($args){
	$blog_id = !empty($args['query']['blog_id']) ? $args['query']['blog_id'] : false;
	$order_id = !empty($args['query']['order_id']) ? $args['query']['order_id'] : false;
	$hash = !empty($args['query']['hash']) ? $args['query']['hash'] : false;
	$update_values = !empty($args['update_values']) ? $args['update_values'] : false;
	$where_payment_status = !empty($args['query']['payment_status']) ? $args['query']['payment_status'] : false;

	/* simply skip if nothing to do */
	if(empty($update_values) || !is_array($update_values) ||  ( !$blog_id && !$order_id && !$hash ) ){
		return true;
	}

	/* update order as required */
	$order_update = WPPIZZA() -> db -> update_order($blog_id, $order_id, $hash, $update_values, $where_payment_status);

return $order_update;
}
/********************************************************************************
	if outputting results from wppizza_get_orders you could use the below to get
	appropriate pagination
	see documentation at https://docs.wp-pizza.com/developers/?section=function-wppizza_get_orders

	@ since 3.5
	@ param int
	@ param int|false
	@ param bool
	@ param false|int
	@ return str
************************************************************************************/
function wppizza_orders_pagination($no_of_orders, $limit, $ellipsis = false, $pagination_info = true, $post_id = false){
	$pagination = WPPIZZA() -> markup_pages -> orderhistory_pagination($no_of_orders, $limit, $ellipsis, $pagination_info, $post_id);
return $pagination;
}
/********************************************************************************
	admin pagination helper
	returns pagination info as array
	@ since 3.10
	@ param int
	@ param int
	@ param str|false (defaults to 'paged')
	@ return array
************************************************************************************/
function wppizza_admin_pagination($results, $max_per_page, $getParam=false){
	$pagination = WPPIZZA()->admin_helper->admin_pagination($results, $max_per_page, $getParam);
return $pagination;
}
/********************************************************************************
	get all available customer form fields set in wppizza->order form

	default: excluding tips
	optionally, enabled only form fields
	optionally, include confirmation form


	@ since 3.7
	@ param bool
	@ param bool
	@ return array
************************************************************************************/
function wppizza_customer_checkout_fields($args = array('enabled_only' => false, 'confirmation_fields' => false, 'tips_excluded' => true, 'sort' => true)){
	global $wppizza_options;


	$ff = array();

	/* default , get all */
	if(!$args['enabled_only']){
		foreach($wppizza_options['order_form'] as $k=>$arr){
			$ff[$k] = $arr;
		}
	}

	/* if we want enabled only , get them here */
	if($args['enabled_only']){
		foreach($wppizza_options['order_form'] as $k=>$arr){
			if(!empty($arr['enabled'])){
				$ff[$k] = $arr;
			}
		}
	}

	// by default we exclude tips
	if($args['tips_excluded']){
		unset($ff['ctips']);
	}


	/* get confirmation form too */
	if($args['confirmation_fields']){
		foreach($wppizza_options['confirmation_form'] as $k=>$arr){
			$ff[$k] = $arr;
		}
	}
	if($args['sort']){
		asort($ff);
	}

return $ff;
}
/********************************************************************************
	add or update (if exists) meta data for an order

	@ since 3.8
	@ param int
	@ param str
	@ param mixed
	@ return bool false or meta_id we updated/inserted
************************************************************************************/
function wppizza_do_order_meta($order_id = false, $meta_name = false, $meta_value = false){
	$result = WPPIZZA() -> db -> do_order_meta($order_id, $meta_name, $meta_value);
return $result;
}
/********************************************************************************
	delete (if exists) meta data for an order

	@ since 3.8
	@ param int
	@ param str
	@ return bool
************************************************************************************/
function wppizza_delete_order_meta($order_id = false, $meta_name = false){
	$bool = WPPIZZA() -> db -> delete_order_meta($order_id, $meta_name);
return $bool;
}
/********************************************************************************
	delete (if exists) meta key for all orders

	@ since 3.8.4
	@ param str
	@ return bool
************************************************************************************/
function wppizza_delete_order_meta_by_key($meta_key){
	$bool = WPPIZZA() -> db -> delete_order_meta_by_key($meta_key);
return $bool;
}
/********************************************************************************
	query for a meta key

	@ since 3.13.3
	@ param str
	@ return mixed
************************************************************************************/
function wppizza_query_by_meta_key($meta_key){
	$res = WPPIZZA() -> db -> query_by_meta_key($meta_key);
return $res;
}
/********************************************************************************
	delete order by id/blogid(including any associated meta data)

	@ since 3.10
	@ param int (order id)
	@ param int (blog id)
	@ return void
************************************************************************************/
function wppizza_delete_order($selected_order_id = false, $selected_blog_id = false){
	/* delete from db (including any associated meta data) */
	$res = WPPIZZA()->db->delete_order($selected_order_id, $selected_blog_id);
return;
}
/********************************************************************************
	get orderid and meta id  from meta table for a specific meta key
	optionally check for a specific value of this meta key too

	@ since 3.8.4
	@param str 						meta key to query
	@param $meta_value  	to query for specific meta value too
	@return array[meta_id] = order_id
************************************************************************************/
function wppizza_get_order_id_by_meta_key($meta_key = false, $meta_value = NULL){
	$array = WPPIZZA() -> db -> get_order_id_by_meta_key($meta_key, $meta_value);
return $array;
}
/********************************************************************************
	get meta data for an order

	@ since 3.8
	@ param int
	@ param str / bool
	@ param bool	
	@ return array()
************************************************************************************/
function wppizza_get_order_meta($order_id = false, $meta_name = false, $meta_value_only = false){
	$array = WPPIZZA() -> db ->get_order_meta($order_id, $meta_name,  $meta_value_only);
return $array;
}

/********************************************************************************
	update meta data of a specific meta id

	@ since 3.13.5
	@ param int
	@ param mixed
	@ return array()
************************************************************************************/
function wppizza_update_order_meta_by_metaid($meta_id = false, $meta_values = false){
	$array = WPPIZZA() -> db -> update_order_meta_by_metaid($meta_id, $meta_values);
return $array;
}
/********************************************************************************
	delete meta data of a specific meta id

	@ since 3.13.5
	@ param int
	@ param mixed
	@ return array()
************************************************************************************/
function wppizza_delete_order_meta_by_metaid($meta_id = false){
	$array = WPPIZZA() -> db -> delete_order_meta_by_metaid($meta_id);
return $array;
}

/********************************************************************************
	get blog info by id helper

	@ since 3.9
	@ param int
	@ return array()
************************************************************************************/
function wppizza_get_blog_details($id = null){
	$array = WPPIZZA() -> helpers -> wppizza_blog_details($id);
return $array;
}
/********************************************************************************
	get blog date format

	@ since 3.9.2
	@ param void
	@ return array()
************************************************************************************/
function wppizza_get_blog_dateformat(){

	/* get date/time options set */
	$format['date'] = get_option('date_format');
	$format['time'] = get_option('time_format');

return $format;
}
/********************************************************************************
	remove an item entirely from cart by array key
	@ since 3.10.2
	@ param str
	@ param bool
	@ return empty array() or recalculated cart if update_cart is set to true
************************************************************************************/
function wppizza_remove_item_from_cart($key, $update_cart = false){
	$cart = WPPIZZA() -> session -> remove_item_from_cart($key, $update_cart);
return $cart;
}
/********************************************************************************
	get reports csv data as string
	@ since 3.12.1
	@ param arr
	@ return str
************************************************************************************/
function wppizza_reports_data($args = false){

	/*
		using the export data, no transients (live data), no dashboard widget
		default detailed report type
	*/
	$export = true;
	$transients = false;
	$dashboard_widget = false;
	$default_report_type = 'detailed';


	/*
		set default args (last 30 days) if not set
	*/
	$xFrom = !empty($args['range']['from']) ? explode('-', $args['range']['from']) : array() ;
	$valid_from = ( !empty($args['range']['from']) && count($xFrom) === 3 ) ? true : false;

	$xTo = !empty($args['range']['to']) ? explode('-', $args['range']['to']) : array() ;
	$valid_to = ( !empty($args['range']['to']) && count($xTo) === 3 ) ? true : false;

	/*
		args to pass on to wppizza_report_dataset
	*/
	$args = array(
		'range' => array(
			// must be Y-m-d format
			'from' => ( !$valid_from ? date('Y-m-d' , strtotime('-30 day', WPPIZZA_WP_TIME ) ) : $args['range']['from'] ),
			// must be Y-m-d format
			'to' =>	  ( !$valid_to ? date('Y-m-d' , WPPIZZA_WP_TIME ) : $args['range']['to'] ),
		),
		/*
			defaults to inbuilt 'detailed'
			if anything else is passed on, that type of report must have been added first of all via
			'wppizza_filter_csv_export_select'
			'wppizza_filter_csv_export_{type}'
			filters
		*/
		'type' => empty($args['type']) ? $default_report_type : wppizza_latin_lowercase($args['type']),
	);

	/*
		get the data
	*/
	$reports_data = WPPIZZA() -> sales_data ->wppizza_report_dataset($export, $transients, $dashboard_widget , $args);

	/*
		format as csv
	*/
	$csv_data = WPPIZZA() -> sales_data ->wppizza_report_detailed_csv('', $reports_data, $args['type']);

/*
	return the csv string
*/
return $csv_data;
}

?>