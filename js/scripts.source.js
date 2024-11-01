var wppizzaCartJson = {}; /* globally available object of whatever is currently in the cart */
var wppizzaOnCartInit = function(){};/* runs set functions on initializing cart */
var wppizzaUpdateCart = function(){};/* update cart by running this function */
var wppizzaTotalsBefore = function(){};/* executed just before the ajax call that will update the cart */
var wppizzaTotals = function(){};/* executed whenever there's an update to the cart */
var wppizzaRestoreOrder = function(){};/* can be used to remove loading div and re-enable order button */
var wppizzaPrepareOrder = function(){};/* modal overlay payment windows. prepare order */
var wppizzaPrettifyJsAlerts = function(){};/* replace js alerts with modal overlays if enabled*/
var wppizzaGatewayCss = {};//capture css dynamically loaded when changing gateways by ajax do remove again when re-changing (so to speak)
var wppizzaGatewayJs = {};//capture js dynamically loaded when changing gateways by ajax do remove again when re-changing (so to speak)
var wppizzaGetCheckout = function(){};/* get the checkout form via ajax, reinitializing any css/js as required - also used when changing gateways */

jQuery(document).ready(function($){

	/***************************
		class slug prefix
	***************************/
	var pluginSlug = 'wppizza' ;


	/***************************
		set some globals
	***************************/
	wppizza.shopOpen = -1 ;/* initializes as undefined */
	var usingCache = (typeof wppizza.usingCache ==='undefined') ? false : true;

	/***************************
		not using cache, check for wppizza-open class
		else we override as carts are loaded
	***************************/
	if(!usingCache){
		wppizza.shopOpen = ($('.'+pluginSlug+'-open').length > 0) ? true : false;
	}

	/****************************************************
	* set current cart object as json
	* in a globally available variable
	* called on page load, and whenever the cart gets updated via ajax
	*****************************************************/
	var set_cart_json = function(event_type){
		/***************************
			get initial cart content
			if it exists (if pages are not cached)
			also make sure to remove on empty
			or distinctly set  by cart parameter
		***************************/
		if($('#'+pluginSlug+'-cart-json').length > 0 ){
			wppizzaCartJson = JSON.parse($('#'+pluginSlug+'-cart-json').val());
		}else{
			wppizzaCartJson = {};
		}

		/* 
			event listener 3rd party plugins can listen for by adding 'wppizza_event' as a class to their element and do something when the event gets triggered 
		*/
		$('.'+pluginSlug+'_event').trigger(event_type);

	}
	//set on page load too
	set_cart_json('ini');

	/********************************************
		set to has cart if using
		main cart,
		hidden cart (orderpage),
		or gettotals shortcode
	********************************************/
	var hasCart = ($('.'+pluginSlug+'-cart').length > 0 || $('.'+pluginSlug+'-cart-novis').length > 0 || $('.'+pluginSlug+'-totals').length > 0 || $('#'+pluginSlug+'-minicart').length > 0) ? true : false;
	var hasLoginForm = ($('.'+pluginSlug+'-login-form').length > 0) ? true : false;
	var isCheckout = (typeof wppizza.isCheckout!=='undefined') ? true : false;	//checkout page (including cancel and confirmation)
	var use_confirmation_form = (typeof wppizza.cfrm!=='undefined') ? true : false;
	var is_page_reload = false;/* flag that gets set to true if whole page gets reloaded to not remove any loading divs */
	var prettify_js_alerts = (typeof wppizza.pjsa!=='undefined') ? true : false;
	var force_pickup_toggle = (typeof wppizza.fpt!=='undefined') ? true : false;//bypass isopen check when pickup toggles have forcefully been made visible even if closed and we are toggeling
	var run_refresh_on_ajaxstop = false;/* ini flag to run (or not) any functions that should run on cart refresh */
	var send_order_form_id = 'wppizza-send-order';
	var reinit_minicart_spinner = false;/* a flag that only re-inits the spinner in minicart (if enabled) after any updates as opposed to on page load (at which point this was done already */
	var gateway_selected_init = '';
	//for old / legacy gateways we must still reload the checkout page when changing gateways
	var gateway_compat_page_reload = (typeof wppizza['compat'] !== 'undefined'  && typeof wppizza['compat'].gwChg !== 'undefined' ) ?  true : false;
	//for some old/legacy gateways, we must run wppizza_gateway_init on load from here
	var gateway_compat_init = (typeof wppizza['compat'] !== 'undefined'  && typeof wppizza['compat'].gwInit !== 'undefined' ) ?  true : false;
	//init accordion on category/menu items
	var useAccordion = (typeof wppizza['acc'] !== 'undefined' ) ?  true : false;


	/******************************
	* all ajax start function
	* must be before spinner
	*******************************/
	var ajaxStartCount = 0;
	$( document ).ajaxStart(function() {
		ajaxStartCount++;
	});

	/******************************
	* all ajax complete function
	* runs BEFORE AJAX STOP
	*******************************/
	$( document ).ajaxComplete(function(event, xhr, settings) {
		wppizza_validator();/*(re)initializes validator - order page only */
	});
	/******************************
	* all ajax requests that error out
	*******************************/
	$( document ).ajaxError(function(event, xhr, textStatus, errorThrown) {
		console.log('error :');
		console.log(errorThrown);
		console.log(textStatus);
		console.log(xhr.responseText);
		removeLoadingDiv();
	});

	/******************************
	* after all ajax stop
	*******************************/
	var ajaxStopCount = 0;
	var forceAjaxStopRefresh = false;
	var spinnerCount = 0;
	$(document).ajaxStop(function() {

		ajaxStopCount++;

		if(spinnerCount == 0){
			wppizza_spinner('complete');/* re-ini spinner when all ajax is said and done */
		}

		/*
			if we are *not* reloading the page anyway,
			remove all loading divs
		*/
		if(!is_page_reload){
			removeLoadingDiv();
		}

		if(ajaxStopCount == 1 || forceAjaxStopRefresh ){

			wppizzaCartRefreshed(wppizza.funcCartRefr, run_refresh_on_ajaxstop);/**also run any cart refreshed functions**/
			/*
				always reset back to false after run
			*/
			run_refresh_on_ajaxstop = false;

			/*
				always reset back to false after run
			*/
			forceAjaxStopRefresh = false;

		}
	});


	/****************************************************
	*
	* somewhat "prettify" js alerts, provided it's enabled in wppizza->layout
	* (will not do anything for "confirms" as it's simply not really doable without jumping through 1000 hoops and would only be a hack)
	*
	*****************************************************/
	/* open modal window instead of native js */
	wppizzaPrettifyJsAlerts = function(txt, alert_type){

		/* make sure we decode entities in title */
		var modal_title = new DOMParser().parseFromString(wppizza.pjsa.h1, 'text/html');
		modal_title = modal_title.documentElement.textContent;

		/*set ok / confirm buttons */
		var button_ok = wppizza.pjsa.ok;

		d = document;

		if(d.getElementById('wppizzaJsAlert')) return;

		mObj = d.getElementsByTagName('body')[0].appendChild(d.createElement('div'));
		mObj.id = 'wppizzaJsAlert';
		mObj.style.height = d.documentElement.scrollHeight + 'px';

		alertObj = mObj.appendChild(d.createElement('div'));
		alertObj.id = 'wppizzaAlertBox';
		if(d.all && !window.opera) alertObj.style.top = document.documentElement.scrollTop + 'px';
		alertObj.style.left = (d.documentElement.scrollWidth - alertObj.offsetWidth)/2 + 'px';
		alertObj.style.visiblity='visible';

		/* title */
		h1 = alertObj.appendChild(d.createElement('div'));
		h1.id = 'wppizzaAlertTitle';
		h1.appendChild(d.createTextNode(modal_title));

		/* message */
		msg = alertObj.appendChild(d.createElement('p'));
		msg.innerHTML = txt;

		/* buttons */
		btn_wrap = alertObj.appendChild(d.createElement('div'));
		btn_wrap.id = 'btnWrap';

		btn_ok = btn_wrap.appendChild(d.createElement('button'));
		btn_ok.id = 'wppizzaAlertOk';
		btn_ok.innerHTML = button_ok;

		/* clicking ok */
		btn_ok.onclick = function() {
			removePrettifyJsAlerts(true);
		return false;
		}

		alertObj.style.display = 'block';
	return ;
	}

	/*
		remove modal window again
	*/
	var removePrettifyJsAlerts = function(e){
		document.getElementsByTagName('body')[0].removeChild(document.getElementById('wppizzaJsAlert'));
	}

	/****************************
		prepare order, saving
		all user data to db
		mainly for overlay gateways
	****************************/
	wppizzaPrepareOrder = function(gateway_selected){

		var data = $('#'+pluginSlug+'-send-order').serialize();
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json', vars:{'type':'prepareorder','gateway_selected':gateway_selected, 'data': data}, ext : wppizza.extend }, function(r){

			/* alert any errors */
			if(typeof r.error!=='undefined'){
				var error_id = [] ;
				var error_message = [] ;
				/* set errors to implode */
				$.each(r.error,function(e,v){
					error_id[e] = v.error_id;
					error_message[e] = v.error_message;
				});

				/* set error div*/
				var error_info = 'ERROR: '+error_id.join('|')+'\n';
					error_info += ''+error_message.join('\n')+'';

				if(prettify_js_alerts){//using prettified alerts
					wppizzaPrettifyJsAlerts(error_info, 'alert');
				}else{
					alert(error_info);
				}

				return;
			}

			run_refresh_on_ajaxstop = false;

		},'json');
	};

	/****************************
		remove all loading divs
		and reenable order button
		mainly for overlay gateways
	****************************/
	wppizzaRestoreOrder = function(){
		removeLoadingDiv();
		$('.'+pluginSlug+'-ordernow, #'+pluginSlug+'-ordernow').attr('disabled', false);//re enable send order button(s)
	};


	/********************************************************************************
	*
	*	instanciating some functions we need before all other
	*
	********************************************************************************/
	/****************************
		add loading gifs
		to all instances
	****************************/
	var addLoadingDiv = function(element, loading_class){

	/* IE doesnt like setting defaults in function parameters , so we have to do the following */
	  if(element === undefined) {
	      element = false;
	   }
	  if(loading_class === undefined) {
	      loading_class = false;
	   }

		/**if on orderpage, cover whole page**/
		if(isCheckout){
			if(!element){
				$('html').css({'position':'relative'});/*stretch html to make loading cover whole page*/
				$('body').prepend('<div class="wppizza-loading"></div>');
				$('.'+pluginSlug+'-ordernow').attr('disabled', 'true');//disable send order button
			}
		}else{
			/* add specific loading class */
			if(element && loading_class){
				element.prepend('<div class="'+loading_class+'"></div>');
			}else{
				/*will also cover any buttons so no need to disable those specifically */
				$('.'+pluginSlug+'-cart').prepend('<div class="wppizza-loading"></div>');
			}
		}
	};
	/****************************
		remove loading gifs
		from all instances
	****************************/
	var removeLoadingDiv = function(element, loading_class){

	/* IE doesnt like setting defaults in function parameters , so we have to do the following */
	  if(element === undefined) {
	      element = false;
	   }
	  if(loading_class === undefined) {
	      loading_class = false;
	   }


		/* only from specific element */
		if(element && loading_class){
			element.find('.'+loading_class+'').remove();
		}else{

			var loadingDivs=$('.'+pluginSlug+'-loading');
			if(loadingDivs.length>0){
				$.each(loadingDivs,function(e,v){
					$(this).remove();
				});
			}
		}
	};

	/******************************
	* get currently selected gateway
	* should be called on checkout only
	*******************************/
	var wppizza_get_gateway_selected = function(set_gateway){

		/*
			specifically setting a gateway
			(on change) , forcing lowercase
		*/
		if(typeof set_gateway !=='undefined' && set_gateway !== false){
			gateway_selected = set_gateway.toLowerCase();
		return gateway_selected;
		}


		/*
			selected gateway ident
			depending on if we are using dropdown, radio or hidden
		*/
		var gateway_as_hidden_input = $('input[type="hidden"][name="wppizza_gateway_selected"]');
		var gateway_as_radio_input = $('input[type="radio"][name="wppizza_gateway_selected"]:checked');
		var gateway_as_select = $('select[name="wppizza_gateway_selected"] option:selected');

		var gateway_selected = '';
		//from hidden input
		if(gateway_as_hidden_input.length > 0){
			gateway_selected = gateway_as_hidden_input.val().toLowerCase();
		}
		//from radio input
		else if(gateway_as_radio_input.length > 0){
			gateway_selected = gateway_as_radio_input.val().toLowerCase();
		}
		//from dropdown
		else if(gateway_as_select.length > 0){
			gateway_selected = gateway_as_select.val().toLowerCase();
		}

	return gateway_selected;
	}
	/*
		the gateway set on (order/confirmation)page (re)load to compare against when submitting the order
		to ensure the html is not being tampered with directly pretending an order was prepaid/cc when in fact it's COD
	*/
	gateway_selected_init = wppizza_get_gateway_selected(false);


	/****************************
		alert or skip
		if shop is not open or yet
		unknown

		after ajax request called with  a little timeout to let html() do it's thing first before showing alert
		otherwise alerts interrupt the html replacement .
		if someone finds a way to reliably chain this (i.e html('bla') first, alert('bla') second ), let me know
	****************************/
   var checkShopOpen = function(){
		if (wppizza.shopOpen === -1){
			return false;
		}
		if (!wppizza.shopOpen && hasCart){
			if(prettify_js_alerts){//using prettified alerts
				wppizzaPrettifyJsAlerts(wppizza.msg.closed, 'alert');
			}else{
				alert(wppizza.msg.closed);
			}
			return false;
		}
		return true;
    };
	/*
		set cache buster on checkout page for people that backpage
		after changing order (or after COD) order
	*/
	if(isCheckout){
		if (!!window.performance && window.performance.navigation.type === 2) {
            addLoadingDiv();
            // value 2 means "The page was accessed by navigating into the history"
            window.location.reload(true); // reload whole page
			return;
        }
	}
/**************************************************************************************************************************************************************************
*
*
*
*	[the main funtionalities af it all really]
*	[add to cart, remove from cart, refresh cart, force refresh cart, increment cart , empty cart]
*
*
*
**************************************************************************************************************************************************************************/

	/**
		(re)-load cart - available to 3rd party plugin
		if markup != '' , run update cart with passed markup
		else run another ajax request
	**/
	wppizzaUpdateCart = function(obj){

		/*
			add loading div
		*/
		addLoadingDiv();

		/*
			if we are passing on full object
			run associated functions without ajax
		*/
		if(typeof obj !== 'undefined' && typeof obj.markup !== 'undefined' && typeof obj.is_open !== 'undefined' && typeof obj.is_pickup !== 'undefined'&& typeof obj.cart !== 'undefined'){
			load_cart_set_height(obj.markup);
			set_pickup_elements_toggle(null, obj.is_pickup);//set pickup/delivery toggles as required
			wppizza.shopOpen = obj.is_open;
			run_refresh_on_ajaxstop = obj.cart;
			forceAjaxStopRefresh = true;
		return;
		}

		/*
			run full ajax if obj is not defined / false
		*/
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'loadcart', 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {
			load_cart_set_height(response.markup);
			set_pickup_elements_toggle(null, response.is_pickup);//set pickup/delivery toggles as required
			wppizza.shopOpen = response.is_open;
			run_refresh_on_ajaxstop = response.cart;
			forceAjaxStopRefresh = true;
			set_cart_json('loadcart');//update wppizzaCartJson from hidden input vars
		},'json');

	return;
	};


	/**
		(re)-load cart
	**/
	var update_cart = function(e){
		/*show loading*/
		if(e == 'refresh'){
			addLoadingDiv();
		}
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'loadcart', 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {
			load_cart_set_height(response.markup);
			wppizza.shopOpen = response.is_open;
			run_refresh_on_ajaxstop = response.cart;
			forceAjaxStopRefresh = true;
			set_cart_json('update_cart');//update wppizzaCartJson from hidden input vars
			shop_status(response, 'debug3');
		},'json');
	};


	/****************************
		if there are several carts on a page - for some reasosn -
		and / or the carts had specific heights set, we
		loop through all carts and set height for each as
		we cannot know beforehand the size it is going to be
		when carts get loaded by ajax
	****************************/
	var load_cart_set_height = function(cart_markup){

		var all_carts = $('.'+pluginSlug+'-cart');

		/* display cart after setting hight for each */
		$.each(all_carts,function(e,v){
			var this_cart = $(this);
			var cart_id = this_cart.attr('id');
			var cart_height_from_id = cart_id.split('-').pop(-1);
			/*
				height not set, simply show markup
				else set height on itemised table body
			*/
			if(cart_height_from_id <= 0 || cart_height_from_id==''){
				$(this).html(cart_markup);
			}else{
				$(this).html(cart_markup);
				var cart_set_itemised_tbody_height = $('#'+cart_id+' .'+pluginSlug+'-order-itemised > tbody').css({'height' : ''+cart_height_from_id+'px', 'min-height' : ''+cart_height_from_id+'px', 'max-height' : ''+cart_height_from_id+'px'});
			}
		});
		return;
	};



	/****************************
	*	load cart dynamically on page load
	*	if using cache
	*	alternatively trigger update by .wppizza-cart-refresh
	****************************/
	if(hasCart){
		if(usingCache){
			update_cart('load');
		}
		$(document).on('click', '.'+pluginSlug+'-cart-refresh', function(e){
			/*show loading*/
			//addLoadingDiv();
			update_cart('refresh');
		});
	}


	/**
		run defined functions before
		each cart refresh/update ajax call
	**/
	var wppizzaCartRefreshedBefore = (function(functionArray, res) {
		if(functionArray.length>0){
			for(i=0;i<functionArray.length;i++){
				var func = new Function('term', 'return ' + functionArray[i] + '(term);');
				func(res);
			}
		}
	});

	/**
		run defined functions after
		each cart refresh/update
	**/
	var wppizzaCartRefreshed = (function(functionArray, res) {
		//skip if not using cache and undefined event (or it might run multiple times)
		if(!usingCache && typeof res['event'] === 'undefined'){
			return;
		}
		if(functionArray.length>0){
			for(i=0;i<functionArray.length;i++){
				var func = new Function('term', 'return ' + functionArray[i] + '(term);');
				func(res);
			}
		}
		wppizza_spinner('refresh');

		spinnerCount++;/* counter to not reinit spinner again on completed ajax requests */
	});
	/***********************************************
	*
	*	[using totals shortcode or minicart,load via js]
	*
	***********************************************/
	if ($('.'+pluginSlug+'-totals-container').length > 0){

		/* toggle shortcode type=totals cart visibility */
		var element_view_cart=$('.'+pluginSlug+'-totals-viewcart, .'+pluginSlug+'-totals-viewcart-button');
		var element_cart=$('.'+pluginSlug+'-totals-cart');

		if (element_view_cart.length > 0){

			/* show this particular cart (minicart or totals) on click of dashicon */
			$(document).on('click', '.'+pluginSlug+'-totals-container>.dashicons-cart, .'+pluginSlug+'-totals-viewcart-button', function(e){
				var self=$(this);
				self.closest('div').find('.'+pluginSlug+'-totals-cart').fadeToggle();
			});
			/* if clicking anywhere else, hide cart details*/
			$('html').click(function (event) {

				/* if clicked dashicon has it's cart shown skip, but DO hide carts of all other shortcodes (in case there's more than one) */
				var target_element=$(event.target);

				//skip if we select the spinner element or spinner up/downs in the minicart (provided it's enabled there in the first place)
				if(target_element.is('#'+pluginSlug+'-minicart .ui-spinner-input, #'+pluginSlug+'-minicart .ui-spinner-button, #'+pluginSlug+'-minicart .ui-icon')){
					return;
				}

  				if(target_element.is('.'+pluginSlug+'-totals-container>.dashicons-cart, .'+pluginSlug+'-totals-viewcart-button>input')){
  					var self_cart = target_element.closest('div').find('.'+pluginSlug+'-totals-cart');
  					if(self_cart.is(':visible')){
            			return;
  					}
				}

    			if(element_cart.is(':visible')){
        			element_cart.fadeOut();
    			}

			});
		}

		/*add small loading gif - but skip when loading page */
		wppizzaTotalsBefore = function(page_load){

			/* IE doesnt like setting defaults in function parameters , so we have to do the following */
	  		if(page_load === undefined) {
	      		page_load = false;
	   		}

			var element_totals_container=$('.'+pluginSlug+'-totals-container');
			if(page_load !== true){
				addLoadingDiv(element_totals_container, 'wppizza-loading-small');
			}
		};
		wppizzaTotalsBefore(true);

		wppizzaTotals = function(page_load){

			/* IE doesnt like setting defaults in function parameters , so we have to do the following */
	  		if(page_load === undefined) {
	      		page_load = false;
	   		}
			var element_totals_container=$('.'+pluginSlug+'-totals-container');
			var element_total_order=$('.'+pluginSlug+'-totals-order');
			var element_total_items=$('.'+pluginSlug+'-totals-items');
			var element_itemcount=$('.'+pluginSlug+'-totals-itemcount');
			var element_checkout_button=$('.'+pluginSlug+'-totals-checkout-button');
			var element_view_cart=$('.'+pluginSlug+'-totals-viewcart');
			var element_view_cart_button=$('.'+pluginSlug+'-totals-viewcart-button');
			var element_emptycart_button=$('.'+pluginSlug+'-totals-emptycart-button');
			var element_cart=$('.'+pluginSlug+'-totals-cart');
			var element_cart_items=$('.'+pluginSlug+'-totals-cart-items');
			var element_cart_summary=$('.'+pluginSlug+'-totals-cart-summary');

			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'gettotals'}, ext : wppizza.extend}, function(res) {
				/*
					adding hidden input for wppizzaCartJson
					- skip if there's a main cart on page
					- skip if there's a NO mini cart on page
					- skip if get_cart_json is not in the results set anyway

					if the cart is being emptied, the #wppizza-cart-json will always be removed
					by the set_cart_json function called
				*/
				if (mainCartElm.length <= 0 && miniCartElm.length > 0 ){


					if(typeof res.get_cart_json !== 'undefined'){

						/*
							if it already exists in the minicart replace , else prepend
						*/
						if($('#'+pluginSlug+'-minicart > #'+pluginSlug+'-cart-json').length > 0 ){

							$('#'+pluginSlug+'-minicart > #'+pluginSlug+'-cart-json').replaceWith(res.get_cart_json);

							/* update cart json with updated values */
							set_cart_json('minicart-update');

						}else{
							miniCartElm.prepend(res.get_cart_json);

							/* update cart json with initial values */
							set_cart_json('minicart-ini');
						}
					}

					else{
						/* remove hidden element and  */
						$('#'+pluginSlug+'-minicart > #'+pluginSlug+'-cart-json').empty().remove();
						/* update cart json */
						set_cart_json('minicart-empty');
					}

				}


				/* add /remove empty|not empty class to surrounding parent div */
				if (res.no_of_items > 0){
					element_totals_container.removeClass('wppizza-totals-no-items');
					element_totals_container.addClass('wppizza-totals-has-items');
				}else{
					element_totals_container.removeClass('wppizza-totals-has-items');
					element_totals_container.addClass('wppizza-totals-no-items');
				}

				/** add view cart dashicons if items in cart and view cart is enabled**/
				if (element_cart.length > 0 ){

					/* totals shortcode:  adding classes if not exist already */
					if(element_view_cart.length > 0){
						/* add if not exists already */
						if (!element_view_cart.hasClass('dashicons-cart')) {
							element_view_cart.addClass('dashicons dashicons-cart');
						}

						/* totals shortcode: simply hide icon if empty cart */
						if(res.cart_empty!=''){
							element_view_cart.hide();
						}else{
							element_view_cart.show();
						}
					}
				}

				/** add is open hiddden input - if open**/
				if(res.is_open){
					wppizza.shopOpen = true;
				}

				/** order total **/
				if (element_view_cart_button.length > 0){
					element_view_cart_button.html(res.view_cart_button);
				}

				/** order total **/
				if (element_total_order.length > 0){
					element_total_order.html(res.total);
				}
				/** items only total **/
				if (element_total_items.length > 0){
					element_total_items.html(res.total_price_items);
				}
				/** number of items **/
				if (element_itemcount.length > 0){
					element_itemcount.html(res.itemcount);
				}
				/** checkout button **/
				if (element_checkout_button.length > 0){
					element_checkout_button.html(res.checkout_button);
				}
				/** emptycart **/
				if (element_itemcount.length > 0){
					element_emptycart_button.html(res.emptycart_button);
				}

				/* set cart markup */
				if (element_cart.length > 0){

					/* loop through each totals as they might have different summary/itemised settings */
					$.each(element_cart,function(e,v){

						/* selected totals element */
						var selected_totals = $(this);

						/** get cart markup **/
						var cart_markup = '';

						/* itemised */
						if(selected_totals.hasClass('wppizza-totals-cart-items')){
							cart_markup += res.items;
						}

						/* summary */
						if(selected_totals.hasClass('wppizza-totals-cart-summary')){
							cart_markup += res.summary;
						}

						/* min order info - will actually be an empty str if min order has been reached */
						if(typeof res.minimum_order !=='undefined'){
							cart_markup += res.minimum_order;
						}

						/* set cart markup for this shortcode element */
						selected_totals.html(cart_markup);

						/* only reinit minicart/gettotals spinner after cart changes not on pageload (as that's already happened) */
						if(reinit_minicart_spinner){
							wppizza_spinner('minicart-cart_markup');
						}
					});
				}
				/* nothing to remove when loading page */
				if(page_load !== true){
					removeLoadingDiv(element_totals_container, 'wppizza-loading-small');
				}


			run_refresh_on_ajaxstop = false;

			/* once the initial page load was completed, we need to reinit the spinner when the (mini)cart changes */
			reinit_minicart_spinner = true;

			},'json');
		}
		wppizzaTotals(true);
	}


	/***********************************************
	*
	*	[minicart behaviour]
	*
	***********************************************/
	var miniCartElm=$('#'+pluginSlug+'-minicart');
	var mainCartElm=$('.'+pluginSlug+'-cart');
	if(miniCartElm.length>0){

		var wppizzaMiniCart = function(){

			/********************************
				add to id.class instead of body if set
			********************************/
			if(typeof wppizza.crt.mElm !=='undefined'){
				miniCartElm.prependTo(''+wppizza.crt.mElm+'');
			}
			var addElmPaddingTop=wppizza.crt.mCartPadTop;
    		/********************************
    			set padding (if set) to body or distinct element
    		********************************/
    		if(typeof wppizza.crt.mPadTop !=='undefined' && wppizza.crt.mPadTop>0){

    			var addElmPaddingTop=wppizza.crt.mPadTop;

				/**add padding to set elements**/
				if(typeof wppizza.crt.mPadElm !== 'undefined'){
					var elmToPad=$(''+wppizza.crt.mPadElm+'');
				}else{
    				var elmToPad=$('body');
				}
    			/* add css padding */
    			elmToPad.css({'padding-top': '+='+wppizza.crt.mPadTop+'px'});
    		}

			/*********************************
				if set to always show skip everything after
			*********************************/
			if(typeof wppizza.crt.mStatic!=='undefined'){
				return;
			}

			/*********************************
				scrolling behaviour if not static
			*********************************/
				/**current window**/
				var currentWindow = $(window);
				var miniCartIni=true;

				/**on initial load**/
		    	setTimeout(function(){
			    	wppizzaMiniCartDo(currentWindow, miniCartElm, mainCartElm, addElmPaddingTop, elmToPad);
		    		miniCartIni=false;
		    	},500);

		    	/**on scroll**/
		    	var showMiniCart;
				currentWindow.scroll(function () {
					/**only on subsequent scrolls not when page is already scrolled on load*/
					if(!miniCartIni){
						clearTimeout(showMiniCart);
						showMiniCart=setTimeout(function(){
							wppizzaMiniCartDo(currentWindow, miniCartElm, mainCartElm,addElmPaddingTop, elmToPad);
						},300);
					}
				});
		    	/**on resize**/
		    	currentWindow.resize(function() {
					/**only on subsequent scrolls not when page is already scrolled on load*/
					if(!miniCartIni){
						clearTimeout(showMiniCart);
						showMiniCart=setTimeout(function(){
							wppizzaMiniCartDo(currentWindow, miniCartElm, mainCartElm,addElmPaddingTop, elmToPad);
						},300);
					}
				});

		}
		wppizzaMiniCart();

		var wppizzaMiniCartDo = function(currentWindow, miniCartElm, mainCartElm, addElmPaddingTop, elmToPad){

				/*get width**/
		    	var docViewWidth = currentWindow.width();

		    	/*
		    		max browser width up to which we display the minicart,
		    		though that would be a bit silly to set if no maincart exists in the first place
		    	*/
		    	if(typeof wppizza.crt.mMaxWidth !=='undefined'){
		    		var docWidthLimit=wppizza.crt.mMaxWidth;
		    	}

				/**skip if wider than max width set or on oderpage**/
				if((typeof docWidthLimit !=='undefined' && docViewWidth>docWidthLimit) || typeof wppizza.isCheckout!=='undefined'){
					/*in case its still visible*/
					if(miniCartElm.is(':visible')){
						miniCartElm.fadeOut(250);
					}
					return;
				}


				/**
					only needed  if there is actually a main cart on page in the first place,
					else just set to not in view
				**/
				if(mainCartElm.length>0){
		    		var docViewTop = currentWindow.scrollTop();
		    		var docViewBottom = docViewTop + currentWindow.height();
					var elemTop = mainCartElm.offset().top;
					var elemBottom = elemTop + mainCartElm.height();
					var notInView = (elemBottom<=docViewTop || elemTop>=docViewBottom);
				}else{
					var notInView = true;
				}

				/*fade in minicart if needed**/
				if(notInView && miniCartElm.is(':hidden')){
					/*add padding if set **/
					if(typeof elmToPad !=='undefined'){
						elmToPad.animate({'padding-top': '+='+addElmPaddingTop+'px'},250);
					}
					miniCartElm.fadeIn(250);
				}

				if(!notInView && miniCartElm.is(':visible')){
					/*reset padding if required **/
					if(typeof elmToPad !=='undefined'){
						elmToPad.animate({'padding-top': '-='+addElmPaddingTop+'px'},250);
					}
					miniCartElm.fadeOut(250);
				}
			};
	}

	/****************************

		repurchase previous order
		[adding to cart]

	****************************/
	$(document).on('click', '.'+pluginSlug+'-reorder-purchase', function(e){
		e.preventDefault();
		e.stopPropagation();

		/*
			let's find out if we know yet if shop is open
			(as on pageload carts might get loaded by ajax)
		*/
		var is_open = checkShopOpen();
		if(!is_open){
			return;
		}

		/*
			only if open and cart on page
		*/
		if(hasCart){

			/*show loading*/
			addLoadingDiv();

			/**get the id**/
			var self=$(this);
			var selfId=self.attr('id');


			wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,e);/**run function before ajax cart update**/
			/******************************
				make the ajax request
			******************************/
			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'reorder', 'id':selfId, 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {
				/**error, invalid - display error code in console **/
				if(typeof response.invalid!=='undefined'){
					console.log(response.invalid);
				}
				/*replace cart contents*/
				if(typeof response.markup!=='undefined'){
					load_cart_set_height(response.markup);
				}
				run_refresh_on_ajaxstop = response.cart;
				forceAjaxStopRefresh = true;
				set_cart_json('update-cart');//update wppizzaCartJson from hidden input vars

			},'json');
		return;
		}

	});
	/****************************

		adding item
		to cart

	****************************/
	$(document).on('click', '.'+pluginSlug+'-add-to-cart, .'+pluginSlug+'-add-to-cart-select', function(e){
		e.preventDefault();
		e.stopPropagation();
		/*
			let's find out if we know yet if shop is open
			(as on pageload carts might get - slowly - loaded by ajax)
		*/
		var is_open = checkShopOpen();
		if(!is_open){
			return false;
		}

		/*
			only if open and cart on page
		*/
		if(hasCart){

			/*show loading*/
			addLoadingDiv();

			/**get the id**/
			var self=$(this);
			var selfId=self.attr('id');


			/**feedback on item add enabled ? - always skip if triggered from add_to_cart_button shortcode*/
			if(typeof wppizza.itm!=='undefined' && typeof wppizza.itm.fbatc!=='undefined' && !self.hasClass('wppizza-add-to-cart-btn')){
				var add_remove_class = true;
				var currentHtml=self.html();
				var target = self;
				/* when reordering, replace inner td */
				if(self.hasClass('wppizza-do-reorder')){
					target = self.closest('td');
					currentHtml=target.html();
					add_remove_class = false;
				}
				if(add_remove_class){
					self.removeClass('wppizza-add-to-cart');/* stop excessive double clicks */
				}

				self.fadeOut(100, function(){
					target.html( '<div class="wppizza-item-added-feedback">'+wppizza.itm.fbatc+'</div>' ).fadeIn(400).delay(wppizza.itm.fbatcms).fadeOut(400,function(){
						target.html(currentHtml).fadeIn(100);
						if(add_remove_class){
							self.addClass('wppizza-add-to-cart');/* re add class */
						}
					});
				});
			}

			/*
				if using shortcode "add to cart button" with select dropdown
			*/
			if(self.hasClass('wppizza-add-to-cart-select')){
				var selected_base = selfId.split('_').pop(-1);
				var selected_option = self.closest('span').find('select').val() ;
				/* set/override id */
				selfId = selected_base + '-' + selected_option;
			}

			wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,e);/**run function before ajax cart update**/
			/******************************
				make the ajax request
			******************************/
			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'add', 'id':selfId, 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {

				/**error, invalid - display error code in console **/
				if(typeof response.invalid!=='undefined'){
					console.log(response.invalid);
				}
				/*replace cart contents*/
				if(typeof response.markup!=='undefined'){
					load_cart_set_height(response.markup);
					wppizza.shopOpen = response.is_open;
					setTimeout(checkShopOpen, 10);/* timeout to finish html() */
				}
				/*replace order page contents (if there's an orderpage widget on the page)*/
				if(isCheckout && typeof response.page_markup!=='undefined'){
					$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
				}
				run_refresh_on_ajaxstop = response.cart;
				forceAjaxStopRefresh = true;
				set_cart_json('add');//update wppizzaCartJson from hidden input vars

			},'json');
		return;
		}
	});


	/****************************

		remove from cart = simple click on [x]
		modify cart using input number text field

	****************************/
	$(document).on('click', '.'+pluginSlug+'-remove-from-cart, .'+pluginSlug+'-delete-from-cart', function(e){//, .wppizza-cart-modify old, using spinner
		e.preventDefault();e.stopPropagation();

		/*show loading*/
		addLoadingDiv();

		/**get the id**/
		var self=$(this);
		var selected_id=self.attr('id');
		var key=selected_id.split('-').pop(-1);
		/** delete entire quantity of this item when spinner enabled and clicked on delete button */
		if(self.hasClass('wppizza-delete-from-cart')){
			var quantity = 0;
		}else{
			/* -1 to just remove one from existing*/
			var quantity = -1;
		}

		wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,e);/**run function before ajax cart update**/
		/******************************
			make the ajax request
		******************************/
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json', vars:{'type':'modify', 'id': selected_id, 'quantity': quantity, 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response){

			/**error, invalid - display error code in console **/
			if(typeof response.invalid!=='undefined'){
				console.log(response.invalid);
			}
			/*replace cart contents*/
			if(typeof response.cart_markup!=='undefined'){
				load_cart_set_height(response.cart_markup);
				wppizza.shopOpen = response.is_open;
				setTimeout(checkShopOpen, 10);/* timeout to finish html() */
			}
			/*replace order page contents*/
			if(isCheckout && typeof response.page_markup!=='undefined'){
				$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
				/* make sure to remove loading div */
				removeLoadingDiv();
			}

			run_refresh_on_ajaxstop = response.cart;
			forceAjaxStopRefresh = true;
			set_cart_json('modify');//update wppizzaCartJson from hidden input vars

		},'json');
	return;
	});

	/*
		only allow integers in cart increase/decrease - although it's set to type=number min=0
		it still allows for negatives ...
		trigger click on enter too
	*/
	$(document).on('keyup', '.'+pluginSlug+'-cart-mod, .'+pluginSlug+'-item-qupdate', function(e){
		this.value = this.value.replace(/[^0-9]/g,'');
	});
	/*
		get value on focus,
		to trigger click on blur too if value has changed
	*/
	var wppizza_current_item_count_val='';
	/**get current value for cart increase/decrease first**/
	$(document).on('focus', '.'+pluginSlug+'-cart-mod, .'+pluginSlug+'-item-qupdate', function(e){
		wppizza_current_item_count_val = this.value.replace(/[^0-9]/g,'');
	});
	/*
		execute/trigger on blur too if value is different
	*/
	$(document).on('blur', '.'+pluginSlug+'-cart-mod, .'+pluginSlug+'-item-qupdate', function(e){
		this.value = this.value.replace(/[^0-9]/g,'');
	});


	/***********************************************
	*
	*	[if we are trying to add to cart by clicking on the title
	*	but there's more than one size to choose from, display alert]
	*	[provided  there's a cart on page and we are open]
	***********************************************/
	/*more than one size->choose alert*/
	$(document).on('click', '.'+pluginSlug+'-trigger-choose', function(e){
		if (wppizza.shopOpen &&  hasCart){
			if(prettify_js_alerts){//using prettified alerts
				wppizzaPrettifyJsAlerts(wppizza.msg.choosesize, 'alert');
			}else{
				alert(wppizza.msg.choosesize);
			}
		}
	});
	/*only one size, trigger click*/
	$(document).on('click', '.'+pluginSlug+'-trigger-click', function(e){
		if (wppizza.shopOpen &&  hasCart){

			/*just loose wppizza-article- from id*/
			var ArticleId=this.id.split('-');
			ArticleId=ArticleId.splice(2);
			ArticleId = ArticleId.join('-');
			/**make target id*/
			target=$('#'+pluginSlug+'-'+ArticleId+'');
			/*trigger*/
			target.trigger('click');
		}
	});

	/****************************

		empty cart entirely

	****************************/
	$(document).on('click', '.'+pluginSlug+'-empty-cart-button', function(e){


		e.preventDefault();
		e.stopPropagation();
		/*show loading*/
		addLoadingDiv();

		wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,e);/**run function before ajax cart update**/
		/******************************
			make the ajax request
		******************************/
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'empty', 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {

			/**error, invalid - display error code in console **/
			if(typeof response.invalid!=='undefined'){
				console.log(response.invalid);
			}

			/*replace cart contents*/
			if(typeof response.cart_markup!=='undefined'){
				load_cart_set_height(response.cart_markup);
				wppizza.shopOpen = response.is_open;
				setTimeout(checkShopOpen, 10);/* timeout to finish html() */
			}
			/*replace order page contents*/
			if(isCheckout && typeof response.page_markup!=='undefined'){
				$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
			}

			run_refresh_on_ajaxstop = response.cart;
			forceAjaxStopRefresh = true;
			set_cart_json('empty');//update wppizzaCartJson from hidden input vars

		},'json');
	});


	/***********************************************
	*	[customer selects self pickup , session gets set via ajax
	*	reload page to reflect delivery charges....
	*	only relevant if there's a shoppingcart or orderpage on page]
	***********************************************/
	var set_pickup_elements_toggle = function(disable_elemnts, pickup_toggle){

		/* get all pickup checkboxes and radios in case there are more than one */
		var all_pickup_checkboxes=$('input[type="checkbox"][name="wppizza-order-pickup"]');//checkbox
		var all_pickup_toggles=$('.'+pluginSlug+'-toggle-pickup');//radio
		var all_delivery_toggles=$('.'+pluginSlug+'-toggle-delivery');//radio
		var all_pickup_toggle_labels = all_pickup_toggles.closest('label');
		var all_delivery_toggle_labels = all_delivery_toggles.closest('label');


		if(disable_elemnts === true){
			/* disable all checkboxes/radios */
			all_pickup_checkboxes.attr('disabled', true);/*disable checkbox to give ajax time to do things*/
			all_pickup_toggles.attr('disabled', true);/*disable radios to give ajax time to do things*/
			all_delivery_toggles.attr('disabled', true);/*disable radios to give ajax time to do things*/
		}

		if(disable_elemnts === false){
			/* enable all checkboxes/radios */
			all_pickup_checkboxes.attr('disabled', false);/*disable checkbox to give ajax time to do things*/
			all_pickup_toggles.attr('disabled', false);/*disable radios to give ajax time to do things*/
			all_delivery_toggles.attr('disabled', false);/*disable radios to give ajax time to do things*/
		}

		if(pickup_toggle === true){
			/* set to true what needs to be true*/
			all_pickup_checkboxes.prop('checked',true);
			all_pickup_toggles.prop('checked',true);
			all_delivery_toggles.prop('checked',false);
			all_pickup_toggle_labels.addClass('wppizza-pickup-toggle-selected');
			all_delivery_toggle_labels.removeClass('wppizza-pickup-toggle-selected');

		}
		if(pickup_toggle === false){
			all_pickup_checkboxes.prop('checked',false);
			all_pickup_toggles.prop('checked',false);
			all_delivery_toggles.prop('checked',true);
			all_pickup_toggle_labels.removeClass('wppizza-pickup-toggle-selected');
			all_delivery_toggle_labels.addClass('wppizza-pickup-toggle-selected');
		}

	}
	//var alert_run = false;
	$(document).on('change', '.'+pluginSlug+'-order-pickup', function(e){

		/*
			let's find out if we know yet if shop is open
			(as on pageload carts might get loaded by ajax)
			bypass if toggle has forced visibility to show even when closed
		*/
		if(!force_pickup_toggle){

			var is_open = checkShopOpen();


			if(!is_open){

				var elm = $(this);
				var elmType = elm.attr('type');
				var elmVal = elm.val();

				/*
					revert back to what it was
				*/
				if(elmType == 'checkbox'){
					$('.'+pluginSlug+'-order-pickup').each(function(){
						$(this).checked = ! $(this).checked
					});
				}

				if(elmType == 'radio'){

					$('.'+pluginSlug+'-order-pickup').each(function(){
						var thisRadioElm = $(this) ;
						/*
							for radios , we only need to re-check the "other one"
						*/
						if(thisRadioElm.val() != elmVal ){
							thisRadioElm.prop('checked', true);
						}

					});
				}


				return;
			}
		}

		if (hasCart || force_pickup_toggle){

			var self=$(this);
			/*
				serialize the data before we mess around with
				states of pickup toggles / checkbox
			*/
			var data = $('#'+pluginSlug+'-send-order').serialize();

			/**
				as the default for checkbox  can change to be pickup,
				instead of delivery find out whats what
			**/
			var default_is_pickup = (typeof wppizza.opt !=='undefined' && typeof wppizza.opt.puDef !== 'undefined') ? true : false;

			/** is radio toggle or checkbox */
			if (self.is(':radio')) {
				var is_pickup =(self.val()==1 ) ? true : false;

			}else{
				var is_pickup = (self.is(':checked')) ? true : false;
				/* overwrite if default is set to be pickup  */
				if(default_is_pickup){
					is_pickup = (self.is(':checked')) ? false : true;
				}
			}
			/**
				changed from default to show (right) alerts
			**/
			var changed_from_default = false ;
			if(default_is_pickup !== is_pickup){
				changed_from_default = true ;
			}

			/* disable all checkboxes/radios */
			set_pickup_elements_toggle(true, null);
			/*
				default is set to pickup or delivery,  checkbox is UN-checked , and labelled for  appropriat selection
			*/
			if(is_pickup){
				set_pickup_elements_toggle(null, true);
			}else{
				set_pickup_elements_toggle(null, false);
			}

			/*js alert if enabled and switching to opposite of default - only runs the first time*/
			if(typeof wppizza.opt!=='undefined' && typeof wppizza.opt.puAlrt!=='undefined'){
				/* only run if changing non default (i.e the opposite to the unchecked default) */
				if(changed_from_default == true){
					/*
						alert only
					*/
					if(wppizza.opt.puAlrt == 1){
						if(prettify_js_alerts){//using prettified alerts
							wppizzaPrettifyJsAlerts(wppizza.msg.pickup, 'alert');
						}else{
							alert(wppizza.msg.pickup);
						}
					}

					/*
						confirm
					*/
					if(wppizza.opt.puAlrt == 2){
						if(confirm(wppizza.msg.pickup)){
							//just continue
						}else{
							/* reset checkboxes / radios*/
							if((default_is_pickup && is_pickup) || (!default_is_pickup && is_pickup)){
								set_pickup_elements_toggle(null, false);
							}else{
								set_pickup_elements_toggle(null, true);
							}
							/* make all selectable again */
							set_pickup_elements_toggle(false, null);
						return;
						}
					}
				}
			}

			/**run function before ajax cart update**/
			wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,e);

			/*show loading*/
			addLoadingDiv();

			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'order-pickup', 'value': is_pickup, 'data': data, 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {

				/*
					conditionally still reloading page entirely
					on change of pickup to delivery (on checkout page)
					if not yet using updated plugins
				*/
				if(typeof wppizza['compat'] !== 'undefined'  && typeof wppizza['compat'].puDel !== 'undefined' ){
					if(isCheckout){
						window.location.reload(true);
						is_page_reload = true;
					return;
					}
				}

				/*
					distinctly set flag here
					to make sure to remove any loading divs
					after change
				*/
				is_page_reload = false;


				/*
					replace cart contents
				*/
				if(typeof response.cart_markup!=='undefined'){
					load_cart_set_height(response.cart_markup);
					wppizza.shopOpen = response.is_open;
					if(!force_pickup_toggle){
						setTimeout(checkShopOpen, 10);/* timeout to finish html() - not ideal but will do for now*/
					}
				}
				/*
					replace order page contents
				*/
				if(isCheckout && typeof response.page_markup!=='undefined'){
					$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
				}

				/*
					replace pickup choice contents
				*/
				$('.'+pluginSlug+'-orders-pickup-choice').replaceWith(response.cart_pickup_select);

				/*
					replace opening hours contents (as pickup/delivery tiimes may be filtered and therefore different)
				*/
				$('.'+pluginSlug+'-opening-hours').html(response.opening_hours);//
				
				/*
					make radios/checkboxes selectable again - irrelevant as html actually gets replaced
				*/
				run_refresh_on_ajaxstop = response.cart;

				forceAjaxStopRefresh = true;

				set_cart_json('pickup-toggle');//update wppizzaCartJson from hidden input vars

			},'json');
		}
	});


/**************************************************************************************************************************************************************************
*
*
*
*	[spinner]
*
*
*
**************************************************************************************************************************************************************************/
		/*******************************************************
		*	[order form , initialize spinner for increase/decrease of items if enabled]
		*	as function to allow re-initializing on ajax complete
		*******************************************************/
		var wppizza_spinner_blur_count = 0;
		var wppizza_spinner = function(action){

			/**
				only on wppizza checkout
				check isCheckout and wppizza.ofqc here too as it gets re-initialized
				on ajaxComplete !
			**/
			if(typeof wppizza.ofqc!=='undefined'){

				var spinnerElements = '';
				if(isCheckout){
					spinnerElements += '.'+pluginSlug+'-item-qupdate, ';
				}
				spinnerElements += '.'+pluginSlug+'-cart-mod';


  				var spinnerElm=$( spinnerElements );

       			/*
       				set min var
       			*/
       			spinnerElm.spinner({
       				min: 0
       			});

				/*
					capture original spinner value on focus
				*/
				var spinner_value;
				var spinner_update_value;
				var spinner_element;


				/*
					stop submitting if we are hitting enter
					after changing quantities
					and update checkout page instead if needed
				*/
				/*
					lets make sure to not re-initialize blur/keydown 2x
					spinner gets initialized from ajax complete.
					however, if no ajax (orderpage)  ajaxStart_count will be 0
				*/
				//if(action=='load' && wppizza.ajaxStart_count > 0)
				if(action=='load' && ajaxStartCount > 0){
					return false;
				}

				spinnerElm.focus(function(event) {
					event.preventDefault();
					wppizza_spinner_blur_count = 0;//reset count on focus
					spinner_value = $(this).val();
					spinner_element_id = $(this).attr('id').split('-').pop(-1);

					/*
						only enable mousewheel on focus
					*/
					$(this).on( 'DOMMouseScroll mousewheel', function ( event ) {
					/*restrict scrollwheel to be >=0*/
					  if( event.originalEvent.detail > 0 || event.originalEvent.wheelDelta < 0 ) { //alternative options for wheelData: wheelDeltaX & wheelDeltaY
					    // down
					    if (parseInt(this.value) > 0) {
					    	this.value = parseInt(this.value, 10) - 1;
					    }
					  } else {
					  	// up
					  	this.value = parseInt(this.value, 10) + 1;
					  }

					//prevent page from scrolling
					return false;
					});

					return false;
				});

				/*
					simply trigger blur event
					on enter keydown
				*/
				spinnerElm.keydown(function(event) {
					spinner_update_value = $(this).val();
					/* simply trigger blur event */
					if(event.which == 13 || event.which == 35){
						$(this).blur();
					return false;
					}
				});

				/*
					after changing quantities on blur
					update checkout page if needed
				*/
				spinnerElm.blur(function(event) {
					spinner_update_value = $(this).val();
					event.preventDefault();
					event.stopPropagation();


					if(wppizza_spinner_blur_count == 0 ){//make sure only the first blur will call the update function

						/*
							only bother with this if the vlues have actually changed
						*/
						if(spinner_value != spinner_update_value){
							/*
								make it behave like an onchange event
								by triggering change event
							*/
							$(this).change();
						}

						wppizza_spinner_blur_count++;
					}
				return false;
				});
			}
		};


		/*
			spinner change event
			(esentially this is forced from the blur event when the value has in fact changed)
		*/
		$(document).on('change', '.'+pluginSlug+'-cart-mod, .'+pluginSlug+'-item-qupdate', function(e){
			e.preventDefault();
			e.stopPropagation();

			var self = $(this);
			var selfId = self.attr('id');
			var spinner_id = selfId.split('-').pop(-1);
			var spinner_value = self.val();

			/*
				update the order
			*/
			wppizza_update_order(spinner_value, spinner_id);

		})

		/*
			if clicking on spinner arrows, immediately update cart by simply triggering the blur event on the input
			(as - by the nature of arrows - they increment by one anyway)
		*/
		$(document).on('click', '.'+pluginSlug+'-item-row > td > span > a.ui-spinner-up, .'+pluginSlug+'-item-row > td > span > a.ui-spinner-down', function(e){
			e.preventDefault();
			e.stopPropagation();
			$(this).closest('span').find('.ui-spinner-input').blur();
		});



/**************************************************************************************************************************************************************************
*
*
*
*	[Accordion]
*
*
*
**************************************************************************************************************************************************************************/
var initAccordion = function(){
	//console.log(wppizza['acc']);
	$( ""+wppizza['acc'].elm+"" ).accordion(wppizza['acc'].opt);
}
if(useAccordion){
	initAccordion();
}

/**************************************************************************************************************************************************************************
*
*
*
*	[submit order - including validation]
*
*
*
**************************************************************************************************************************************************************************/

		/*******************************************
		*	[validate order form - in function
		*	to allow re-initializing on ajax complete]
		*******************************************/
		var wppizza_validator = function(){

			/**
				only on wppizza checkout
				check isCheckout here too as it gets re-initialized
				on ajaxComplete !
			**/
			if(isCheckout){
				$('#'+send_order_form_id+'').validate({

					rules: wppizza.validate.rules,

			    	errorElement : 'div',

					errorClass:'wppizza-validation-error error',

					ignore: ':hidden, :disabled, .'+pluginSlug+'-ignore, .ignore',

					errorPlacement: function(error, element) {
						/* append to parent div */
						var parent = element.closest('div');
			     		error.appendTo(parent);

					},

			  		invalidHandler: function(form, validator) {
						if (!validator.numberOfInvalids()){
							return;
						}

						/**check if element is in view and scrollto if not*/
						var errorElem = $(validator.errorList[0].element);
			   			var currentWindow = $(window);

			   			var docViewTop = currentWindow.scrollTop();
			   			var docViewBottom = docViewTop + currentWindow.height();

					    var elemTop = errorElem.offset().top;
			   			var elemBottom = elemTop + errorElem.height();

				        var inView= ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));

				        /**scroll into view if needed*/
				        if(!inView){
				        	$('html, body').animate({
					            scrollTop: errorElem.offset().top-50
				        	}, 300);
				        }
					},

					submitHandler: function(form, event) {

						/*
							stop double clicks, add spinner - just for good measure.
							loading will cover all anyway
						*/
						var submit_button = $('#'+pluginSlug+'-ordernow');
						submit_button.attr('disabled', 'true');//.addClass('wppizza-ordernow-spinner');

						/*
							show loading
						*/
						addLoadingDiv();

						/*
							submit
						*/
						wppizza_submit_order();

					return false;/* dont submit form, we'll do that via wppizza_submit_order */
					}
				});
			};
		};
		/******************************
			initialize spinner on load
		******************************/
		wppizza_spinner('load');
		/******************************
		* update order page modules via ajax instead of reload
		*******************************/
		var wppizza_update_order = function(current_value, element_id, force_reload){

			/*
				forcing reload of page regardless making sure not to cache
				not really in use anywhere from what i can tell....
				but lets keep it for now
			*/
			if(typeof force_reload !== 'undefined' && force_reload === true){
				window.location.reload(true);
				is_page_reload = true;
			return;
			}
			// just stop if there's no element id to begin with
			if(typeof element_id === 'undefined'){
				return;
			}

			/* cover page with loading gif before running ajax*/
			addLoadingDiv();
			var element_totals_container=$('.'+pluginSlug+'-totals-container');
			addLoadingDiv(element_totals_container, 'wppizza-loading-small');

			/* when changing quantities on checkout page make sure we keep any newly entered/changed customer data */
			var data = '';
			if(isCheckout){
				data = $('#'+pluginSlug+'-send-order').serialize();
			}


			/** run function before ajax cart update **/
			wppizzaCartRefreshedBefore(wppizza.funcBeforeCartRefr,'update');

		     /**now send ajax to update**/
			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json', vars:{'type':'update-order', 'id': element_id, 'quantity': current_value, 'isCheckout': isCheckout, 'data': data}, ext : wppizza.extend}, function(response) {

				/**error, invalid - display error code in console **/
				if(typeof response.invalid!=='undefined'){
					console.log(response.invalid);
				}
				/*replace cart contents*/
				if(typeof response.cart_markup!=='undefined'){
					load_cart_set_height(response.cart_markup);
					wppizza.shopOpen = response.is_open;
					setTimeout(checkShopOpen, 10);/* timeout to finish html() */
				}
				/*replace page contents*/
				if(typeof response.page_markup!=='undefined'){
					$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
					/* make sure to remove loading div */
					removeLoadingDiv();
				}

				run_refresh_on_ajaxstop = response.cart;

				forceAjaxStopRefresh = true;

				set_cart_json('update-order');//update wppizzaCartJson from hidden input vars

				return;
			},'json');
		}



		/********************************************
		*
		*	orderpage only
		*
		********************************************/
		if(isCheckout){

			/******************************
				make sure page gets reloaded/changed
				to avoid possible "cannot find order by hash"
				when simply backpaging from payment gateway pages
				without having canceled or done anything further

				strangly enough, simply adding  a class to the 'body'
				seems to make this work without having to reload
				the order page
			******************************/
		    if($('body').hasClass('wppizza-checkout')){
		    }else{
		    	$('body').addClass('wppizza-checkout');
		    }
			/******************************
				initialize validator on load
			******************************/
			wppizza_validator();

			/******************************
			* validation - set error messages
			*******************************/
			jQuery.extend(jQuery.validator.messages, {
	    		required: wppizza.validate.error.required,
	    		email: wppizza.validate.error.email,
	    		decimal: wppizza.validate.error.decimal,
			})
			/******************************
				validation - add method - decimals (for tips)
			******************************/
			$.validator.methods.decimal = function (value, element) {
		    	return this.optional(element) || /^(?:\d+|\d{1,3}(?:[\s\.,]\d{3})+)(?:[\.,]\d+)?$/.test(value);
			}

			/*******************************
			*	[apply percentage tips  if enabled]
			*******************************/
			$(document).on('change', '#ctips_pc', function(e){

				/*
					take the total, minus current tips and calculate new tips
				*/
				var total_before_tips = 0;
				// current total
				if(typeof wppizzaCartJson['summary']['total'].total !== 'undefined'){
					total_before_tips = wppizzaCartJson['summary']['total'].total;
				}
				// deduct current tips
				if(typeof wppizzaCartJson['summary']['tips'].tips !== 'undefined' && total_before_tips > 0){
					total_before_tips -= wppizzaCartJson['summary']['tips'].tips;
				}

				// currency decimals - defaults to 2
				var decimals = (typeof wppizzaCartJson['currency'].decimals !== 'undefined') ?  wppizzaCartJson['currency'].decimals : 2 ;

				// calc tip based on total before tip, 2 decimals max
				var new_tip = ($(this).val() == '' || $(this).val() == 0 ) ? 0 : total_before_tips * ($(this).val() / 100 );
				new_tip = new_tip.toFixed(decimals);

				/*
					apply tip and trigger update
				*/
				$('#ctips').val(new_tip).trigger('blur');

			return;
			});

			/*******************************
			*	[validate tips/gratuities]
			* 	remove any utter nonsense on keyup first
			*******************************/
			$(document).on('keyup', '#ctips', function(e){
				/* ignore arrows/home/end/backspacing*/
				if(e.which==8 || (e.which>=35 && e.which<=40)){
					return;
				}

				var self = $(this);
				var value = self.val();
				validate = value.replace(/[^0-9\.,]+/g, '');
				//validate = parseFloat(validate)
				//num = validate.toFixed(2);
				self.val(validate);
			});
			/*******************************
			*	get current tip value set
			*******************************/
			var tips_input=$('#ctips');
			var tips_percent_select = $('#ctips_pc');
			var current_tips=tips_input.val();
			/*******************************
			*	get current tip value set on focus
			*******************************/
			$(document).on('focus', '#ctips', function(e){
				current_tips=$(this).val();
				/*
					if we focus on the tips input to enter the value manually
					unset the dropdown tips percentage
				*/
				if(tips_percent_select.length > 0 ){
					$('#ctips_pc').val('');
				}
			});
			/*******************************
			*	stop submitting form if we are hitting enter on tip field ...
			*******************************/
			$(document).on('keydown', '#ctips', function(e){
				if(event.which == 13 || event.which == 35){
					event.preventDefault();
					$(this).blur();
					//apply_tips(current_tips);
				return false;
				}
			});
			/*******************************
			*	.....and just apply tip by triggering "blur" button
			*******************************/
			$(document).on('blur', '#ctips', function(e){
				apply_tips(current_tips);
				return false;
			});
		}




		/*******************************
		*	apply tip set
		*******************************/
		var apply_tips = function(current_tips){

			var self = $(this);
			var entered_tips=$('#ctips').val();

			/**
				only update/refresh if the value has actually changed
			**/
			if( current_tips != entered_tips ){
				var data = $('#'+pluginSlug+'-send-order').serialize();
				/*stop double clicks*/
				self.attr('disabled', 'true');
				/*show loading*/
				addLoadingDiv();
				jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'addtips','data':data, 'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {

					/*replace cart contents*/
					if(typeof response.cart_markup!=='undefined'){
						load_cart_set_height(response.cart_markup);
						wppizza.shopOpen = response.is_open;
						setTimeout(checkShopOpen, 10);/* timeout to finish html() */
					}
					/*replace order page contents*/
					if(isCheckout && typeof response.page_markup!=='undefined'){
						$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
						/* make sure to remove loading div */
						removeLoadingDiv();
					}

					run_refresh_on_ajaxstop = response.cart;
					forceAjaxStopRefresh = true;
					set_cart_json('tips');//update wppizzaCartJson from hidden input vars

				},'json');
			}
		};

/**************************************************************************************************************************************************************************
*
*
*
*	[submit order - end validation]
*
*
*
****************************************************************************************************************************************************************************


	/******************************
	* ini selected wppizza_'+gateway_selected+'_init
	* js function if it was ddefined by currently selected
	* gateway -> on load
	*******************************/
	var wppizza_gateway_init=function(){

		/*
			get selected gateway
		*/
		var gateway_selected = wppizza_get_gateway_selected(false);
		if(gateway_selected == '' ){
			return;
		}
		/*
			check if gateway provides it's own init function
			to perhaps mount some fields or similar
		*/
		var gateway_function_name = 'wppizza_'+gateway_selected+'_init'/* the function name to look for */
		gateway_function_name = window[gateway_function_name];
		if(typeof gateway_function_name === 'function'){
			/*
				run defined ini function
			*/
			gateway_function_name();
		}
	}
	/*
		only run above init function on load of checkout page
		if no confirmation form is being used and only
		for some old/legacy gateway implementations
		(only affects non-redirects / inline gateways)
	*/
	if(gateway_compat_init){
		if(!use_confirmation_form){
			wppizza_gateway_init();
		}
	}



	/******************************
	* submit_order
	*******************************/
	var wppizza_submit_order=function(){
		/*
			dont use jQuery here to get the form to keep it
			consistent with "form", "form.id" etc returned by the validator
		*/
		var form = document.getElementById(send_order_form_id);

		/*
			already on confirmation page, override must_confirm
		*/
		var must_confirm = use_confirmation_form;
		if($('#'+form.id+'').hasClass('wppizza-order-confirmed')){
			must_confirm = false;
		}

		/*
			are we on checkout or confirmation page
		*/
		var is_confirmation_page = $('#'+form.id+'').hasClass('wppizza-order-confirmed') ? true : false


		/*
			serialized input data
		*/
		var data = $(form).serialize();


		/**************************************************************************************************
			Allow the execution to be interrupted AFTER the submit button was clicked
			by running functions defined in 'fnBeforeOrderSubmit'

			run any js functions that interjects before submitting order.
			if any of these do NOT return true, stop execution , reloading the page
			it is the called functions responsibility to display alerts, confirms etc
			as appropriate before page reload
		**************************************************************************************************/
		if(typeof wppizza.fnBeforeOrderSubmit !== 'undefined' && wppizza.fnBeforeOrderSubmit.length>0){

			/*
				set to true here to
				stop removing loading div early
			*/
			is_page_reload = true;

			/*
				by default, we always continue, unless some function
				set in fnBeforeOrderSubmit is set to interrupt the process
				by distinctly returning

				res.responseJSON['continue'] = false;

				(can be either ajax or non-ajax)
			*/
			var continue_execution = true;


			/*
				list of functions to execute (as they are most likely ajax calls)
				we have to wait for all of their results before deciding to either interrupt
				the checkout process or continue
			*/
			var promiseList = [];
			for(i=0; i < wppizza.fnBeforeOrderSubmit.length; i++){
				var fn = new Function('formid, data, must_confirm, is_confirmation_page', 'return ' + wppizza.fnBeforeOrderSubmit[i] + '(formid, data, must_confirm, is_confirmation_page);');
				var fnResult = fn(form.id, data, must_confirm, is_confirmation_page);
				/*
					add the function to the list pf promises
				*/
				promiseList.push(fnResult);

			}

			/*
				loop through the promises list and set continue_execution to false
				if *any* of the functions return continue = false in the responseJSON

				(if function is not using ajax, it still needs to return the same responseJSON.continue = false just like ajax calls)
			*/
			$.when.apply($, promiseList).then(function(results){
				$.each(promiseList,function(idx,res){


					// set continue_execution to false if defined as such
					if(typeof res.responseJSON['_wppizza_verify_on_submit'] !== 'undefined' && typeof res.responseJSON['_wppizza_verify_on_submit']['continue'] !== 'undefined' && res.responseJSON['_wppizza_verify_on_submit']['continue'] === false){

						/*
							set flags to not continue execution
							including elemnt id and alert mssage (if defined)
						*/
						continue_execution = res.responseJSON['_wppizza_verify_on_submit'];

						/*
							if even only one is false, stop the each loop
						*/
					return false;
					}
				});

			return continue_execution;

			}).done(function(order_continue){

				/*
					continue submitting the order
					(or not as the case may be if order_continue === false)
				*/
				on_before_order_continue(order_continue, data, form, must_confirm, is_confirmation_page);

			return;

			});

		/*
			always return here if we have defined fnBeforeOrderSubmit functions
		*/
		return;
		}

		/***********************************************************
			continue execution as normal if no
			fnBeforeOrderSubmit is set to interrupt the process
		***********************************************************/
		on_before_order_continue(true, data, form, must_confirm, is_confirmation_page);

	return;
	}

	/**************************************************************************************************************************************
	*	a helper to allow for fnVerifyOnOrderSubmit added in 3.13.5
	*	for a better way (we can check multiple results in *one* ajax call)
	*	to verify things before submitting
	*	@since 3.13.5+
	***************************************************************************************************************************************/
	var on_before_order_continue = function(order_continue, data, form, must_confirm, is_confirmation_page){


		/* fnVerifyOnOrderSubmit not defined, or order_continue == false already */
		if(typeof wppizza['fnVerifyOnOrderSubmit'] === 'undefined' || order_continue === false ){
			submit_order_continue(order_continue, data, form, must_confirm, is_confirmation_page);
		}
		/*
			3rd party plugins can check for whatever they need to check for , before an order gets submitted
			plugins should return bool "true" or an error message
		*/
		else{
			var param = {};
				param['post_data'] = data;
				//param['form'] = form;

			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'before_order_continue', 'param' : param }, ext : wppizza.extend}, function(response) {


				if(typeof response['errors'] !== 'undefined' ){

					/*
						aid debugging
					*/
					console.log('ERROR - OBOC');
					console.log(response);

					/*
						if there's a function defined, run that, passing on any args set , else just alert the error
					*/
					var showOrderContinueAlert = true;

					if(typeof response['errors']['func'] !== 'undefined' && response['errors']['func'] != '' ){

						/*
							define as function
						*/
						var fn_error_on_order_continue = window[response['errors']['func']];
						/*
							run function
						*/
						if(typeof fn_error_on_order_continue === 'function'){
							/*
								as there is a corresponding function
								we don't alert but let it do what it
								wants to do
							*/
							showOrderContinueAlert = false;

							/*
								the function called here really should exist!
							*/
							fn_error_on_order_continue(response['errors'].args);
						}
					}

					/*
						restore page visibility / re-enable submit button again
						(let's do this before we show the actual error alert)
					*/
					wppizzaRestoreOrder();

					/*
						just alert error if necessary
					*/
					if(showOrderContinueAlert){
						//set error message
						var alert_txt = response['errors'].message;
						/* prettify alert */
						if(prettify_js_alerts){
							alert_txt = '<center>'+alert_txt.replace(/[\r\n]+/g,'<br>')+'</center>';
							wppizzaPrettifyJsAlerts(alert_txt, 'interrupt before continue');
						}
						/* regular alert */
						else{
							alert(alert_txt);
						}
					}
				}else{
					submit_order_continue(order_continue, data, form, must_confirm, is_confirmation_page);
				}

			return;
			},'json');
		}

	return;
	}

	/**************************************************************************************************************************************
	*	get checkout form via ajax
	*	made globally available for other plugins to use
	*	@since 3.13+
	***************************************************************************************************************************************/
	wppizzaGetCheckout = function(gatewayChanged){

		/**
			show loading div
		**/
		addLoadingDiv();

		/**
			get form data too so we set
			session to not loose already entered info
		**/
		var data = $('#'+pluginSlug+'-send-order').serialize();

		/**
			get the now selected gateway value
			when changing gateways
		**/
		if(typeof gatewayChanged !== 'undefined'){
			/*
				id of gateway we have now changed to
			*/
			var gateway_selected = gatewayChanged.val();
			/*######################################################################
			#	the gateway now set to compare against when submitting the order
			#	to ensure the html is not being tampered with directly pretending
			#	an order was prepaid/cc when in fact it's COD (etc)
			######################################################################*/
			gateway_selected_init = wppizza_get_gateway_selected(gateway_selected);

		}
		/**
			if we are not chaging gateways specifically
			just use the gateway value we have set previously
		**/
		else{
			var gateway_selected = gateway_selected_init;
		}

		/************************************************************************************************
		 Run the AJAX request
		 even if we are not in fact chaging gateways by calling wppizzaGetCheckout() directly
		 we need to run this ajax call here to replace and recalculate what needs replacing and recalculating
		************************************************************************************************/
		jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'changegateway','data': data,'gateway_selected':gateway_selected, 'isCheckout': isCheckout, 'pageReload': gateway_compat_page_reload}, ext : wppizza.extend}, function(response) {

			/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
			#	GATEWAY LEGACY / COMPATIBILITY
			#	conditionally still reloading page entirely
			#	on change of gateway to delivery if not yet using updated inline payment gateways
			*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*/
			if(gateway_compat_page_reload){
				window.location.reload(true);
				is_page_reload = true;
			return;
			}
			/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
			#	END GATEWAY LEGACY / COMPATIBILITY
			*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*/


			/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*
			#
			# 	PAGE / FORM / SCRIPTS / CSS -> (RE-)LOAD BY AJAX
			#
			*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*\/*/


			/*######################################################################
			#
			#	replace ORDER FORM contents
			#
			######################################################################*/
			if(isCheckout && typeof response.page_markup!=='undefined'){
				$('.'+pluginSlug+'-order-wrap').replaceWith(response.page_markup);
			}

			/*######################################################################
			#
			#
			#	GATEWAY CSS
			#
			#
			######################################################################*/
			/*******************************************************
				remove any previously dynamically loaded css again
				when changing gateways
			*******************************************************/
			$.each(wppizzaGatewayCss,function(idx, handle){
				// remove stylesheet and reference by idx/handle
				// (they are the same here)
				$('#'+idx).remove();
				delete wppizzaGatewayCss[idx];
			});
			/*******************************************************
				load any gateway css
			*******************************************************/
			if(typeof response.gwAjax['styles'] !=='undefined'){
				$.each(response.gwAjax['styles'],function(handle,param){

					/*
						capture added css id in variable
						(to remove it again, if we change gateway)
					*/
					var cssId = handle+'-css';
					wppizzaGatewayCss[cssId] = cssId;

					/*
						construct css url with version no
					*/
					var cssUrl = param.src;
					if(param.ver !='' ){
						cssUrl += '?ver='+param.ver;
					}
					/*
						add stylesheet to Dom
					*/
					if(document.createStyleSheet) {
				    	try { document.createStyleSheet(cssUrl); } catch (e) { }
					}else {
				    	var cssElm;
				    	cssElm         = document.createElement('link');
				    	cssElm.rel     = 'stylesheet';
				    	cssElm.id      = cssId;
				    	cssElm.href    = cssUrl;
				    	cssElm.type    = 'text/css';
				    	cssElm.media   = 'all';
				    	/*
				    		unless specifically defined to be dependent of a specific element,
				    		we simply append to 'head'
				    	*/
				    	if(typeof param.deps !== 'undefined' && param.deps != '' ){
				    		$('#'+param.deps).after(cssElm);
				    	}else{
				    		$('head').append(cssElm);
				    	}
					}
				});
			}

			/*######################################################################
			#
			#	UPDATING MAIN WPPIZZA JS INLINE PARAMETERS
			#	COULD - IN THEORY - ALSO REPLACE/ADD SOME OTHER GATEWAY SPECIFIC
			#	INLINE PARAMETERS ONE DAY IF NECESSARY
			#
			######################################################################*/
			if(typeof response.gwAjax['inline'] !=='undefined'){
				$.each(response.gwAjax['inline'],function(handle,param){

					//inline script id to replace
					var jsInlineId = ''+handle+'-js-extra';
					var jsScriptId = ''+handle+'-js';

					//inline script data
					var jsInlineData = '/* <![CDATA[ */';
						jsInlineData += param.data;
						jsInlineData += '/* ]]> */';

					//inline script element
					   var jsInlineElm;
					   	jsInlineElm         = document.createElement('script');
					   	jsInlineElm.type    = 'text/javascript';
					   	jsInlineElm.id      = jsInlineId;
					   	jsInlineElm.innerText = jsInlineData;

					//replace current if exists,
					/* (this should always be the case as wppizza js really need to exist for anything to work at all !!) */
					if($('#'+jsInlineId+'').length > 0 ){
						$('#'+jsInlineId+'').replaceWith(jsInlineElm);
					}
					//else, add before wppizza src script
					/* (this should never actually need to be done !!!) */
					else{
						$('#'+jsScriptId+'').before(jsInlineElm);
					}
				});
			}


			/*######################################################################
			#
			#
			#	LOAD GATEWAY JS SOURCE FILES (i.e URLs)
			#	AND ASSOCIATED INLINE PARAMETERS (if any)
			#
			#
			######################################################################*/
				/*******************************************************
					remove any previously dynamically loaded js
					src or inline again when changing gateways
				*******************************************************/
				$.each(wppizzaGatewayJs,function(idx, handle){
					// remove stylesheet and reference by idx/handle
					// (they are the same here)
					$('#'+idx).remove();
					delete wppizzaGatewayJs[idx];
				});

				/*******************************************************
					load any gateway js
				*******************************************************/
				if(typeof response.gwAjax['scripts'] !=='undefined'){

					$.each(response.gwAjax['scripts'],function(handle,param){

						/******************
						#	JS SRC SCRIPT
						******************/
						if(typeof param.src !=='undefined' && param.src !=''){

							/* set id's / handle */
							var jsId = handle+'-js';
							wppizzaGatewayJs[jsId] = jsId;//(capture added js id in variable to remove it again, if we change gateway)

							/* check if theres some inline js data associated with the script and whether to add or replace*/
							var hasInlineData = (typeof param.data !=='undefined' && param.data !='' ) ? true : false ;
							var addInlineData = true;
							/*
								if there's also inline data associated with it
								add this first
							*/
							if(hasInlineData){

								//inline script id to add/replace
								var jsInlineId = ''+handle+'-js-extra';

								//inline script data
								var jsInlineData = '/* <![CDATA[ */';
									jsInlineData += param.data;
									jsInlineData += '/* ]]> */';

								//inline script element
								var jsInlineElm;
								   	jsInlineElm         = document.createElement('script');
								   	jsInlineElm.type    = 'text/javascript';
								   	jsInlineElm.id      = jsInlineId;
								   	jsInlineElm.innerText = jsInlineData;


								//replace current if exists and set flag, not to add it again
								if($('#'+jsInlineId+'').length > 0 ){
									/* replace existing */
									$('#'+jsInlineId+'').replaceWith(jsInlineElm);
									/* set flag it exists already */
									addInlineData = false;

								}
							}



							//construct js url with version no
							var jsUrl = param.src;
							if(typeof param.src !=='undefined' && param.ver !='' ){
								jsUrl += '?ver='+param.ver;
							}

							/*
								if for some reason it still exists, remove it here
								(some gateways are very particular about this)
								to make sure any init/load functions run
							*/
							var scriptExists = document.getElementById(jsId);
							if (scriptExists) {
								$('#'+jsId).remove();
							}

							//create element
					    	var jsElm;
					    		jsElm       = document.createElement('script');
						    	jsElm.type  = 'text/javascript';
								jsElm.src   = jsUrl;
								jsElm.id    = jsId;
								//defer if definded
								if(typeof param.strategy !=='undefined' && param.strategy == 'defer' ){
									jsElm.defer = true;
								}
								//async if defined
								else{
									jsElm.async = true;
								}

					    	/*
						    	unless specifically defined to be dependent of a specific element,
					    		or to footer somwhere we simply append to 'head'
					    	*/
				    		if(typeof param.deps === 'undefined' || param.deps == ''){

				    			//add inline data - if any and previous data was not already replaced - first
				    			if(hasInlineData && addInlineData){
				    				$('head').append(jsInlineElm);
				    			}
								//add the js script itself
								$('head').append(jsElm);

				    		}else{

				    			//add inline data - if any and previous data was not already replaced - first
				    			if(hasInlineData && addInlineData){
				    				$('#'+param.deps+'').after(jsInlineElm);//inline vars
				    				$('#'+jsInlineId).after(jsElm);//script, after inline
				    			}else{
				    				$('#'+param.deps).after(jsElm);
				    			}
				    		}

						}
					});
				}



				/*######################################################################
				#
				#	set shop open (or not) parameter - appending to
				#	the now updated / reinitialize MAIN WPPIZZA JS INLINE PARAMETERS
				#
				######################################################################*/
				wppizza.shopOpen = response.is_open;


				/*######################################################################
					distinctly set flag here to make sure to
					remove any loading divs after change
				######################################################################*/
				is_page_reload = false;


				/*######################################################################
					needed to reinit and gw scripts on ajaxstop (and  a couple of other refresh functions like spinner init etc) !!!
				######################################################################*/
				run_refresh_on_ajaxstop = response.cart;

				forceAjaxStopRefresh = true;
				/*######################################################################
					update wppizzaCartJson from hidden input vars as they will have changed when chaging gateways
					must run after page/cart markup is replaced
				######################################################################*/
				set_cart_json('change-gateway');

			return;

		},'json');

	return;
	}
	/**************************************************************************************************************************************
	* submit_order_continue
	* continue with order execution
	* if it was not interrupted by
	* some functions
	***************************************************************************************************************************************/
	var submit_order_continue = function(order_continue, data, form, must_confirm, is_confirmation_page){

		/*
			make sure to re-get the form wrapper here
			as the form might have been dynamically added and may
			not yet exists on page-load !!
		*/
		var target_replace = $('.'+pluginSlug+'-order-wrap');/* form element */


		/*****************************************************************
		#	[INTERRUPT EXECUTION]
		#
		#	stop right here if set that way by some 3rd party plugin
		#	and simply reload the page, but making sure we updated
		#	whatever was already entered by the customer
		*****************************************************************/
		/*
			just to be save, dont just simply check
			for !true (though should work just as well)
		*/
		if(typeof order_continue !== 'undefined' && typeof order_continue['continue'] !== 'undefined' && order_continue['continue'] === false ){


			/*
				is there an alert message defined ?
			*/
			var alert_txt = (typeof order_continue['alert'] !== 'undefined' && order_continue['alert'] != '' ) ? order_continue['alert'] :  false ;

			/*
				is there an element to scroll to ?
			*/
			var anchor_element = (typeof order_continue['element_id'] !== 'undefined' && order_continue['element_id'] != '' ) ? order_continue['element_id'] :  false ;


			/*
				Regular checkout page (not confirmation page)
				scroll to anchor if we are on the checkout page using prettified or standard alert
			*/
			if(!is_confirmation_page){


				/*
					alert, if any
				*/
				if(alert_txt !== false){

					/* prettify alert */
					if(prettify_js_alerts){
						alert_txt = '<center>'+alert_txt.replace(/[\r\n]+/g,'<br>')+'</center>';
						wppizzaPrettifyJsAlerts(alert_txt, 'invalid');
					}
					/* regular alert */
					else{
						alert(alert_txt);
					}

				}

				/*
					scroll to element
				*/
				if(anchor_element !== false){
					scroll_to_anchor(anchor_element)
				}

				/*
					remove any loading divs
					and re-enable button
				*/
				wppizzaRestoreOrder()

			return;
			}

			/*
				on confirmation page
				reload with anchor in hash after calling standard (blocking) alert
			*/
			if(is_confirmation_page){

				/*
					change class name of loading div to not
					remove it on ajax stop before actually reloading
				*/
				$('.'+pluginSlug+'-loading').addClass('wppizza-load-redirect').removeClass('wppizza-loading');

				/*
					alert, if any
					(blocking, so people can actually read the message before being redirected
				*/
				if(alert_txt !== false){
					//alert_txt = ''+alert_txt.replace(/[\r\n]+/gm,'\n')+'';
					alert(alert_txt);
				}

				/*
					add anchor element to url if set to make
					scroll_to_anchor() scroll to it when the page we are redirecting
					to , i.e the order page , gets loaded
					(always remove any previously existing anchors)
				*/
				var url = location.href.replace(location.hash,'')
				if(anchor_element !== false){
					url +='#'+ anchor_element;
				}

				window.location.href = url;
				window.location.reload(true);//must force reload
			return;
			}

		return;
		}


		/*****************************************************************
		#
		#	[CONTINUE EXECUTION]
		#
		*****************************************************************/

		/*******************************
			confirmation page enabled,
			show that first
		********************************/
		if(must_confirm){

			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'confirmorder','data':data,'isCheckout': isCheckout}, ext : wppizza.extend}, function(response) {

				/**
					replace the whole div with the confirmation page form
					and if selected gateway has an init function, run it
				**/
				target_replace.replaceWith(response.markup).promise().done(function(elem) {

					set_cart_json('confirmation_page');

					wppizza_gateway_init();
				});

				//scroll to a bit higher than top of form
				var form_top = $('#'+pluginSlug+'-send-order').offset().top;
				$('html, body').animate({ scrollTop: form_top - 100 }, 300);

				run_refresh_on_ajaxstop = false;

				removeLoadingDiv();

			},'json');
		return;
		}



		/*********************************
			confirmation page not enabled
			or already on confirmation page
		**********************************/
		if(!must_confirm){

			/*
				get selected gateway
			*/
			var gateway_selected = wppizza_get_gateway_selected(false);

			/**
				just in case something went wrong and nothing was selected
			**/
			if(gateway_selected == '' ){
				console.log('No payment method selected');
				return false;
			}

			/**
				if payment method that was set on page load was altered directly in the html
				setting another radio for example to be checked bypassing page reload - i.e hacking around somewhere
				to execute COD payments pretending to be cc/prepaid for example, stop right here with console log notice

			**/
			if(gateway_selected != gateway_selected_init ){
				console.log('Payment method mismatch ['+gateway_selected+'#'+gateway_selected_init+']');
				return false;
			}

			/*
				run gateways own payment function

				check if gateway provides it's own function (mainly for overlays / inline payments)
				if so, submit order will be halted and script used instead
				should result in redirect to thank you page
			*/
			var gateway_function_name = 'wppizza_'+gateway_selected+'_payment'/* the function name to look for */
			gateway_function_name = window[gateway_function_name];
			if(typeof gateway_function_name === 'function'){
				/*
					firefox does - for some reason - return event as undefined, so lets account for that
					(not even sure stopPropagation is needed here anyway, but lets keep it for now)
				*/
				if(typeof event !== 'undefined'){
					event.stopPropagation();
				}

				is_page_reload = true;
				gateway_function_name(form.id, data, event );

			return false;
			}


			/*
				run ajax
			*/
			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'submitorder','gateway_selected':gateway_selected, 'wppizza_data':data}, ext : wppizza.extend}, function(response){

				/** if we have errors , display those , replacing page */
				/** if we have no errors , redirect to thank you / results */
				if(typeof response.error!=='undefined'){
				var error_id = [] ;
				var error_message = [] ;
					/* set errors to implode */
					$.each(response.error,function(e,v){
						error_id[e] = v.error_id;
						error_message[e] = v.error_message;
					});

					/* set error div*/
					var error_info = '<div id="wppizza-order-error" class="wppizza-error">ERROR: '+error_id.join('|')+'<br /><br /></div>';
						error_info += '<div id="wppizza-order-error-details" class="wppizza-error-details"><ul><li>'+error_message.join('</li><li>')+'</li></ul></div>';

					/* replace with error */
					target_replace.replaceWith(error_info);

					/* scroll to top of page */
					$('html,body').animate({scrollTop:0},300);

					/* remove loading div */
					removeLoadingDiv();

				return;
				}
				/**
					show email output - if enabled via DEV constant
				**/
				if(typeof response.output!=='undefined'){

					console.log(response);

					/* replace with output */
					target_replace.replaceWith(response.output);
				return;
				}

				/**
					if we have no errors and selected gateway
					needs to redirect, do this here now
				**/
				if(typeof response.gateway !=='undefined'){
					/*  if posting form*/
					if(typeof response.gateway.form !=='undefined'){
						is_page_reload = true;/* do not clear loading div */
						$(response.gateway.form).appendTo('body').submit();
					return;
					}
					/* redirect by url */
					if(typeof response.gateway.redirect !=='undefined'){
						is_page_reload = true;/* do not clear loading div */
						window.location.href=response.gateway.redirect;
					return;
					}
				return;
				}

				/**
					if we have no errors , and no previous redirect
					redirect to thank you (COD)
				**/
				if(typeof response.redirect_url!=='undefined'){
					is_page_reload = true;/* do not clear loading div */
					window.location.href=response.redirect_url;
					return;
				}

				run_refresh_on_ajaxstop = false;

			},'json');

		}
	return;
	}

	/******************************************************
	*	[changing gateways, re-calculate handling charges
	*	and reload - if necessary]
	******************************************************/
	$(document).on('change', 'input[name="wppizza_gateway_selected"]:radio, select[name="wppizza_gateway_selected"]', function(e){
		var self = $(this);
		//do not try to load standard checkout via ajax if using a custom checkout implementation
		if(self.hasClass('wppizza-custom-checkout')){
		return;
		}
		wppizzaGetCheckout($(this));
	});


	/***********************************************
	*
	*	[order form: scroll to anchor if there is one]
	*
	***********************************************/
	var scroll_to_anchor = function(elm_id){
		if(isCheckout){
			/*
				on page load take from page url hass as id
				if called directly, use elm_id (must be an id)
			*/
			var anchor_elm = !elm_id ? window.location.hash.substring(1) : elm_id;

			if(anchor_elm !='' && $('#'+anchor_elm).length > 0){
				$('html, body').animate(
					{scrollTop: $('#'+anchor_elm).offset().top-100},
				1000);
			}
		}
	}
	scroll_to_anchor(false);
	/***********************************************
	*
	*	[order form: toggle info "create account" / "continue as guest"]
	*
	***********************************************/
	$(document).on('change', '.'+pluginSlug+'_account', function(e){
		$('#'+pluginSlug+'-user-register-info' ).toggle(200);
	});

	/*******************************************
	*	[user login (order form | orderhistory): show login input fields]
	*******************************************/
	$(document).on('click', '.'+pluginSlug+'-login-show, .'+pluginSlug+'-login-cancel', function(e){
		e.preventDefault(); e.stopPropagation();
		$('.'+pluginSlug+'-login-fieldset').slideToggle(300);
		$('.'+pluginSlug+'-login-option>a').toggle();
	});
	/*******************************************
	*	[user login (order form | orderhistory): validation login]
	*******************************************/
	if(hasLoginForm){
		$('.'+pluginSlug+'-login-form > form ').validate({
			rules: {
				log: {
					required: true
			    },
			   	pwd: {
					required: true
				},
			},
			errorElement : 'div',
			errorClass:'wppizza-validation-error error',
			errorPlacement: function(error, element) {
			/* append to parent div */
			var parent = element.closest('p');
	    		error.appendTo(parent);
			}
		});
	};
	/*******************************************
	*	[user login (order form | orderhistory): logging in or error]
	*******************************************/
	$(document).on('click', '.'+pluginSlug+'-login-form #wp-submit', function(e){
	    e.preventDefault(); e.stopPropagation();
	    var self = $(this);
	    var form = self.closest('form');
	    if (form.valid()) {


			var data = form.serialize();
			var setWidth=self.css('width');
			var setHeight=self.css('height');
			var info_div = $('.'+pluginSlug+'-login-info');
			self.attr('disabled', 'true').val('').addClass('wppizza-wait').css({'width':setWidth,'height':setHeight});
			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'user-login','data':data}, ext : wppizza.extend}, function(response) {

				/* successful login, -> reload */
				if(typeof response.error ==='undefined'){
					/* just reload after login */
					window.location.reload(true);
					is_page_reload = true;/* do not clear loading div */
					return;
				}

				/* error login, -> show info for a few seconds */
				if(typeof response.error !=='undefined'){
					/* show error, fadeout, remove again */
					info_div.append(''+response.error+'').slideDown(250).delay(3500).slideUp(1000,function(){info_div.empty()});
					/* reenable button */
					self.removeAttr('disabled').val(response.button_value).removeClass('wppizza-wait');
				return;
				}

				run_refresh_on_ajaxstop = false;

			},'json');

	    }
	});

	/*******************************************
	*	[USER - orderhistory: toggle transaction/order details]
	*******************************************/
	$(document).on('click', '.'+pluginSlug+'-transaction-details-orderhistory > legend', function(e){
		var self = $(this);
		var order_details = self.closest('fieldset').find('.'+pluginSlug+'-order-details');
		var transaction_details = self.closest('fieldset').find('.'+pluginSlug+'-transaction-details');

		if(order_details.is(':visible')){
			order_details.hide();
			transaction_details.fadeIn();
		}else{
			transaction_details.hide();
			order_details.fadeIn();
		}
	});

	/***********************************************
	*
	*	[navigation widget as dropdown - redirect on change]
	*
	***********************************************/
	$(document).on('change', '.'+pluginSlug+'-dd-categories > select', function(e){
		e.preventDefault(); e.stopPropagation();
		var url = $(this).val();
		if(url <=0 ){/* placeholder */
			return;
		}
		window.location.href = url;/*make sure page gets loaded without confirm*/
		return;
	});


/**********************************************************************************
#
#	SHOP STATUS CHANGE
#	DO THINGS WHENTHE SHOP CHANGES FROM BEING OPEN TO BEING CLOSED OR VICE VERSA
#	@since 3.13.2
#
***********************************************************************************/


	/**************************************************************************
	* get and pass on parameters / values to some defined fuctions
	* either on page load or directly from first ajax call when loading
	* the cart if caching function will get called whenever the status
	* of the shop changes (open -> close and vice versa)
	**************************************************************************/
	var shop_status_init = true;//bool true on pageload
	var shop_status_interval = null;//set interval to update status
	var shop_status = function(results, debug){

		/*
			initially turned off and will only run
			if a plugin adds some function that needs to
			be called by adding it to the 'funcSetSts' array
			with wppizza_filter_js_set_status_functions
		*/
		if(typeof wppizza['funcSetSts'] === 'undefined'){
			return;
		}

		/*
			if not caching pages, run ajax on load.
			also called when status changes from open to closed
			(or reverse) at end of countdown to get current values
		*/
		if(results === false){

			jQuery.post(wppizza.ajaxurl , {action :'wppizza_json',vars:{'type':'loadcart', 'isCheckout': isCheckout}, ext : wppizza.extend}, function(results){
				/*
					seconds until status change
				*/
				var seconds_remaining = results['shop_status']['next']['remaining']['sec'];

				/*
					run that functions defined when status changes
				*/
				exec_shop_status(shop_status_init, results, seconds_remaining, 'A=>cache OFF');

				 shop_status_init = false;

			},'json');

		}
		/*
			if pages caching enabled function will be run when
			cart loading by ajax with the relevant parameters
			returned already
		*/
		else{
			/*
				seconds until status change
			*/
			var seconds_remaining = results['shop_status']['next']['remaining']['sec'];
			/*
				run that functions defined when status changes
			*/
			exec_shop_status(shop_status_init, results, seconds_remaining, 'B=>cache on');

				 shop_status_init = false;


		}
	}
	/**********************************************************
		if we are not caching pages, run "shop_status" on page load
		as opposed to it being triggered when cart is being loaded by ajax
	**********************************************************/
	if(!usingCache){
		shop_status(false, 'debug1');
	}

	/*********************************************************
		run attached functions on shop status set as required
	*********************************************************/
	var exec_shop_status = function(shop_status_init, results, seconds_remaining, debug){

		/*
			run on page load
		*/
		on_status_set(shop_status_init, results, seconds_remaining);

		/*
			1 second interval countdown to status change
			based on seconds_remaining until that event
		*/
		shop_status_interval = setInterval(function () {
      		/*
      			countdown to zero
      			to next status change
      		*/
      		seconds_remaining--;

      		/*
      			run any fn's and
      			reinit countdown at zero
      		*/
      		if(seconds_remaining == 0){

				/*
					clear the interval
				*/
      			clearInterval(shop_status_interval);

				/*
					force update cart when shop status changes (at end of countdown)
					but allow for a second grace for the moment
				*/
				setTimeout(function(){

					//on checkout
					if(typeof wppizza['isCheckout'] !== 'undefined'){
						wppizzaGetCheckout();
					}
					//on NON  checkout where there's a cart
					else{
						wppizzaUpdateCart();
					}


				},1000);


				/*
					give it 3 secs before re-running itself
					it doesnt need to be that manic
				*/
      			setTimeout(function(){
					shop_status(false, 'debug2');
      			},3000);


      		return;
      		}

      }, 1000);
	}

	/**************************************************************************************************************************
	* run some functions when status (open/close) is set or changes
	* @param - init will be true or false (tru on page load, false on update/status change
	* @param - json object of values
	* @param - integer seconds until next closing/opening
	* Note: depending on what needs to be executed , one might not want to execute any defined functions if
	* is_init == true and seconds_remaining <=3 (or so) as the defined function(s) will run again when the
	* "seconds_remaining" reaches 0 !! (just a suggestion)
	* i.e fn(a,b,c){if (c <= 3){return;} else{//do stuff }}; or some such
	***************************************************************************************************************************/
	var on_status_set = function(is_init, values, seconds_remaining){

		/*
			check if we have any functions defined that need to run
			and if so , do
		*/
		if(typeof wppizza.funcSetSts === 'undefined'){
			return;
		}
		var functionArray = wppizza.funcSetSts;
		if(functionArray.length>0){
			for(i=0;i<functionArray.length;i++){
				var func = new Function('is_init, values, seconds_remaining', 'return ' + functionArray[i] + '(is_init, values, seconds_remaining);');
				//run the function
				func(is_init, values, seconds_remaining);
			}
		}
	return false;
	}

});