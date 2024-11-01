jQuery(document).ready(function($){
	/******************************
	*	[additives/allergens/foodtype - add new]
	******************************/
	$(document).on('click', '#wppizza_add_foodtype, #wppizza_add_allergens, #wppizza_add_additives', function(e){
		e.preventDefault();
		var self=$(this);
		self.prop( "disabled", true );/* disable add button */
		var item = self.attr('id').split('_').pop(-1);
		var allKeys=$('#wppizza_'+item+'_options .wppizza-getkey');
		if(allKeys.length>0){
			var setKeys=allKeys.serializeArray();
		}else{
			var setKeys='';
		}
		jQuery.post(ajaxurl , {action :'wppizza_admin_additives_ajax',vars:{'field':item, 'setKeys': setKeys }}, function(response) {		
			$('#wppizza_'+item+'_options').append(response);
			self.prop( "disabled", false  );
		},'html').fail(function(jqXHR, textStatus, errorThrown) {alert("error : " + errorThrown);});
	});
	/*****************************
	*	[remove an option]
	*****************************/
	$(document).on('click', '.wppizza-delete', function(e){
		e.preventDefault();
		$(this).closest('div').empty().remove();
	});
});