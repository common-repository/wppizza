wppizza-gateway-dummypayhpp.zip

a skeleton gateway you can use for your own gateway development for 
payment processors that use  own hosted payment pages (i.e a redirect)


unzip the file somewhere and read the comments in the files

generally speaking :
	
	replace all Dummypayhpp, DUMMYPAYHPP, , dummypayhpp etc with an appropriate unique identifier for your gateway (including directory and file names). 
	
	So if your gateway is called "Xyz - We Pay U" use perhaps somethig like "Xyzwepayu", "XYZWEPAYU" and "xyzwepayu" ($gatewayName however can be set to the actual name i.e "Xyz - We Pay U" )
	
	you must edit all files (some more than others). 
	
	please read the comments and see the examples in *each* file
	
	make sure to also update and rename the language files in the lang/ directory
	(if using poedit for exmple, rename the .po file according to your textdomain set. open the file in poedit, click on update and save. Subsequently you can delete the old .mo file)
	
	
have fun	
	
	
	