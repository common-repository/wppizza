/******************************************************************************************************************
	to customize prettyPhoto() (theme etc) , copy this file as wppizza.prettyPhoto.custom.js  to your theme directory 
	and edit as required see: http://www.no-margin-for-errors.com/projects/prettyphoto-jquery-lightbox-clone/documentation for options
******************************************************************************************************************/
jQuery(document).ready(function($){
	/* selected pretty photo style */
	var ppStyle = wppizza.pp.s;
	$("a[rel^='wpzpp']").prettyPhoto({theme:ppStyle,social_tools:false,show_title:false});
});