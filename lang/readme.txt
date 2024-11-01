

If you want to contribute to the plugin by adding your own translation or improving/completing existing ones please note the following

== Preamble: == 

every translation has three parts 

a) 
Pot-File: wppizza.pot
The translations that are added to WPPizza-> Localisation (and in a few other places) which are used in the frontend of the plugin.
When the plugin is first installed, these translations - if available and depending on your language set in "Wordpress -> Settings -> General" - will be inserted as default frontend text/strings.
If english (GB) is set, the file(s) responsible are [plugin-path]/wppizza/lang/wppizza-en_GB.po and [plugin-path]/wppizza/lang/wppizza-en_GB.mo 
If italian is set it's [plugin-path]/wppizza/lang/wppizza-en_GB.po and [plugin-path]/wppizza/lang/wppizza-it_IT.mo
etc
If no corresponsing files exist, plain english will be installed. (read directly from the plugin files themselves).
Note: After installation, these files will not do anything anymore as strings can then be translated and customised as appropriate in the plugin itself.
There are really only used as basic frontend defaults on install.


b) 
Pot-File: wppizza-admin.pot
the second part/file is exclusively for any text (help, explanations etc ) in the admin side of things of the plugin.
So, if your language is "en_GB" [plugin-path]/wppizza/lang/wppizza-admin-en_GB.po and [plugin-path]/wppizza/lang/wppizza-admin-en_GB.mo will be read
if it is "it_IT" it's [plugin-path]/wppizza/lang/wppizza-admin-it_IT.po and [plugin-path]/wppizza/lang/wppizza-admin-it_IT.mo
and so on
If no corresponsing files exist, plain english will be used (read directly from the plugin files themselves).


c) 
Pot-File: wppizza_dev.pot
The strings in this file are really only used in the frontend when certain development settings were activated by an administrator
but are split from the other admin strings specifically so as not to have to load the relatively large admin translations in the frontend that are not being used there at all
However, for the wppizza_dev-xx_XX.mo file to be read at all you must also define('WPPIZZA_DEV_LOAD_TEXTDOMAIN', true); in your wordpress wp-config.php 




== Editing Existing Translations ==

simply open the relevant xxx.po file in your favourite translation editor and complete or change the strings that you think would benefit from improvement.
then save and send them to me. I will happyly add them to the next release of the plugin.



== Creating New Translations ==
if - for example - you want to translate into Portugese (pt_PT)
copy
[plugin-path]/wppizza/lang/wppizza-en_GB.po
and/or
[plugin-path]/wppizza/lang/wppizza-admin-en_GB.po

as

[plugin-path]/wppizza/lang/wppizza-pt_PT.po
and/or
[plugin-path]/wppizza/lang/wppizza-admin-pt_PT.po

and edit in your favourite translation editor. when you are done, save and send them to me if you want to make sure to have them included in future updates of the plugin

Alternatively , you can of course also add a new translation using the relevant .pot file(s) instead .



== New Partial Translations ==
if you only want to do one of them (wppizza-en_GB.po for example only has about 200 strings whereas wppizza-admin-en_GB.po has sevaral thousand)
feel free to do so. every little helps and others will no doubt be thankful for your efforts



if you would also like a credit here - https://wordpress.org/plugins/wppizza/ - for your efforts in translating things,  let me know. I'll be happy to do so.


thank you so much