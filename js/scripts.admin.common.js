/**admin only: loaded when post_type == wppizza **/
jQuery(document).ready(function($){
	/******************************
	*	[toggle contextual help on help icon click]
	******************************/
	$(document).on('click', '.wppizza-show-admin-help', function(e){
		$('#contextual-help-link').trigger('click');
	});

  	/*******************************
	*	[chosen.js - make sure it exists before calling]
	*******************************/
	if(typeof $.fn.chosen === 'function') {

		/* edit post (wppizza menu items) */
		// additives meta $('select#wppizza-meta-additives').chosen({minWidth: '125px', width: 'auto', 'white-space': 'nowrap'});
		/* settings */
		$('#wppizza-settings select').chosen({});
		/* order settings */
		$('#wppizza-order_settings select').chosen({});
		/* order form */
		$('#wppizza-order_form select').chosen({});
		/* openingtimes */
		$('#wppizza-openingtimes select').chosen({});
		/* gateways */
		$('#wppizza-gateways select').chosen({});
		$('#wppizza-gateways .chosen-drop, #wppizza-gateways .chosen-single').css({minWidth: '125px', width: 'auto', 'white-space': 'nowrap'});
		$('#wppizza-gateways .chosen-container').css({minWidth: '125px', width: 'auto', 'white-space': 'nowrap'});
		/* layout */
		$('#wppizza-layout select').chosen({});
		/* templates */
		$('#wppizza-templates select').chosen({});
		/* reports */
		$('#wppizza-reports select').chosen({});
		/* orderhistory - tables only , not popUps*/
		$('#wppizza-orderhistory #wppizza_orderhistory_polling select').chosen({});
		$('#wppizza-orderhistory .wppizza-orderhistory-column-details select').chosen({minWidth: '100%', width: 'auto'});
		/* tools */
		$('#wppizza-tools select').chosen({});
	}

	/******************************
	*	[meta boxes pricetier select - onchange]
	******************************/
	$(document).on('change', '.wppizza_pricetier_select', function(e){

		var post_box_inner = $('#wppizza .inside');

		/* add loading div */
		post_box_inner.prepend('<div class="wppizza-load"></div>');

		var self=$(this);
		var selId=self.val();
		var fieldArray=self.attr('name').replace("[sizes]","");
		var classId=self.attr('class').split(" ").pop(-1);

		jQuery.post(ajaxurl , {action :'wppizza_admin_menu_items_ajax',vars:{'field':'sizeschanged','id':selId,'inpname':fieldArray,'classId':classId}}, function(response) {
			$.each(response.element,function(e,v){
				if(typeof response.inp[e]!=='undefined'){
					var findElementById=self.closest('#wppizza').find(v);
					if(findElementById.length>0){
						self.closest('#wppizza').find(v).empty().html(response.inp[e]);
					}else{
						self.closest('.wppizza_option').find(v).empty().html(response.inp[e]);
					}
				}
			});

			/* remove loading div when done*/
			$('.wppizza-load').remove();
		},'json').fail(function(jqXHR, textStatus, errorThrown) {alert("error : " + errorThrown);});
	});

});