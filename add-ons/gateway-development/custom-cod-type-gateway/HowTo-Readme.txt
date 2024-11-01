========== wppizza-gateway-mycod.zip =================

An example of a COD type gateway you can use as a base for your own gateway development to add an additional COD gateway to WPPizza. 

Example: Assuming you want to change the slug / name / paths etc from "mycod" to "mobilepayments".

Make sure this 'slug' / 'Name' is unique and not already in use. 
I.e you must not use 'cod' or 'ccod' or 'stripe' or 'paypal' etc, or any other WPPizza gateways you may have already installed. 
(Ideally you would even add a unique prefix to this slug. For example: use "ollys_mobilepayments" instead of just "mobilepayments" to be able to avoid any potential issues in the future )


Howto:

a)  Unzip wppizza-gateway-mycod.zip into your Wordpress plugins directory.

You should end up with structure like this:
	[WP Installation]/wp-content/plugins/wppizza-gateway-mycod/
	[WP Installation]/wp-content/plugins/wppizza-gateway-mycod/readme.txt
	[WP Installation]/wp-content/plugins/wppizza-gateway-mycod/uninstall.php
	[WP Installation]/wp-content/plugins/wppizza-gateway-mycod/wppizza-gateway-mycod.php

Alternatively, unzip it elsewhere, do the adjustments as mentioned above and then copy it to the plugins directory when you are done.

b) Rename the file and directory structure from the above to the following 
	[WP Installation]/wp-content/plugins/wppizza-gateway-mobilepayments/
	[WP Installation]/wp-content/plugins/wppizza-gateway-mobilepayments/readme.txt
	[WP Installation]/wp-content/plugins/wppizza-gateway-mobilepayments/uninstall.php
	[WP Installation]/wp-content/plugins/wppizza-gateway-mobilepayments/wppizza-gateway-mobilepayments.php

c) Open readme.txt in your favourite editor and change the information therein as appropriate (it should be quite obvious as to what might need adjusting). 
Save the file.

d) Open uninstall.php and change 
	$optionSuffix = 'mycod'; 
		to 
	$optionSuffix = 'mobilepayments'; 
Save the file.

e) Open (the now renamed) wppizza-gateway-mobilepayments.php  

	Line 3: Adjust Plugin Name as required (i.e change "Plugin Name: WPPizza Gateway - MyCod" to "Plugin Name: WPPizza Gateway - Mobile Payments" for example) 

	Line 4: Adjust Description as required 

	Lines 5-10: (Author, Plugin URI, etc ) Feel free to change this as you require  

	Line 32: change 
		register_uninstall_hook( __FILE__, 'wppizza_gateway_mycod_uninstall');
		to
		register_uninstall_hook( __FILE__, 'wppizza_gateway_mobilepayments_uninstall');

	Line 40: change 
		function wppizza_register_wppizza_gateway_mycod( $gateways ) {
		to
		function wppizza_register_wppizza_gateway_mobilepayments( $gateways ) {

	Line 42: change 
		$gateways[] = 'WPPIZZA_GATEWAY_MYCOD';
		to
		$gateways[] = 'WPPIZZA_GATEWAY_MOBILEPAYMENTS';

	Line 46: change 
		add_filter( 'wppizza_register_gateways', 'wppizza_register_gateway_mycod' );
		to
		add_filter( 'wppizza_register_gateways', 'wppizza_register_gateway_mobilepayments' );

	Line 73: Adjust gatewayName as required (i.e change 'My COD Gateway' to something else if you wish)
	
	Line 86: Adjust gatewayDescription as required (i.e change 'Some more information about this gateway' to something else if you wish or simply set it to an empty string )
Save the file.


f) Now activate the plugin in your Wordpress installation

g) Go to WPPizza->Gateways and adjust the options as required




