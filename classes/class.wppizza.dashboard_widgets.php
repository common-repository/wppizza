<?php
/**
* WPPIZZA_DASHBOARD_WIDGETS Class
*
* @package     WPPIZZA
* @subpackage  dashboard widgets
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.0
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/


/************************************************************************************************************************
*
*
*	WPPIZZA_DASHBOARD_WIDGETS
*
*
************************************************************************************************************************/
class WPPIZZA_DASHBOARD_WIDGETS{

	function __construct() {
		/**dashboard widget**/
		add_action( 'wp_dashboard_setup', array( $this, 'wppizza_dashboard_widget'));
	}

	/*********************************************************
	*
	*		[ini dashboard widgets]
	*
	*********************************************************/
	function wppizza_dashboard_widget(){
		/*
			sales only load for role with
			wppizza_cap_reports capabilities
		*/
		if (current_user_can('wppizza_cap_reports')){
			$dpwTitle =__('Overview','wppizza-admin');
			if(apply_filters('wppizza_filter_reports_all_sites',false)){
				$dpwTitle.=' '.__('[All Sites]','wppizza-admin');
			}
			if(WPPIZZA_ADMIN_DASHBOARD_TRANSIENT_REPORTS_EXPIRY == 3600){
			$dpwTitle.='  - <em>'.__('updated hourly','wppizza-admin').'</em>';
			}
			wp_add_dashboard_widget('wppizza_dashboard_widget',''. WPPIZZA_NAME.' '.$dpwTitle, array($this, 'wppizza_do_dashboard_widget_sales'));
		}
	}

	/*********************************************************
	*
	*		[sales dashboard widgets]
	*
	*********************************************************/
	function wppizza_do_dashboard_widget_sales($return_markup = false) {


		/***
			get sales data returns error if <5.3
		***/
		$data = WPPIZZA() -> sales_data  -> wppizza_report_dataset(false, WPPIZZA_ADMIN_DASHBOARD_TRANSIENT_REPORTS_EXPIRY, true);/*export false, transients true, dashboard true*/

		/**number of wppizza posts**/
		$count_posts = $data['counts']['posts'];

		/*number of wppizza categories**/
		$count_categories = $data['counts']['categories'];

		/*recent orders**/
		$recent_orders = $data['recent_orders'];
		$recent = '';
		foreach($recent_orders as $recent_order){
			$user = array();
			if(!empty($recent_order['user'])){
				$user['first_name'] = $recent_order['user']['first_name'];
				$user['last_name'] = $recent_order['user']['last_name'];
				if(!empty($user['first_name']) || !empty($user['last_name'])){
					$user['hyphen'] ='-';
				}
				$user['user_email'] = $recent_order['user']['user_email'];
				$user['user_login'] = '('.$recent_order['user']['user_login'].')';
			}else{
				$user[] = __('Guest', 'wppizza-admin');
			}

			$recent .='<tr><td>'.trim(implode(' ',$user)).'</td><td class="button button-secondary">'.wppizza_format_price($recent_order['total']).'</td></tr>';
		}

		/*transient set time*/
		$transientSetAt = $data['transient_set_at_'.WPPIZZA_ADMIN_DASHBOARD_TRANSIENT_REPORTS_EXPIRY];


		/**
			totals - sum up per payment type
		**/
		$totalSalesValue = 0;
		$totalSalesCount = 0;
		$totalItemsCount = 0;

		if(!empty($data['totals']['payment_type'])){
		foreach($data['totals']['payment_type'] as $k => $arr){
			$totalSalesValue += $arr['total_value'];
			$totalSalesCount += $arr['total_sales'];
			$totalItemsCount += $arr['total_items'];
		}}



		/**users**/
		$usersRegisteredCount = $data['totals']['users']['registered']['no_of_users'] ;//$data['dataset']['users_registered_count'];
		$usersRegisteredTotalValue = $data['totals']['users']['registered']['total_value'] ;//$data['dataset']['users_registered_total_value'];
		$usersRegisteredTotalItems = $data['totals']['users']['registered']['total_items'] ;//$data['dataset']['users_registered_total_items'];

		$usersGuestCount = $data['totals']['users']['guest']['no_of_users'] ;//$data['totals']['users']['users_guest_count'];
		$usersGuestTotalValue = $data['totals']['users']['guest']['total_value'] ;//$data['dataset']['users_guest_total_value'];
		$usersGuestTotalItems = $data['totals']['users']['guest']['total_items'] ;//$data['dataset']['users_guest_total_items'];



		/**today**/
		$totalSalesValueToday=0;
		$totalSalesCountToday=0;
		$totalItemsCountToday=0;
		if(isset($data['dataset']['sales'][date("Y-m-d", WPPIZZA_WP_TIME)])){
		$totalSalesValueToday=$data['dataset']['sales'][date("Y-m-d", WPPIZZA_WP_TIME)]['sales_value_total'];
		$totalSalesCountToday=$data['dataset']['sales'][date("Y-m-d", WPPIZZA_WP_TIME)]['sales_count_total'];
		$totalItemsCountToday=$data['dataset']['sales'][date("Y-m-d", WPPIZZA_WP_TIME)]['items_count_total'];
		}

		/**this month**/
		$totalSalesValueThisMonth=$data['dataset']['sales_this_month_value_total'];
		$totalSalesCountThisMonth=$data['dataset']['sales_this_month_count_total'];
		$totalItemsCountThisMonth=$data['dataset']['items_this_month_count_total'];

		/**last month**/
		$totalSalesValueLastMonth=$data['dataset']['sales_last_month_value_total'];
		$totalSalesCountLastMonth=$data['dataset']['sales_last_month_count_total'];
		$totalItemsCountLastMonth=$data['dataset']['items_last_month_count_total'];


		$dpwDashicon='<span class="wppizza-dashicons-medium wppizza-dashboard-widget-update dashicons dashicons-update" title="'.__('update now', 'wppizza-admin').'"></span>';
		$markup = '<div class="wppizza-dash wppizza-dash-updated">'.__('last update ','wppizza-admin').' '.date('Y-m-d H:i:s',$transientSetAt).' '.$dpwDashicon.'</div>';

		/*
			sales summary
		*/
		$dashboard_widget['sales_'] = '
			<table class="wppizza-dash wppizza-dash-sales">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>'.__('Total','wppizza-admin').'</th>
						<th>'.__('Today','wppizza-admin').'</th>
						<th>'.__('This Month','wppizza-admin').'</th>
						<th>'.__('Last Month','wppizza-admin').'</th>
				</tr>
				</thead>
				<tbody>
			';

			$dashboard_widget['sales_earnings'] = '<tr>
						<td>'.__('Earnings','wppizza-admin').'</td>
						<td>'.wppizza_format_price($totalSalesValue).'</td>
						<td>'.wppizza_format_price($totalSalesValueToday).'</td>
						<td>'.wppizza_format_price($totalSalesValueThisMonth).'</td>
						<td>'.wppizza_format_price($totalSalesValueLastMonth).'</td>
					</tr>';
			$dashboard_widget['sales_sales'] = '<tr>
						<td>'.__('Sales','wppizza-admin').'</td>
						<td>'.$totalSalesCount.'</td>
						<td>'.$totalSalesCountToday.'</td>
						<td>'.$totalSalesCountThisMonth.'</td>
						<td>'.$totalSalesCountLastMonth.'</td>
					</tr>';
			$dashboard_widget['sales_items'] = '<tr>
						<td>'.__('Items Sold','wppizza-admin').'</td>
						<td>'.$totalItemsCount.'</td>
						<td>'.$totalItemsCountToday.'</td>
						<td>'.$totalItemsCountThisMonth.'</td>
						<td>'.$totalItemsCountLastMonth.'</td>
					</tr>';

		$dashboard_widget['_sales'] = '</tbody>
			</table>
		';

		/*
			gateways summary provided there's more than one
			to start off with
		*/
		if(!empty($data['totals']['payment_type']) && count($data['totals']['payment_type']) > 1 ){
			$dashboard_widget['gateways'] = '
				<table class="wppizza-dash wppizza-dash-sales">
					<thead>
						<tr>
							<th>'.$data['currency'].'&nbsp;</th>
							<th>'.__('Total','wppizza-admin').'</th>
							<th>'.__('Today','wppizza-admin').'</th>
							<th>'.__('This Month','wppizza-admin').'</th>
							<th>'.__('Last Month','wppizza-admin').'</th>
					</tr>
					</thead>
					<tbody>
			';


			foreach($data['totals']['payment_type'] as $gw_id => $arr){

				$today_date = date("Y-m-d", WPPIZZA_WP_TIME);
				$gw_ident = strtoupper($gw_id);//uppercase to match key of 'gateway_sales'
				$gw_name = '<span style="font-size:90%;margin:0;padding:0">'.$gw_id.'</span>';
				$gw_total = empty($arr['total_value']) ? 0 : $arr['total_value'] ;
				$gw_total_count = empty($arr['total_sales']) ? '' : '<span style="font-size:70%;margin:0;padding:0 0 0 3px">'.$arr['total_sales'].'x</span>' ;
				$gw_today = empty($data['dataset']['gateway_sales'][$gw_ident][$today_date]) ? 0 : $data['dataset']['gateway_sales'][$gw_ident][$today_date];
				$gw_this_month = empty($data['dataset']['gateway_sales'][$gw_ident]['total_this_month']) ? 0 : $data['dataset']['gateway_sales'][$gw_ident]['total_this_month'];
				$gw_last_month = empty($data['dataset']['gateway_sales'][$gw_ident]['total_last_month']) ? 0 : $data['dataset']['gateway_sales'][$gw_ident]['total_last_month'];


				$dashboard_widget['gateways'] .= '
					<tr>
						<td>'.$gw_name . $gw_total_count . '</td>
						<td>'.wppizza_format_price($gw_total, null).'</td>
						<td>'.wppizza_format_price($gw_today, null).'</td>
						<td>'.wppizza_format_price($gw_this_month, null).'</td>
						<td>'.wppizza_format_price($gw_last_month, null).'</td>
					</tr>
				';

			}

			$dashboard_widget['gateways'] .= '
					</tbody>
				</table>
			';

		}

		/*
			items / categories
		*/
		$dashboard_widget['items_categories'] = '
			<table class="wppizza-dash wppizza-dash-items">
				<thead>
					<tr>
						<th>'.__('Menu Items (active)','wppizza-admin').'</th>
						<th>'.__('Categories','wppizza-admin').'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>'.$count_posts.'</td>
						<td>'.$count_categories.'</td>
					</tr>
				</tbody>
			</table>
		';
		/*
			registered customer values
		*/
		$dashboard_widget['customers'] = '
			<table class="wppizza-dash wppizza-dash-customers">
				<thead>
					<tr>
						<th>'.__('Customers','wppizza-admin').'</th>
						<th>'.__('Registered','wppizza-admin').'</th>
						<th>'.__('Guests','wppizza-admin').'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>'.__('Unique','wppizza-admin').'</td>
						<td>'.$usersRegisteredCount.'</td>
						<td>'.$usersGuestCount.'</td>
					</tr>
					<tr>
						<td>'.__('Sales Value','wppizza-admin').'</td>
						<td>'.wppizza_format_price($usersRegisteredTotalValue).'</td>
						<td>'.wppizza_format_price($usersGuestTotalValue).' </td>
					</tr>
					<tr>
						<td>'.__('Items Sold','wppizza-admin').'</td>
						<td>'.$usersRegisteredTotalItems.'</td>
						<td>'.$usersGuestTotalItems.'</td>
					</tr>
				</tbody>
			</table>
		';
		/*
			recent orders
		*/
		if(!empty($recent)){
		$dashboard_widget['recent'] = '
			<table class="wppizza-dash wppizza-dash-recent-orders">
				<thead>
					<tr>
						<th colspan="2">'.__('Recent Orders','wppizza-admin').'</th>
					</tr>
				</thead>
				<tbody>
					'.$recent.'
				</tbody>
			</table>
		';
		}
		/*
			allow filteringbefor implode
		*/
		$dashboard_widget = apply_filters('wppizza_filter_dashboard_widget', $dashboard_widget);
		$dashboard_widget = implode('', $dashboard_widget);

		/*
			add to markup
		*/
		$markup .= $dashboard_widget;


		if(empty($return_markup)){
			echo $markup;
		}else{
			return $markup;
		}
	}
}
?>