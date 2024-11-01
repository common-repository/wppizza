<?php
/**************************************************
	[ajax only]
**************************************************/
if(!defined('DOING_AJAX') || !DOING_AJAX){
	header('HTTP/1.0 400 Bad Request', true, 400);
	print"you cannot call this script directly";
  exit; //just for good measure
}
/**************************************************
	catch all echo'd output that really 
	should not even be there for ajax requests 
	(Avada 7.something, for example, echos stylesheets directly instead of enqueueing them .
	You would have thought they know by now how to use stylesheets in WP ...)
**************************************************/
	ob_start();

/**************************************************
	set json header
**************************************************/
	header('Content-type: application/json');
/**************************************************
	[custom headers to aid debugging]
**************************************************/
	header( "HTTP/1.1 200 OK (WPPizza)" );
	header('Plugin: WPPizza v'.WPPIZZA_VERSION.'');

/**************************************************
	[testing variables - uncomment when needed]
**************************************************/
//sleep(5);//when testing jquery fadeins etc

/**************************************************
	[supress errors unless debug]
**************************************************/
if(!wppizza_debug()){
	error_reporting(0);
}

/**************************************************
	[add globals to use]
**************************************************/
global $wppizza_options, $blog_id;


/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
*
*
*
*	aJax Calls Actions
*
*
*
*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\**\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\**\/*\/*\/*\/*\/*/

/*****************************************************
	[action hook for modules to hook into]
*****************************************************/
do_action('wppizza_ajax', $wppizza_options, $blog_id);

/***************************************************************
*
*
*	[load cached cart ]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='loadcart'){

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = (!empty($_POST['vars']['isCheckout']) && $_POST['vars']['isCheckout']==='true') ? true : false;


	/*********
		get current session cart data before adding
	*********/
	$obj['cart'] = WPPIZZA()->session->get_cart($is_checkout);
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */

	/*********
	 get cart contents html  (including pickup options and is_open ident) to replace via ajax
	*********/
	$obj['markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/**********
		get current status of shop (open/closed/gmtoffset etc)
		with number of seconds remaining /timestamp etc
		until the status changes to the opposite
		- only if "wppizza_filter_js_set_status_functions" is used
	***********/
	if(has_filter('wppizza_filter_js_set_status_functions')){
		$obj['shop_status'] = wppizza_get_shop_status();
	}


	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj, 'is_checkout' => $is_checkout);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request - filterable
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/************************************************************************************************
*
*
*	[getting totals via shortcode - also used for minicart]
*
*
************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='gettotals'){

	/* set type */
	$type = $_POST['vars']['type'];

	/* set localization */
	$txt = $wppizza_options['confirmation_form']['localization'] + $wppizza_options['localization'];

	/* get order data formatted from session */
	$order_formatted = WPPIZZA()->order->session_formatted();
	$can_checkout = $order_formatted['checkout_parameters']['can_checkout'];
	$is_pickup = $order_formatted['checkout_parameters']['is_pickup'];
	$min_order_required = $order_formatted['checkout_parameters']['min_order_required'];

	/* how many items in cart (counting quantity) */
	$no_of_items = 0;
	if(!empty($order_formatted['order']['items'])){
	foreach($order_formatted['order']['items'] as $item){
		$no_of_items += ($item['quantity']);
	}}

	/*********
		are we on checkout page ?
	*********/
	//$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;
	/* item count */
	$obj['itemcount'] = !empty($no_of_items) ? '<span class="'.WPPIZZA_PREFIX.'-totals-itemscount">'.$no_of_items.'</span>' : '&nbsp;' ;	/* number of items */

	/* item count as integer*/
	$obj['no_of_items'] = !empty($no_of_items) ? $no_of_items : 0 ;	/* number of items as integer*/


	/* total order value - but force zero if no items (as min delivery costs might still be set ) */
	$obj['total'] = (!empty($order_formatted['summary']['total'][0]['value_formatted']) && !empty($no_of_items) )? $order_formatted['summary']['total'][0]['value_formatted'] : wppizza_format_price(0);/* total order */

	/* total value items only  - but force zero if no items (as min delivery costs might still be set ) */
	$obj['total_price_items'] = (!empty($order_formatted['summary']['total_price_items'][0]['value_formatted']) && !empty($no_of_items) ) ? $order_formatted['summary']['total_price_items'][0]['value_formatted'] : wppizza_format_price(0);/* total items only */
	/** view cart as button empty if no items*/
	if(empty($no_of_items)){
		$obj['view_cart_button'] = '';
	}else{
		$obj['view_cart_button'] = '<input type="button" value="'.$txt['view_cart'].'" />';
	}

	/*
		cart empty
	*/
	$obj['cart_empty']= WPPIZZA() -> markup_maincart -> cart_empty($order_formatted, $type);

	/*
		itemised items table
	*/
  	$obj['items']= WPPIZZA() -> markup_maincart -> itemised_markup($order_formatted, $type);

	/*
		subtotals/summary table
	*/
	$obj['summary'] = WPPIZZA() -> markup_maincart -> summary_markup($order_formatted, $type);

	/*
		pickup / delivery note
	*/
	$obj['pickup_note'] = WPPIZZA() -> markup_maincart -> pickup_note($order_formatted, $type);

	/*
		minimum order required text - currently not in use
	*/
	$obj['minimum_order'] = WPPIZZA() -> markup_maincart -> minimum_order($order_formatted, $type);

	/*
		checkout button
	*/
	$obj['checkout_button'] = WPPIZZA() -> markup_maincart -> checkout_button($order_formatted, $type);

	/*
		empty_cart button
	*/
	$obj['emptycart_button'] = WPPIZZA() -> markup_maincart -> empty_cart_button($order_formatted, $type);

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/**********
		if we are *only* using the minicart
		we actually also need to make sure to append the get_cart_json hiddden input there to have it always at least once loaded
	***********/
	$obj['get_cart_json'] = WPPIZZA() -> markup_maincart -> get_cart_json($order_formatted, 'minicart');

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();

}
/***************************************************************
*
*
*	[add item to cart]
*
*
***************************************************************/

if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='add' && !empty($_POST['vars']['id'])){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;

	/*********
		sanitize element id - just in case
	*********/
	$element_id = wppizza_validate_alpha_only($_POST['vars']['id']);

	/*********
		add item to session data
	*********/
	$do_cart = WPPIZZA()->session->add_item_to_cart($element_id, $is_checkout);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = !empty($do_cart['cart']) ? $do_cart['cart'] : false;
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */
	/*********
	 get cart contents html  (including pickup options and is_open ident) to replace via ajax
	*********/
	$obj['markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], true);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
exit();
}

/***************************************************************
*
*
*	[empty cart]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='empty'){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;

	/*********
		empty cart session
	*********/
	$do_cart = WPPIZZA()->session->empty_cart($is_checkout);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */

	/*********
	 get cart contents html  (including pickup options and is_open ident) to replace via ajax
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], true);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/***************************************************************
*
*
*	[modify item in cart]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='modify' && !empty($_POST['vars']['id'])){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;

	/*********
		sanitize element id - just in case
	*********/
	$session_cart_item_id=wppizza_validate_element_id($_POST['vars']['id']);

	/*********
		how many (-1 to remove one , 0 to remove all, >=1 to set distinct quantity)
	*********/
	$quantity = ((int)$_POST['vars']['quantity'] == -1 ) ? -1 : abs((int)$_POST['vars']['quantity']);

	/*********
		remove item from session data
	*********/
	$do_cart = WPPIZZA()->session->modify_items_in_cart($session_cart_item_id, $quantity, $is_checkout);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */

	/*********
	 get cart contents html  (includin pickup options and is_open ident) to replace via ajax
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], true);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
exit();
}


/***************************************************************
*
*
*	[modify item on checkout page]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='update-order' && !empty($_POST['vars']['id'])){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;


	/***************************************************************
		[get and parse all user post variables on checkout and save in session
		(including gateway selected)]
	***************************************************************/
	if($is_checkout && !empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}

	/*********
		sanitize element id - just in case
	*********/
	$session_cart_item_id=wppizza_validate_element_id($_POST['vars']['id']);

	/*********
		how many (-1 to remove one , 0 to remove all, >=1 to set distinct quantity)
	*********/
	$quantity = ((int)$_POST['vars']['quantity'] == -1 ) ? -1 : abs((int)$_POST['vars']['quantity']);

	/*********
		modify item in session data
	*********/
	$do_cart = WPPIZZA()->session->modify_items_in_cart($session_cart_item_id, $quantity, $is_checkout);


	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */


	/*********
	 get cart contents html  (includin pickup options and is_open ident) to replace via ajax
	 always get this as the order page widget might have been placed on a non-orderpage page
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);


	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), true);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}


/***************************************************************
*
*
*	[repurchase previous order]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='reorder' && !empty($_POST['vars']['id'])){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;
	/*********
		sanitize element id - just in case - and explode into blog id and order id
	*********/
	$purchase_id=wppizza_validate_alpha_only($_POST['vars']['id']);
	$xArray = explode('-',$purchase_id);
	$selected_blog_id = $xArray[count($xArray)-2];
	$selected_order_id = $xArray[count($xArray)-1];

	/****************************************
		get entire order for this purchase
	****************************************/
	$args = array(
		'query' => array(
			'order_id' => $selected_order_id ,
			'payment_status' => 'COMPLETED',
			'blog_ids' => array($selected_blog_id),
		),
		'format' => false,//not required here
	);
	/*************************************************
		run query, and get results
		even single order results are always arrays
		so simply use reset here
	*************************************************/
	$order = WPPIZZA() -> db -> get_orders($args, 'ajax_reorder');
	$order = reset($order['orders']);

	/*********
		get current session cart data before adding
	*********/
	$current_cart_session = WPPIZZA()->session->get_cart($is_checkout);

	/*********
		loop through individual items and add to session
	*********/
	if(!empty($order['order_ini']['items'])){
	foreach($order['order_ini']['items'] as $session_cart_item_id => $item){
		
		$session_keys = array($item['blog_id'], $item['cat_id_selected'], $item['post_id'], $item['sizes'], $item['size']);
		$element_id = ''.WPPIZZA_SLUG.'-'.implode('-',$session_keys);
		$session_cart_item_id = implode('.',$session_keys);

		$add_item = WPPIZZA()->session->add_item_to_cart($element_id, $is_checkout, false); /* do not recalculate here , we will call this distinctly after the loop has finished */

		/** add multiple if > 1 */
		if(!empty($current_cart_session['cart']['items'][$session_cart_item_id]['quantity'])  ||  $item['quantity']>1 ){
			$adjust_quantity = !empty($current_cart_session['cart']['items'][$session_cart_item_id]['quantity']) ? $current_cart_session['cart']['items'][$session_cart_item_id]['quantity'] + $item['quantity'] : $item['quantity'] ;
			$add_item = WPPIZZA()->session->modify_items_in_cart($session_cart_item_id, $adjust_quantity, $is_checkout, false);	/* do not recalculate here , we will call this distinctly after the loop has finished */
		}

	}}
	/**********
		as sort_and_calculate_cart only recalculates the first time an item is added when looping (by setting a static variable), distinctly call this one more time after adding items with loop above
	**********/
	$do_cart = WPPIZZA()-> session -> sort_and_calculate_cart($is_checkout, true, __FUNCTION__);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart;
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */

	/*********
	 get cart contents html  (includin pickup options and is_open ident) to replace via ajax
	*********/
	$obj['markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], true);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/***************************************************************
*
*
*	[switch pickup/delivery]
*
*
***************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='order-pickup'){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;

	/*********
		set session delivery/pickup

		simply also force to pickup if 'no_delivery' in case someone
		is trying to mess around with some frontend input fields
	*********/
	$is_pickup = ($_POST['vars']['value']==='true' || $wppizza_options['order_settings']['delivery_selected']=='no_delivery' ) ? true : false;
	$set_pickup = WPPIZZA()->session->set_pickup($is_pickup);

	/*********
		set session userdata too if switching on orderpage !
	*********/
	if(!empty($_POST['vars']['data']) && $is_checkout){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}

	/*********
		get cart details from session data
	*********/
	$do_cart = WPPIZZA()->session->get_cart($is_checkout);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'] . '-' .($is_pickup ? 'true' : 'false' );	/* add current event */

	/*********
	 get cart contents html  (includin pickup options text and is_open ident ) to replace via ajax
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);
	/*********
	 get cart pickup checkbox/radio markup, replacing other cart toggles
	*********/
	$obj['cart_pickup_select']  = WPPIZZA() -> markup_pickup_choice -> attributes(null, 'cart');

	/*********
	 opening times widget(s)
	*********/
	$obj['opening_hours']  = WPPIZZA() -> markup_openingtimes -> get_markup(null, false);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}
	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/****************************************************************************************************************************************
*
*
*	[add tips]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='addtips'){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/*********
		are we on checkout page ?
	*********/
	$is_checkout = ($_POST['vars']['isCheckout']==='true') ? true : false;

	/***************************************************************
		[get and parse all user post variables and save in session
		(including gateway selected)]
	***************************************************************/
	if(!empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}

	/*********
		get cart details from session data
	*********/
	$do_cart = WPPIZZA()->session->get_cart($is_checkout);

	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */

	/*********
	 get cart contents html  (includin pickup options and is_open ident) to replace via ajax
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}
	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/****************************************************************************************************************************************
*
*
*	[changing gateways - recalculation required]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='changegateway'){

	/*********
		run an action on cart update
	*********/
	do_action('wppizza_on_cart_update', wppizza_validate_alpha_only($_POST['vars']['type']));

	/***************************************************************
		[get and parse all user post variables and save in session
		(including gateway selected)]
	***************************************************************/
	if(!empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}

	/**********
		make sure we are on checkout page
		submitted by ajax
	**********/
	$is_checkout = !empty($_POST['vars']['isCheckout']) ? true :  false;

	/*********
		get cart details from session data
	*********/
	$do_cart = WPPIZZA()->session->get_cart($is_checkout);


	/*********
	 return obj of cart variables
	*********/
	$obj['cart'] = $do_cart['cart'];
	$obj['cart']['event'] = $_POST['vars']['type'];	/* add current event */
	/*********
	 get cart contents html  (includin pickup options and is_open ident) to replace via ajax
	*********/
	$obj['cart_markup'] = WPPIZZA()->markup_maincart->cart_contents_markup_from_session($is_checkout);

	/*********
	 get complete order page contents html
	*********/
	if($is_checkout){
		/* pages markup takes data from db, so make sure we have updated the db first */
		WPPIZZA()->db->order_initialize();
		$obj['page_markup'] = WPPIZZA()->markup_pages->markup('orderpage');
	}
	/**********
		check if shop is open to be able to add things
		if this returns false there will be a js alert
	***********/
	$obj['is_open'] = wppizza_is_shop_open();

	/**********
		check if delivery is set to pickup
		to set (un)check relevant checkbox
	***********/
	$obj['is_pickup'] = wppizza_is_pickup();

	/**********
		get scripts and styles needed by
		selected gateway here too
		to load via ajax as the gateway we will have changed to will rely on it

		However, we can simply skip this if we are still reloading the page
		due to some legacy gateways being active
	***********/
	if($_POST['vars']['pageReload'] !== 'true'){
		$obj['gwAjax'] = WPPIZZA() -> gateways -> get_gateway_scripts_and_styles();
	}

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);

	/* 
		ob_end before returning obj back to ajax 
	*/
	ob_end_clean();

	print"".json_encode($obj)."";

exit();
}
/****************************************************************************************************************************************
*
*
*	[get the confirm order page]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='confirmorder'){

	/***************************************************************
		[get and parse all user post variables and save in session
	***************************************************************/
	if(!empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}
	/* update order */
	WPPIZZA()->db->order_initialize();

	/* get confirmation page markup */
	$obj['markup'] = WPPIZZA()->markup_pages->markup('confirmationpage');

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}
/****************************************************************************************************************************************
*
*
*	[update user data]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='update_userdata'){

	/***************************************************************
		[get and parse all user post variables and save in session
	***************************************************************/
	if(!empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
		/* set session */
		WPPIZZA()->session->set_userdata($posted_data);
	}

	/******************************************
		doesnt really get output anywhere
		but just for consistency - also passed on
		to action hook
	******************************************/
	$obj = $posted_data;

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);


	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
exit();
}
/****************************************************************************************************************************************
*
*
*	[logging in from orderform]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='user-login'){

	/*********
		ini object
	*********/
	$obj = array();
	$obj['button_value'] = __( 'Log In' );

	/*********
		parse posted vars
	*********/
	if(!empty($_POST['vars']['data'])){
		$posted_data = array();
		parse_str($_POST['vars']['data'], $posted_data);
	}
	$nonce = sanitize_text_field($posted_data['' . WPPIZZA_PREFIX . '_nonce_login']);
	$username = sanitize_text_field($posted_data['log']);
	$password = sanitize_text_field($posted_data['pwd']);
	$valid_login = true;

	/***************************************************************
		[verify nonce]
	***************************************************************/
	if (!wp_verify_nonce(  $nonce , '' . WPPIZZA_PREFIX . '_nonce_login' ) ) {
		/* invalid nonce */
		$valid_login = false;
	}
	/***************************************************************
		[verify credentials and login if valid]
	***************************************************************/
    $credentials = array();
    $credentials['user_login'] = $username;
    $credentials['user_password'] = $password;
    $credentials['remember'] = true;

    $user_signon = wp_signon( $credentials, false );
	if ( is_wp_error($user_signon) ){
		/* invalid login */
		$valid_login = false;
		/* login errors (not output) */
		$obj['login_errors'] = $user_signon->get_error_codes();
	}

	/***************************************************************
		[output error if any]
	***************************************************************/
	if(!$valid_login){
		$wp_error = new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));/*native wp localization*/
		$obj['error'] = '<span class="' . WPPIZZA_PREFIX . '-login-error">'.$wp_error->get_error_message().'</span>';
	}

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('obj' => $obj, 'valid_login' => $valid_login);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
}

/****************************************************************************************************************************************
*
*
*	[prepare order - when modal gateway open their overlay]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='prepareorder'){

	/***************************************
		get cart details from session data,
		update any submitted wppizza formfields
		and pass on what we need
		update userdata session, check nonces , update initialized db (by hash), get order formatted by id ect
	***************************************/

	/*****************************************
		SELECTED GATEWAY - sanitized somewhat
	*****************************************/
	$selected_gateway = preg_replace('/[^a-z0-9_]/','' , strtolower($_POST['vars']['gateway_selected']));

	/***************************************
		ini execute class
	***************************************/
	$ORDER_EXECUTE = new WPPIZZA_ORDER_EXECUTE($selected_gateway, $_POST['vars']['data']);
	$order_details = $ORDER_EXECUTE -> order_prepare($selected_gateway);

	/*
		only ever return results here - for the moment anyway
	*/
	$results = array();
	if(!empty($order_details['error'])){
		$results['error'] = $order_details['error'];
	}

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('results' => $results, 'selected_gateway' => $selected_gateway);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$results = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $results);
	print"".json_encode($results)."";
	exit();
}
/****************************************************************************************************************************************
*
*
*	[before submit order - 3rd party plugins can hook into here and return an error or true]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type'] == 'before_order_continue'){

	/*********
	 parameters to parse
	*********/
	$parameters = $_POST['vars']['param'];

	/*********
		parse posted vars
	*********/
	$parsed_postdata = array();
	if(!empty($parameters)){
		foreach($parameters as $k => $data){
			$parsed_postdata[$k] = array();
			parse_str($data, $parsed_postdata[$k]);
		}
	}
	/*********
		add order id
	*********/
	$parsed_postdata['order_id'] = wppizza_order_id();


	/*********
		remove some superflous
	*********/
	unset($parsed_postdata['post_data']['wppizza_nonce_checkout']);
	unset($parsed_postdata['post_data']['_wp_http_referer']);


	/*********
		run the filters return to ajax request

		a plugin that hooks into this should
		return an array like this if there are errors
		$results['pluginSlug'] = array(
			'errors' => array(
				'code' => 123,
				'message' => 'some error message ',
				'action' => 'some error message ',//a function name to run (if any
			)
		);
		if 'errors' is not defined, it will be treated like success and order execution
		will then continue as planned
	*********/
	$results = apply_filters('wppizza_filter_verify_on_order_submit', array(), $parsed_postdata);

	/*********
		we start off assuming everything is hunkydoory
	*********/
	$obj = array('success' =>  true,);

	/*********
		check for any plugins that hook into this filter
		and retun an error
	*********/
	if(!empty($results)){
	foreach($results as $plugin => $pluginData){
		if(!empty($pluginData['errors'])){
			$e = $pluginData['errors'] ;
			$obj = array(
				'errors' =>  array(
					'code' =>  !empty($e['code']) ? $e['code'] : 'U-001' ,
					'message' =>  !empty($e['message']) ? $e['message'] : 'Error, that is all we know' ,
					'func' =>  !empty($e['func']) ? $e['func'] :  '' ,//a js function name to run (if any) - must be defined (obviously)
					'args' =>  !empty($e['args']) ? $e['args'] :  '' ,//arguments to pass onto function (if any)
				),
			);
		break; //we only want one (for the tiome being anyway)
		}
	}}
	/*********
		return to ajax request
	*********/
	print"".json_encode($obj)."";
exit();
}
/****************************************************************************************************************************************
*
*
*	[submit order]
*
*
****************************************************************************************************************************************/
if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='submitorder'){

	/*********
		INI RESULT
	*********/
	$results = array();

	/*********
		SELECTED GATEWAY - sanitized somewhat
	*********/
	$selected_gateway = preg_replace('/[^a-z0-9_]/','' , strtolower($_POST['vars']['gateway_selected']));


	/********
		INI CLASS
	*********/
	$ORDER_EXECUTE = new WPPIZZA_ORDER_EXECUTE($selected_gateway, $_POST['vars']['wppizza_data']);


	/**************************************************************
	*
	*	PREPARE ORDER
	*
	**************************************************************/
	/*
		ini execute and prepare (update), returning
		sanitized order details
	*/
	$order_details = $ORDER_EXECUTE -> order_prepare($selected_gateway);
	/* simply exit if error returned (js will output error) */
	if(!empty($order_details['error'])){
		/* bail and return errors */
		print"".json_encode($order_details)."";
	exit();
	}

	/***************************************************************
		force COD - even if cc gateway - if total order is zero (unless it's COD/CCOD already anyway)
		(as no payment/cc gateway will process a payment of zero - for obvious reasons)
		updating above INPROGRESS order
	****************************************************************/
	if($order_details['order']['summary']['total'][0]['value'] <= 0 && !in_array($selected_gateway,  array('cod', 'ccod'))){

		/*
			forcing cod
			bypassing any redirections below
		*/
		$forced_cod_gateway = 'COD';

		/***************************************************************
			updating above INPROGRESS order
		****************************************************************/
		$update_db_values = array();
		$update_db_values['initiator'] 	= array('type'=> '%s', 'data' => $forced_cod_gateway);
		$order_update = WPPIZZA()->db->update_order($order_details['order']['site']['blog_id']['value'], $order_details['order']['ordervars']['order_id']['value'], false , $update_db_values, 'INPROGRESS');
		/*
			set order_details payment_gateway value
		*/
		$order_details['order']['ordervars']['payment_gateway']['value'] = $forced_cod_gateway;

	}


	/**************************************************************
	*
	*	REDIRECT - if set and not forced to be COD
	*
	**************************************************************/
	if(empty($forced_cod_gateway)){

		/*
		*	allow to interject before order redirect
		*	@since v3.1.7
		*/
		do_action('wppizza_before_order_redirect', $order_details['order']);


		/*
			if gateway requires redirection, do that instead
			checks if selected gateway has method "payment_redirect"
		*/
		$redirect = $ORDER_EXECUTE -> order_redirect($order_details['order']);
		/*
			[return and exit if we are redirecting and/or there are errors]
		*/
		if(!empty($redirect)){
			print"".json_encode($redirect)."";
		exit();
		}

	}
	/**************************************************************
	*
	*	EXECUTE - no redirect, cod style or overlay gateways
	*
	**************************************************************/
	/*
	*	allow to interject before order execution
	*	non-redirect gateways
	*	@since v3.1.7
	*/
	do_action('wppizza_before_order_execute', $order_details['order']);

	$results = $ORDER_EXECUTE -> order_execute($order_details['order']);
	/*
		[bail if any errors]
	*/
	if(isset($results['error'])){
		/* bail and return errors */
		print"".json_encode($results)."";
		exit();
	}

	/*****************************************************
		[action hook for modules to hook into if required]
	*****************************************************/
	$action_args = array('results' => $results);
	//3rd parameter indicates if items in cart were modified or not in this ajax call
	do_action('wppizza_after_ajax', $action_args, $_POST['vars']['type'], false);

	/*********
		return to ajax request
	*********/
	$results = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $results);
	print"".json_encode($results)."";
	exit();
}

/****************************************************************************************************************************************
#
#
#
#	[ADMIN - ORDER HISTORY ON PAGES WITH SHORTCODE]
#
#
#
****************************************************************************************************************************************/
	/*************************************************************************************
	*
	*	[FRONTEND - IF USED/ADDED BY SHORTCODE]
	*	[admin order history -> get orders , including pagination]
	*
	*************************************************************************************/
	if(isset($_POST['vars']['type']) && $_POST['vars']['type']=='admin-order-history'){


		/*
			pass on same type and attributes as shortcode set
		*/
		$type = 'admin_orderhistory';

		$atts = json_decode(stripslashes($_POST['vars']['atts']), true);
		$atts['post_id'] = (int)$_POST['vars']['post_id'];

		/*
			get html
		*/
		$results['html'] = WPPIZZA() -> markup_pages -> markup($type, $atts, true);

		/*
			if we need audio notifications run a quick query to see if there are any new orders
			and return
		*/
		if(!empty($atts['audio_notify'])){
			$args = array(
				'query'=>array(
					'payment_status' => 'COMPLETED',//get completed orders only
					'order_status' => 'NEW',//get orders with status NEW only
					'summary' => true,// only return count/totals
				),
			);
			$new_orders = wppizza_get_orders($args, 'ajax_admin-order-history');

			if(!empty($new_orders['total_number_of_orders'])){
				$results['notify'] = true;
			}
		}

		/*****************************************************
			[action hook for modules to hook into if required]
		*****************************************************/
		$action_args = array('results' => $results, 'atts' => $atts);
		//3rd parameter indicates if items in cart were modified or not in this ajax call
		do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), false);


		/*********
			return to ajax request
		*********/
		$results = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $results);
		print"".json_encode($results)."";
		exit();
	}
	/*************************************************************************************
	*
	*	[FRONTEND - IF USED/ADDED BY SHORTCODE]
	*	[admin order history -> view/print order]
	*
	*************************************************************************************/
	if( isset($_POST['vars']['type']) && $_POST['vars']['type']=='admin-view-order' && !empty($_POST['vars']['uoKey']) ){

		/*
			get unique order key and split into blog/order id
		*/
		$uoKey = explode('_',$_POST['vars']['uoKey']);

		/*ini array for json*/
		$obj=array();

		/*blog_id*/
		$blog_id = !empty($uoKey[0]) ? (int)$uoKey[0] : 0 ;

		/*order id*/
		$order_id = !empty($uoKey[1]) ? (int)$uoKey[1] : 0 ;

		/*template type*/
		$template_type='print';

		/****************************************
			get the order
		****************************************/
		$args = array(
			'query' => array(
				'order_id' => $order_id ,
				'payment_status' => array('COMPLETED','REFUNDED') ,
				/* in case we are in a multisite setup */
				'blogs' => array($blog_id),
			),
			/* add in class idents here as we'll need them for email templates */
			'format' => array(
				'blog_options' => array('localization', 'blog_info', 'date_format'),// add some additional - perhaps useful - info to pass on to gateways
				'sections' => true,//leave order sections in its distinct [section] array
			),
		);
		/*************************************************
			run query, and get results
			even single order results are always arrays
			so simply use reset here
		*************************************************/
		$order = WPPIZZA() -> db -> get_orders($args, 'ajax_admin-view-order');
		$order = reset($order['orders']);

		/****************************************
			no order exists that could be used
			as preview
		****************************************/
		if(empty($order)){
			$markup['str']="Error [AOH-101]:".__(' Sorry, this order does not exist.','wppizza-admin');
			print"".json_encode($markup)."";
			exit();
		}

		/***************************************
			get selected template id and vars
		***************************************/
		global $wppizza_options;
		$template_id = apply_filters('wppizza_filter_admin_print_template_id', $wppizza_options['templates_apply'][$template_type]);

		/* default values  (-1) */
		$as_html = true;
		$template_values = false;

		/* saved template , anything != -1 */
		if($template_id != -1){
			/**get set print template options**/
			$template_options = get_option(WPPIZZA_SLUG.'_templates_'.$template_type,0);
			$template_options = apply_filters('wppizza_filter_template_options', $template_options, $template_type, $order);
			$template_values = $template_options[$template_id];
			$as_html = ($template_values['mail_type'] == 'phpmailer') ?  true : false ;
		}

		/****************************************
			what size do we want to open the window
		****************************************/
		$obj['window-width'] = apply_filters('wppizza_filter_admin_print_window_width','750');
		$obj['window-height'] = apply_filters('wppizza_filter_admin_print_window_height','550');
		/****************************************
			object to return to ajax, content type
		****************************************/
		$obj['content-type'] = ($as_html) ? 'text/html' : 'text/plain';
		/****************************************
			get html or plaintext output
		****************************************/
		if($as_html){
			$obj['markup']['html'] = WPPIZZA()->templates_email_print->get_template_email_html_sections_markup($order, $template_values, $template_type, $template_id );
		}else{
			/* plaintext returns sections too, so get the array first */
			$tpl = WPPIZZA()->templates_email_print->get_template_email_plaintext_sections_markup($order, $template_values, $template_type, $template_id  );
			$obj['markup']['plaintext'] = $tpl['markup'];
		}

		/*****************************************************
			[action hook for modules to hook into if required]
		*****************************************************/
		$action_args = array('obj' => $obj, 'order_id' => $order_id);
		//3rd parameter indicates if items in cart were modified or not in this ajax call
		do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), false);



		/*********
			return to ajax request
		*********/
		$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
		print"".json_encode($obj)."";
		exit();
	}
	/*************************************************************************************
	*
	*	[FRONTEND - IF USED/ADDED BY SHORTCODE]
	*	[admin order history -> admin-change-status]
	*
	*************************************************************************************/
	if( isset($_POST['vars']['type']) && $_POST['vars']['type']=='admin-change-status' && !empty($_POST['vars']['uoKey']) ){


		/*
			saving disabled
		*/
		if(WPPIZZA_DEV_ADMIN_NO_SAVE){
			$obj['update_prohibited'] = __('Update Prohibited', 'wppizza-admin');
			print"".json_encode($obj)."";
		exit();
		}



		/*
			get unique order key and split into blog/order id
		*/
		$uoKey = explode('_',$_POST['vars']['uoKey']);

		/*ini array for json*/
		$obj=array();

		/*blog_id*/
		$blog_id = !empty($uoKey[0]) ? (int)$uoKey[0] : 0 ;

		/*order id*/
		$order_id = !empty($uoKey[1]) ? (int)$uoKey[1] : 0 ;

		/****get oder status ***/
		$order_status=esc_sql($_POST['vars']['status']);

		/***************************************************************
			update order status with update timestamp
		****************************************************************/
		$update_db_values = array();

		/**
			amend order update
		**/
		$update_db_values['order_update'] 	= array('type'=> '%s', 'data' =>date('Y-m-d H:i:s', WPPIZZA_WP_TIME));

		/**
			set status
		**/
		$update_db_values['order_status'] 	= array('type'=> '%s', 'data' => $order_status );

		/**
			update payment status too if set to refunded
		**/
//			if($order_status=='REFUNDED' ){
//				$update_db_values['payment_status'] 	= array('type'=> '%s', 'data' => $order_status );
//			}
//
		/**
			set order delivered time if set as delivered
		**/
		if(in_array($order_status, unserialize(WPPIZZA_ADMIN_ORDER_DELIVERED_STATUS))){
			$update_db_values['order_delivered']= array('type'=> '%s', 'data' =>date('Y-m-d H:i:s', WPPIZZA_WP_TIME));
		}else{
			$update_db_values['order_delivered']= array('type'=> '%s', 'data' =>'0000-00-00 00:00:00');
		}

		/*
			run update query, making sure only to update 'COMPLETED' orders
		*/
		$order_update = WPPIZZA()->db->update_order($blog_id, $order_id, false , $update_db_values, 'COMPLETED' );

		/**
			return new timestamp formatted to js
		**/
		$obj['update_timestamp']= wppizza_orderdate_formatted(date("Y-m-d H:i:s",WPPIZZA_WP_TIME));
		$obj['update_timestamp']= apply_filters('wppizza_filter_order_history_update_timestamp', $obj['update_timestamp']['formatted'], $obj['update_timestamp']['timestamp']);

		/*
			allow an action to run on order status change
		*/
		$obj['orderstatus_change_alert'] = '';/* ini as empty*/

		/*
			if wppizza_on_orderstatus_change filter has been added
			run filter in process_orderstatus_change and
			return alert as set
		*/
		if(has_filter('wppizza_on_orderstatus_change')){
			/* using helper function since 3.6 */
			$obj['orderstatus_change_alert'] = WPPIZZA() -> admin_helper -> process_orderstatus_change($blog_id, $order_id, $order_status);
		}

		/*****************************************************
			[action hook for modules to hook into if required]
		*****************************************************/
		$action_args = array('obj' => $obj);
		//3rd parameter indicates if items in cart were modified or not in this ajax call
		do_action('wppizza_after_ajax', $action_args, str_replace('-','_',$_POST['vars']['type']), false);

	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
	}


	/*************************************************************************************
	*
	*	[FRONTEND - IF USED/ADDED BY SHORTCODE]
	*	[admin order history -> admin-delete-order]
	*
	*************************************************************************************/
	if( isset($_POST['vars']['type']) && $_POST['vars']['type']=='admin-delete-order' && !empty($_POST['vars']['uoKey']) ){

		/*
			saving disabled
		*/
		if(WPPIZZA_DEV_ADMIN_NO_SAVE){
			$obj['update_prohibited'] = __('Update Prohibited', 'wppizza-admin');
			print"".json_encode($obj)."";
		exit();
		}
		/*
			missing credentials
		*/
		if(!current_user_can('wppizza_cap_delete_order')){
			$obj['update_prohibited'] = __('Error: You need order delete permissions to perform this action.', 'wppizza-admin');
			print"".json_encode($obj)."";
		exit();
		}

		/*
			blog_id / order id
		*/
		$_id = explode('_', $_POST['vars']['uoKey']);
		//blogid
		$blog_id=(int)$_id[0];
		/*order id*/
		$order_delete_id=(int)$_id[1];
		/* delete from db */
		$res = WPPIZZA()->db->delete_order($order_delete_id, $blog_id);
		/* ajax alert */
		$obj['success']="".sprintf(__('Order #%s deleted', 'wppizza-admin'), $order_delete_id )."";


	/*********
		return to ajax request
	*********/
	$obj = apply_filters('wppizza_filter_ajax_obj_'.$_POST['vars']['type'].'', $obj);
	print"".json_encode($obj)."";
	exit();
	}

exit();
?>