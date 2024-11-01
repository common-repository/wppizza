<?php
/**
* WPPIZZA_USER Class
*
* @package     WPPIZZA
* @subpackage  WPPIZZA_USER
* @copyright   Copyright (c) 2015, Oliver Bach
* @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
* @since       3.0
*
*/
if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/
/********************************************************************************************



********************************************************************************************/
class WPPIZZA_USER{
	function __construct() {

		/***************
			[add selected fields to the registration process - frontend]
		***************/
		add_action( 'register_form', array( $this, 'user_register_formfields') );
		add_action( 'user_register', array( $this, 'register_user_profile'), 100 );
		/**
			registration fields - also invoked outside is_admin for themed profiles
		**/
		add_action( 'show_user_profile', array( $this, 'print_user_profile') );
		add_action( 'personal_options_update', array( $this, 'update_user_profile' ));

		/**
			multisite
		**/
		if(is_multisite()){
			add_action( 'signup_extra_fields', array( $this, 'user_register_formfields') );
			add_filter( 'add_signup_meta',array($this, 'wppizza_ms_user_register_add_signup_meta'));//capture the data
			add_action( 'wpmu_activate_user', array($this, 'register_user_profile'), 10, 3 );//get the meta data out of signups and push it into wp_usermeta during activation
		}

		/**
			admin only
		**/
		if(is_admin()){
			/**
				registration fields - admin
			**/
			add_action( 'edit_user_profile', array( $this, 'print_user_profile') );
			add_action( 'edit_user_profile_update', array( $this, 'update_user_profile' ));
		}

	}

	/******************************************************
	*
	*	[save wppizza formfields that were added to WP registration form]
	*	@return void
	******************************************************/
	function register_user_profile( $user_id,  $password = '', $meta = array()){

		/**
			enabled form fields
		**/
		$formfields = WPPIZZA() -> helpers -> enabled_formfields();
		/**
			loop trough form fields enabled for registration
		**/
	    foreach( $formfields as $field ) {

	    	/**
	    		only listen deal with fields that were enabled to be used for registration
	    	**/
	    	if(!empty($field['onregister'])) {


	    		$metaValue = '';

	    		/*
	    			since v3.16,
	    			selects, radios, multicheckboxes are now post'ed using their indexes ,
	    			so we map these back to the values here to keep this consistent with  pre v3.16 registrations
	    		*/
	    		if(in_array($field['type'], array('select', 'radio', 'multicheckbox'))){

	    			/*
	    				select / radio
	    				convert posted numeric index to associated string
	    			*/
	    			if(in_array($field['type'], array('select', 'radio'))){

	    				//sanitise posted value (technically this should actually always be numeric)
	    				$postedIndex =  wppizza_validate_string($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]);
	    				//set meta value - if it exists
	    				$metaValue = !empty($field['value'][$postedIndex]) ? $field['value'][$postedIndex] : '' ;
	    			}

	    			/*
	    				multicheckbox
	    				convert posted array index
	    			*/
	    			if(in_array($field['type'], array('multicheckbox'))){

	    				//must be an array now
	    				$metaValue = array();

	    				//sanitise posted value (technically this should actually always be numeric)
	    				$postedIndex =  wppizza_validate_array($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]);
	    				//set meta value - if it exists
	    				foreach($postedIndex as $idxKey){
	    					if(!empty($field['value'][$idxKey])){
	    						$metaValue[$idxKey] = 	$field['value'][$idxKey];
	    					}
	    				}

	    			}

	    		}
	    		/*
	    			all others (i.e non selects, non-radios, non-multicheckboxes)
	    		*/
	    		else{

	    			$metaValue = !empty($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]) ? wppizza_validate_string($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]) : false ;

	    		}

				/*
					set meta data
				*/
				update_user_meta( $user_id, ''.WPPIZZA_SLUG.'_'.$field['key'], $metaValue );
			}
		}

		/**
			distinctly add email from wp email field for cemail
		**/
		if( isset($_POST['user_email']) ){
			$sanitizedEmail = wppizza_validate_string($_POST['user_email']);
			update_user_meta( $user_id, ''.WPPIZZA_SLUG.'_cemail', $sanitizedEmail );
		}


		/* update user */
		$userdata	=	array(
			'ID' => $user_id,
		);
		$new_user_id = wp_update_user( $userdata );


	return;
	}

	/********************************************************************************************
		update profile
		@return void;
		@since unknown
	********************************************************************************************/
	function update_user_profile($user_id){
		/*
			bail if user does not have the required permissions
		*/
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		/*
			get and loop through enabled formfields
		*/
		$formfields = WPPIZZA()->helpers->enabled_formfields();

		foreach( $formfields as $field ) {

			/*
				only worry about fields that are set to be used when registering
				but also  specifically excluding cemail here
			*/
			if($field['key']!='cemail' && !empty($field['onregister'])) {

				/*
					sanitise arrays separately
				*/
				if(!empty($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]) && is_array($_POST[''.WPPIZZA_SLUG.'_'.$field['key']])){

					$metaValue = array();

					foreach($_POST[''.WPPIZZA_SLUG.'_'.$field['key']] as $arrKey => $val){

						$metaValue[$arrKey] = wppizza_validate_string($val);

					}

				}else{

					$metaValue = !empty($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]) ? wppizza_validate_string($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]) : false ;
				}

				update_user_meta( $user_id, ''.WPPIZZA_SLUG.'_'.$field['key'], $metaValue );
			}
		}

		/**
			distinctly add email from wp email field
		**/
		$sanitizedEmail = wppizza_validate_string($_POST['email']);
		update_user_meta( $user_id, ''.WPPIZZA_SLUG.'_cemail', $sanitizedEmail );

	return;
	}

	/*
		multisite signup add meta
	*/
	function wppizza_ms_user_register_add_signup_meta($meta){
		$formfields=WPPIZZA()->helpers->enabled_formfields();
	    foreach( $formfields as $field ) {
	    if(!empty($field['onregister']) && isset($_POST[''.WPPIZZA_SLUG.'_'.$field['key']])) {
	    	/**selects/radios should be stored by index**/
	    	if($field['type'] == 'select' || $field['type'] == 'radio'){
	    		$posted=$_POST[''.WPPIZZA_SLUG.'_'.$field['key']];
	    		$sanitizedInput = isset($field['value'][$posted]) ? $posted : null;
	    	}else{
	    		$sanitizedInput = wppizza_validate_string($_POST[''.WPPIZZA_SLUG.'_'.$field['key']]);
	    	}

			$meta[''.WPPIZZA_SLUG.'_'.$field['key']] = $sanitizedInput;
		}}

	return $meta;
	}



	/********************************************************************************************
		profile page - admin or themed profiles
	********************************************************************************************/
	function print_user_profile($user){

		global $wppizza_options;

		/* get user meta data */
		$userMetaData=$this->user_meta($user->ID);

		/* get enabled formfields*/
		$formfields=WPPIZZA()->helpers->enabled_formfields();

		/* h3 header */
		if($wppizza_options['localization']['user_profile_label_additional_info']!=''){
			print'<h3>'.$wppizza_options['localization']['user_profile_label_additional_info'].'</h3>';
		}

		print'<table class="form-table">';
			foreach( $formfields as $field ) {

				/**lets exclude disabled and "email" as wp already has this of course, as well as gratuities**/
				if($field['type']!='email' && $field['type']!='tips' && !empty($field['onregister'])){

				$selectedValue = !empty($userMetaData[''.WPPIZZA_SLUG.'_'.$field['key'].'']) ? (maybe_unserialize($userMetaData['wppizza_'.$field['key'].''])) : '';

				print'<tr><th><label for="'.WPPIZZA_SLUG.'_'.$field['key'].'">' . $field['lbl'] . '</label></th><td>';

					/**normal text input**/
					if ( $field['type']=='text'){
			    		print'<input type="text" name="'.WPPIZZA_SLUG.'_'.$field['key'].'" id="'.WPPIZZA_SLUG.'_'.$field['key'].'" value="'.$selectedValue.'" class="regular-text" />';
					}
					/**textareas**/
					if ( $field['type']=='textarea'){
						print'<textarea name="'.WPPIZZA_SLUG.'_'.$field['key'].'" id="'.WPPIZZA_SLUG.'_'.$field['key'].'" rows="5" cols="30">'.$selectedValue.'</textarea>';
					}
					/**select**/
					if ( $field['type']=='select'){

						$setVal = wppizza_decode_entities_trim($selectedValue);

						print'<select name="'.WPPIZZA_SLUG.'_'.$field['key'].'" id="'.WPPIZZA_SLUG.'_'.$field['key'].'">';

							print'<option value="">-----------</option>';

							foreach($field['value'] as $sKey => $value){

								$optVal = wppizza_decode_entities_trim($value);

								print'<option value="'.$value.'" '.selected($optVal, $setVal, false).'>'.$value.'</option>';

							}
						print'</select>';
					}
					/**checkbox**/
					if ($field['type']=='checkbox'){
						print'<input type="checkbox" name="'.WPPIZZA_SLUG.'_'.$field['key'].'" id="'.WPPIZZA_SLUG.'_'.$field['key'].'" value="1" '.checked(!empty($selectedValue),true,false).' />';
					}
					/**multicheckbox**/
					if ($field['type']=='multicheckbox'){
						foreach($field['value'] as $mKey => $multicheckbox_value){
							echo'<span><input type="checkbox" name="'.WPPIZZA_SLUG.'_'.$field['key'].'['.$mKey.']" id="'.WPPIZZA_SLUG.'_'.$field['key'].'_'.$mKey.'"  '.checked(!empty($selectedValue[$mKey]),true,false).' value="'.$multicheckbox_value.'"/>'.$multicheckbox_value.' </span>';
						}
					}
					/**radio**/
					if ($field['type']=='radio'){

						$setVal = wppizza_decode_entities_trim($selectedValue);

						foreach($field['value'] as $rKey=>$radio_value){

							$optVal = wppizza_decode_entities_trim($radio_value);

							echo'<span><input type="radio" name="'.WPPIZZA_SLUG.'_'.$field['key'].'" id="'.WPPIZZA_SLUG.'_'.$field['key'].'"  '.checked($optVal, $setVal, false).' value="'.$radio_value.'"/>'.$radio_value.' </span>';
						}
					}
				print"</td></tr>";
			}}
		print"</table>";
	}


	/******************************************************
	*
	*	[show selected fields in WP registration form]
	*
	******************************************************/
	function user_register_formfields(){

		$formfields = WPPIZZA()->helpers->enabled_formfields();

	    foreach($formfields as $field){
	    	if(!empty($field['onregister'])) {


			/* name / id / label for*/
			$name_id = ''.WPPIZZA_SLUG.'_'.$field['key'].'';
			/** class **/
			$class= 'input';

			/*
				entered value - strings / text(areas) only
				- i.e no arrays to eliminate potential fatal errors in case the page gets reloaded with "This username is already registered" or some such
			*/
			if(in_array( $field['type'], array('text', 'textarea'))){
				$input_value = !empty($_POST[ ''.WPPIZZA_SLUG.'_'.$field['key']]) && !is_array($_POST[ ''.WPPIZZA_SLUG.'_'.$field['key']])  ? stripslashes(wppizza_validate_string($_POST[ ''.WPPIZZA_SLUG.'_'.$field['key']])) : '' ;
			}

			/*
				output
			*/
	 		echo'<p>';
	 			/* label */
	 			echo'<label for="' . $name_id . '">';
	 			/* text input */
	 			if ( $field['type']=='text'){
	 				echo''.$field['lbl'].'<br />';
	 				echo'<input type="text" name="' . $name_id . '" id="' . $name_id . '" class="' . $class . '" value="'. $input_value . '" size="20" />';
	 			}
				/**textareas**/
				if ( $field['type']=='textarea'){
					echo''.$field['lbl'].'<br />';
					print'<textarea name="' . $name_id . '" id="' . $name_id . '" class="' . $class . '" rows="5" cols="30">' . $input_value . '</textarea>';
				}
				/**select**/
				if ( $field['type']=='select'){
					echo''.$field['lbl'].'<br />';
					print'<select name="' . $name_id . '" id="' . $name_id . '" class="' . $class . '">';
						print'<option value="">--------</option>';
						foreach($field['value'] as $key => $select_value){
							print'<option value="' . $key . '" '.selected($key,$select_value,false).'>' . $select_value . '</option>';
						}
					print'</select>';
				}
				/**checkbox**/
				if ( $field['type']=='checkbox'){
					echo''.$field['lbl'].' ';
					echo'<input type="checkbox" name="' . $name_id . '" id="' . $name_id . '" class="" value="1" />';
				}
				/**multicheckbox**/
				if ( $field['type'] == 'multicheckbox'){
					echo''.$field['lbl'].'<br />';
					foreach($field['value'] as $key => $select_value){
						/* show multi checkbox options */
						echo'<span><input type="checkbox" name="' . $name_id . '[]" id="' . $name_id . '_'.$key.'"  value="'. $key . '" />'.$select_value.' </span>';
					}
				}
				/**radio**/
				if ( $field['type']=='radio'){
					echo''.$field['lbl'].'<br />';
					$i=0;
					foreach($field['value'] as $key => $select_value){
						/* show radio options, preselecting first one */
						echo'<span><input type="radio" name="' . $name_id . '" id="' . $name_id . '"  value="'. $key . '"  '.checked($i,0,false).'/>'.$select_value.' </span>';
					$i++;
					}
				}
	 			echo'</label>';
	 		echo'</p>';
	    	}
	    }
	}

	/******************************************************
	*
	*	[update meta from order page ]
	*	set user_id to force update for specific user (when creating account)
	******************************************************/
	function update_profile($user_id = false, $userdata = false, $force_update = false){

		/*
			can users register  and is there actually any data to update ?
		*/
		$users_can_register = is_multisite() ? apply_filters('option_users_can_register', false) :  get_option('users_can_register');
		/* users cannot register anyway or userdata empty, bail */
		if(empty($users_can_register) || empty($userdata)){
			return;
		}

		/*********
			get formfield keys native to this plugin to distinguish between
			own wppizza fields or fields that have been added by filters
		*********/
		$native_formfields = WPPIZZA() -> helpers -> native_formfields();

		/*
			user id 0 / not logged in /  no userdata and not forced update, bail
		*/
		if((empty($user_id) || !is_user_logged_in()) && !$force_update ){return;}


		/*
			get all enabled formfields
		*/
		$formfields = WPPIZZA() -> helpers -> enabled_formfields();


		/**
			loop trough enabled form fields checking they are enabled for registration
			and update what we can
		**/
	    foreach( $formfields as $ffKey => $field ) {

    		/**
    			only capture fields that were enabled to be used for registration
    		**/
	    	if(!empty($field['onregister']) && isset($userdata[$ffKey]) && isset($field['value']) && is_array($field['value']) ) {

				$metaValue = $userdata[$ffKey]['value'];


				/*
					map if necessary
				*/
				if(in_array($field['type'], array('select', 'radio', 'checkbox', 'multicheckbox'))){

	    			/*
	    				select
	    				convert numeric index back to string
	    			*/
	    			if(in_array($field['type'], array('select'))){

						//for backwards compatibility with other plugins
						if(!isset($native_formfields[$ffKey])){
							$metaValue = wppizza_sanitize_post_vars(wppizza_decode_entities_trim($metaValue));
						}
	    				$metaValue = isset($field['value'][$metaValue]) ? $field['value'][$metaValue] : '' ;

	    			}

	    			/*
	    				radio
	    				convert numeric index back to string
	    			*/
	    			if(in_array($field['type'], array('radio'))){
	    				$metaValue = isset($field['value'][$metaValue]) ? $field['value'][$metaValue] : '' ;
	    			}

	    			/*
	    				checkbox - dont use the value (as it might be a string saying "No" or some such)
	    				but the boolean valueSet here
	    			*/
	    			if(in_array($field['type'], array('checkbox'))){
	    				$metaValue = !empty($userdata[$ffKey]['ischecked']) ? true : false ;//bool
	    			}

	    			/*
	    				multicheckbox
	    				convert numeric indexes back to string
	    			*/
	    			if(in_array($field['type'], array('multicheckbox'))){


	    				$selected = array_flip(wppizza_strtoarray($metaValue));
						$intersected = array_intersect_key($field['value'], $selected);
						$metaValue = $intersected;
	    			}

				}

				/*
					update the meta
				*/
				update_user_meta( $user_id, ''.WPPIZZA_SLUG.'_'.$ffKey, $metaValue );
			}
		}

	return;
	}

	/******************************************************
	*
	*	[create account from order page when order has been successfully executed]
	*
	******************************************************/
	function create_account($order_id = false, $userdata = false){
		global $wp_version;
		/*
			 already logged in, users cannot register, no userdata or no email, bail
		*/
		$set_email = !empty($userdata['cemail']['value']) ? $userdata['cemail']['value'] : false ;
		$can_register = is_multisite() ? apply_filters('option_users_can_register', false) :  get_option('users_can_register');
		if(	is_user_logged_in() || empty($can_register) || empty($set_email) ){
			return;
		}

		/************************************************
			check if email exists already
			if it does not carry on adding account
		************************************************/
		$user_id = username_exists( $set_email );
		$email_id = email_exists( $set_email );


		/**
			user already exists, just login and update meta
			i dont think one should be doing this
			as anyone could use any others email to get logged in ...!?
		**/
		//if($user_id && $email_id){
		//	/* just login */
		//	wp_set_auth_cookie( $user_id );
		//	/** update user profile */
		//	$this->update_profile($user_id);
		//}


		/**
			new user
		**/
		if(!$user_id && !$email_id){

			/*
				change name and email address "From" for registration emals (to avoid it simply sayin "Wordpress")
				@since 3.7.1
			*/
			add_filter( 'wp_mail_from', array($this, 'registrations_sender_email' ));
			add_filter( 'wp_mail_from_name', array($this, 'registrations_sender_name' ));

			/*generate a pw**/
			$user_password = wp_generate_password( 10, true );
			/*create the user**/
			$user_id_new = wp_create_user( $set_email, $user_password, $set_email );
			/*send un/pw to user*/
			if($user_id_new && $user_password!=''){/*bit of overkill*/

				$new_user_notification = apply_filters('wppizza_new_user_notification', 'both');/* should return 'user', 'admin' or 'both'. default 'both' */

				/*old wp versions <4.3**/
 				if ( version_compare( $wp_version, '4.3', '<' ) ) {
            		wp_new_user_notification( $user_id_new, $user_password );
        		}
 				if ( version_compare( $wp_version, '4.3', '==' ) ) {
            		wp_new_user_notification( $user_id_new, $new_user_notification );
        		}
        		if ( version_compare( $wp_version, '4.3.1', '>=' ) ) {
					wp_new_user_notification( $user_id_new, null, $new_user_notification );
        		}
        		/**login too*/
				wp_set_auth_cookie( $user_id_new );
				/**turn off admin bar front by default**/
				update_user_meta( $user_id_new, 'show_admin_bar_front', 'false' );
				/** force update user profile */
				$this->update_profile($user_id_new, $userdata, true);

				/***************************************************************
					associate order with this userid now
				****************************************************************/
				$update_db_values = array();
				/** amend wp_user_id */
				$update_db_values['wp_user_id'] 		= array('type'=> '%d', 'data' =>(int)$user_id_new);
				/* update db */
				$order_update_user_id = WPPIZZA()->db->update_order(false, $order_id, false , $update_db_values);

			}
		}
	return;
	}

	/******************************************************
	*
	*	[set user registration from email and name
	*	instead of default "Wordpress" , "wordpress@...."]
	*	@since 3.7.1
	*
	******************************************************/
	function registrations_sender_email( $email_address ) {
		/*
			set to do not reply if part before @ is still "wordpress"
		*/
		$x_email_address = explode('@', $email_address);
		if(!empty($x_email_address[0]) && strtolower(trim($x_email_address[0])) == 'wordpress' && !empty($x_email_address[1])){
			/* simply replace "wordpress" as the domain has already been dealt with by wp_mail function */
			$email_address = 'do-not-reply@'.$x_email_address[1];
		}
	return $email_address;
	}

	/********************************************************************************************
	# @since unknown
	********************************************************************************************/
	function registrations_sender_name( $email_from ) {
		/*
			replace email from, if it is still set to be wordpress here
		*/
		if(empty($email_from) || strtolower(trim($email_from)) == 'wordpress' ){
			global $blog_id;
			$blog_info = WPPIZZA() -> helpers -> wppizza_blog_details($blog_id);

			$email_from = wp_specialchars_decode($blog_info['blogname'], ENT_QUOTES );
		}
	return $email_from;
	}

	/******************************************************
	*
	*	[login form markup on order page]
	*
	******************************************************/
	/************************************************************************
		[output login form or logout link on order page or user history or admin order history by shortcode]
	************************************************************************/
	function login_form($show_registration_disabled = false, $do_login = true, $force_login = false){
		global $wppizza_options;
		$txt=$wppizza_options['localization'];
		$users_can_register = is_multisite() ? apply_filters('option_users_can_register', false) :  get_option('users_can_register');
		$login_form_args = array('echo'=>false, 'remember' => false);

		/*
			if: not forcing to show login,
			or: registration disabled sitewide,
			or: already logged in,
			or nothing in cart (if on orderpage)
			do nothing
		*/
		if(empty($force_login)){

			if(empty($users_can_register) && $show_registration_disabled && !is_user_logged_in()){
				return __('Sorry, user registration is disabled on this system', 'wppizza-admin');
			}

			if(empty($users_can_register) || is_user_logged_in() || empty($do_login)){
				return;
			}
		}

		/**
			login enabled
		**/
		if($do_login) {

			/* set classes */
			$anchor_name = '' . WPPIZZA_PREFIX . '-login';
			$class = '' . WPPIZZA_PREFIX . '-login';
			$class_toggle = '' . WPPIZZA_PREFIX . '-login-option';
			$class_show = '' . WPPIZZA_PREFIX . '-login-show';
			$class_cancel = '' . WPPIZZA_PREFIX . '-login-cancel';
			$class_fieldset = '' . WPPIZZA_PREFIX . '-fieldset ' . WPPIZZA_PREFIX . '-login-fieldset';
			$class_form = '' . WPPIZZA_PREFIX . '-login-form';
			$class_info = '' . WPPIZZA_PREFIX . '-login-info';
			$class_password = '' . WPPIZZA_PREFIX . '-login-lostpw';


			$login_form = apply_filters('wppizza_filter_login_markup', wp_login_form(apply_filters('wppizza_filter_loginform_arguments', $login_form_args)));
			/* let's add a nonce, just for the hell of it. cannot do any harm */
			$nonce = wp_nonce_field( ''.WPPIZZA_PREFIX.'_nonce_login',''.WPPIZZA_PREFIX.'_nonce_login', false, false);
			$login_form = str_ireplace('</form', $nonce.'</form', $login_form);
			/*
				ini markup array
			*/
			$markup = array();
			/*
				get markup
			*/
			if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/login.php')){
				require(WPPIZZA_TEMPLATE_DIR.'/markup/global/login.php');
			}else{
				require(WPPIZZA_PATH.'templates/markup/global/login.php');
			}
			/*
				apply filter if required and implode for output
			*/
			$markup = apply_filters('wppizza_filter_login_widget_markup', $markup);
			$markup = implode('', $markup);


			return $markup;
		}
	}

	/********************************************************************************
		[output
		div with radio options to continue as guest or simultaneous registration]
		will not output anything if registration is disabled or emails are not on
		order page
	********************************************************************************/
	function profile_options(){
		global $wppizza_options;
		$txt = $wppizza_options['localization'];
		$enabled_formfields = WPPIZZA()->helpers->enabled_formfields();
		$users_can_register = is_multisite() ? apply_filters('option_users_can_register', false) :  get_option('users_can_register');
		$user_session = WPPIZZA()->session-> get_userdata();
		/***********************************************************
			check if we have the email set to enabled and required
			as otherwise new registration on order will not work
			as there's noweher to send the password to
		*************************************************************/
		$can_register = (isset($enabled_formfields['cemail']) && !empty($enabled_formfields['cemail']['required'])) ? true : false ;
		/** profle update ? */
		$profile_update = (is_user_logged_in() && !empty($users_can_register)) ? true : false;
		/** create account */
		$create_account = (!is_user_logged_in() && !empty($users_can_register) && !empty($can_register)) ? true : false;

		/*
			user is logged in and
			registration enabled
			add profile update checkbox
		*/
		if($profile_update) {

			$id = WPPIZZA_PREFIX . '_profile_update';
			$class = WPPIZZA_PREFIX . '_profile_update';
			$name = WPPIZZA_PREFIX . '_profile_update';
			$checked = checked(!empty($user_session[''.WPPIZZA_SLUG.'_profile_update']),true,false);



			/*
				ini markup array
			*/
			$markup = array();
			/*
				get markup
			*/
			if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/profile.update.php')){
				require(WPPIZZA_TEMPLATE_DIR.'/markup/global/profile.update.php');
			}else{
				require(WPPIZZA_PATH.'templates/markup/global/profile.update.php');
			}

			/*
				filter and
				implode for output
			*/
			$markup = apply_filters('wppizza_filter_profile_update_markup', $markup);
			$markup = implode('',$markup);

		return $markup;
		}


		/*
			user is NOT logged in and
			registration enabled (with email set as form field)
			add register user / continue as guest
			radio options, provided email field exists and is required
		*/
		if($create_account) {
			$id_create_account = WPPIZZA_PREFIX . '-create-account';
			$class_create_account = WPPIZZA_PREFIX . '-create-account';
			$id_account_guest = WPPIZZA_PREFIX . '_account_guest';
			$id_account_register = WPPIZZA_PREFIX . '_account_register';
			$class_guest = ''.WPPIZZA_PREFIX . '_account '.WPPIZZA_PREFIX . '_account_guest';
			$class_register = ''.WPPIZZA_PREFIX . '_account '.WPPIZZA_PREFIX . '_account_register';
			$name = WPPIZZA_PREFIX . '_account';
			$checked_guest = checked(empty($user_session[''.WPPIZZA_SLUG.'_account']),true,false);
			$checked_register = checked(!empty($user_session[''.WPPIZZA_SLUG.'_account']),true,false);
			$id_register_info = WPPIZZA_PREFIX . '-user-register-info';
			$register_info_css_visibility = empty($user_session[''.WPPIZZA_SLUG.'_account']) ? 'display:none' : 'display:block' ;//show/hide register info depending on selection

			/*
				ini markup array
			*/
			$markup = array();
			/*
				get markup
			*/
			if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/profile.register.php')){
				require(WPPIZZA_TEMPLATE_DIR.'/markup/global/profile.register.php');
			}else{
				require(WPPIZZA_PATH.'templates/markup/global/profile.register.php');
			}
			/*
				filter and
				implode for output
			*/
			$markup = apply_filters('wppizza_filter_profile_register_markup', $markup);
			$markup = implode('',$markup);

		return $markup;
		}

	/* if it gets here, markup will be an empty string */
	return '';
	}


	/**************************************************************************************************************************
		fieldset markup userdata
		labels and inputs
		or selected only
	**************************************************************************************************************************/
	function formfields_inputs($ffs, $order_formatted = array(), $type = 'orderpage'){

		$formfields =array();

		foreach($ffs as $key=>$ff){

			/*
				if omit_if_optional is set,
				omit formfield if field is not required to be filled in
				the 'required_attribute' willalready be set (or not as the case may be)
				depending on if it's pickup or delivery and required or not for those
				
				Note: Perhaps change to 'hidden' type here - instead of omitting entirely - to be able to switch 
				between pickup and delivery and keep any session values.
				However, this needs thorough checking with selects , radios etc, so for now let's leave this as is
			*/
			if(!empty($ff['omit_if_optional']) && empty($ff['required_attribute'])){
				continue;
			}

			/*
				omit tips as they are displayed in the order summary
			*/
			if($ff['type'] != 'tips'){


				/*
					allow label etc filtering per formfield inputs
				*/
				$ff = apply_filters('wppizza_filter_formfields_inputs_'.$key.'', $ff, $type );

				/*
					alternative to above  but key and type passed on as arguments instead
				*/
				$ff = apply_filters('wppizza_filter_formfields_input', $ff, $key, $type );

				/*
					ini array
				*/
				$formfields[$key] = array();

				/* set class adding type */
				$formfields[$key]['class'] = ''.WPPIZZA_PREFIX.'-'.$key.'';
				if(!empty($ff['type'])){
				$formfields[$key]['class'] .= ' '.WPPIZZA_PREFIX.'-'.$ff['type'].'';
				}

				/* set style if set (currenty only used on *orderpage* formfields) */
				$formfields[$key]['style'] = !empty($ff['style']) ? 'style="'.$ff['style'].'"' : '' ;

				/* set field */
				$formfields[$key]['field'] = '';

				/* normal links - no inputs */
				if($ff['type']=='link'){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
				}

				/* text / emails / (tips are displayed in subtotals) */
				if(in_array($ff['type'],array('text', 'email'))){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
					$formfields[$key]['field'] .= '<input id="'. $key .'" name="'. $key.'"  type="text" value="' . $ff['value'] . '" placeholder="' .$ff['placeholder'] . '"  ' . $ff['required_attribute'] . ' ' . ( !empty($ff['autocomplete']) ? $ff['autocomplete'] : '' ) . ' />';
				}

				/* textarea */
				if($ff['type']=='textarea'){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
					$formfields[$key]['field'] .= '<textarea id="'. $key .'" name="'. $key.'" placeholder="' .$ff['placeholder'] . '" ' . $ff['required_attribute'] . ' ' . (!empty($ff['autocomplete']) ? $ff['autocomplete'] : '') . '>' . $ff['value'] . '</textarea>';
				}

				/* checkbox -  with label _after_ input*/
				if($ff['type']=='checkbox'){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . ' title="'.esc_attr($ff['placeholder']).'">';
					$formfields[$key]['field'] .= '<input id="'. $key .'" name="'. $key.'"  type="checkbox" value="1"  ' . $ff['required_attribute'] . ' '.checked($ff['value'], true, false).'/>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
				}

				/* multicheckbox */
				if($ff['type']=='multicheckbox'){
					// convert comma separated value back to (trimmed) array
					$val_as_array = array_map('trim', explode(',' , $ff['value']) );

					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
					$formfields[$key]['field'] .= '<div class="'.WPPIZZA_PREFIX.'-multicheckbox" title="'.esc_attr($ff['placeholder']).'">';
					foreach($ff['options'] as $k => $option){
						//Note: Do not add the $k to the [] in the name or validation will not work when set to required
						$formfields[$key]['field'] .= '<label><input id="'. $key .'_'.$k.'" value="'. $k .'" name="'. $key.'[]"  type="checkbox" ' . $ff['required_attribute'] . ' '.checked( ( !empty($val_as_array) && in_array($k, $val_as_array)) ,true, false).'/>'.$option.' </label>';
					}
					$formfields[$key]['field'] .= '</div>';
				}

				/* radio */
				if($ff['type']=='radio'){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
					$formfields[$key]['field'] .= '<div class="'.WPPIZZA_PREFIX.'-radio" title="'.esc_attr($ff['placeholder']).'">';
					foreach($ff['options'] as $k => $option){
						$formfields[$key]['field'] .= '<label><input id="'. $key .'_'.$k.'" value="'. $k .'" name="'. $key.'"  type="radio" ' . $ff['required_attribute'] . ' '.checked($ff['value'], $k, false).'/>'.$option.' </label>';
					}
					$formfields[$key]['field'] .= '</div>';
				}

				/* select */
				if($ff['type']=='select'){
					$formfields[$key]['field'] .= '<label for="'. $key .'"' . $ff['required_class'] . '>';
					$formfields[$key]['field'] .= '' . $ff['label'] . '';
					$formfields[$key]['field'] .= '</label>';
					$formfields[$key]['field'] .= '<select id="'. $key .'" name="'. $key.'" title="'.esc_attr($ff['placeholder']).'" ' . $ff['required_attribute'] . ' >';
					foreach($ff['options'] as $oKey => $option){
						/* account for placeholder separately*/
						if($oKey == -1){
							$formfields[$key]['field'] .= '<option value="">'. $option['label'] .'</option>';
						}else{
							$set = wppizza_decode_entities_trim($ff['value']);
							$match = wppizza_decode_entities_trim($oKey);

							$formfields[$key]['field'] .= '<option value="'. $oKey .'" '.selected($set, $match, false).'>'. $option['label'] .'</option>';
						}
					}
					$formfields[$key]['field'] .= '</select>';
				}

				/* hidden, just add hidden field  */
				if($ff['type']=='hidden'){
					$formfields[$key]['field'] .= '<input id="'. $key .'" name="'. $key.'"  type="hidden" value="' . $ff['value'] . '" />';
				}

				/* html,  adding some custom html after it all - unused in plugin itself , but might come in handy for 3rd party plugins*/
				/* to additionally bypass the above too and just have the html, set 'type' to something not used above (like html for example) */
				if(!empty($ff['html'])){
					$formfields[$key]['field'] .= $ff['html'];
				}
			}
		}

		/*
			filter formfields if required (type will be orderpage or confirmation page)
		*/
		$formfields = apply_filters('wppizza_filter_'.$type.'_formfields', $formfields, $order_formatted);


		/*************************************

			markup

		*************************************/
		/*
			ini array
		*/
		$markup = array();
		/*
			get markup
		*/
		if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/formfields.inputs.php')){
			require(WPPIZZA_TEMPLATE_DIR.'/markup/global/formfields.inputs.php');
		}else{
			require(WPPIZZA_PATH.'templates/markup/global/formfields.inputs.php');
		}
		/*
			apply filter if required and implode for output
		*/
		$markup = apply_filters('wppizza_filter_formfields_inputs', $markup, $formfields);
		$markup = implode('', $markup);


	return $markup;
	}

	/******************************
		fieldset markup userdata
		labels and values only , no inputs
	******************************/
	function formfields_values($ffs, $page){

		/*********
			get formfield keys native to this plugin to distinguish between
			own wppizza fields or fields that have been added by filters
			(backwards compatibility)
		*********/
		$native_formfields = WPPIZZA() -> helpers -> native_formfields();


		$formfields =array();
		if(!empty($ffs)){
		foreach($ffs as $key=>$ff){

			/*
				if omit_if_optional is set,
				omit formfield if field is not required to be filled in
				the 'required_attribute' willalready be set (or not as the case may be)
				depending on if it's pickup or delivery and required or not for those
			*/
			if(!empty($ff['omit_if_optional']) && empty($ff['required_attribute'])){
				continue;
			}



			/*
				allow filtering of formfield values (label for example) - passing on page parameters too
			*/
			$ff = apply_filters('wppizza_filter_formfields_values_'.$key.'', $ff, $page);

			/*
				ini array
			*/
			$formfields[$key] = array();

			/*
				set class
			*/
			$formfields[$key]['class'] = ''.WPPIZZA_PREFIX.'-'.$key.'';

			/*
				set label
			*/
			$formfields[$key]['label'] = $ff['label'];

			/*
				set value - if its coming from a select/dropdown/radio, map the id back to the set label/value
			*/

			//selects
			if($ff['type'] == 'select'){
				/*
					for backwards compatibility with other/older plugins

					adding 'indexed' = true to the ff as key  denotes that we are using numeric indexes
					as dropdown values associated to the displayed text string - effectively making
					it behave like any select formfield native to the wppizza plugin before adding additional
					fields via filters
				*/
				if(!isset($native_formfields[$key]) && empty($ff['indexed'])){
					$formfields[$key]['value'] = $ff['value'] ;
				}else{
 					$formfields[$key]['value'] = isset($ff['options'][$ff['value']]['label']) ? $ff['options'][$ff['value']]['label'] : '' ;
				}
			}

			//radios
			elseif($ff['type'] == 'radio' ){
				$formfields[$key]['value'] = isset($ff['options'][$ff['value']]) ? $ff['options'][$ff['value']] : '' ;
			}

			//multicheckboxes
			elseif($ff['type'] == 'multicheckbox' ){


				$output = '';//init empty

				//intersect and implode if something was selected
				if($ff['value'] != ''){
					$selected = array_flip(wppizza_strtoarray($ff['value']));
					$intersect = array_intersect_key($ff['options'], $selected);
					$output = implode(', ',$intersect);
				}
				$formfields[$key]['value'] = $output ;
			}

			//all other non dropdown / non radio / non multicheckboxes
			else{
				$formfields[$key]['value'] = is_array($ff['value']) ? implode(', ',$ff['value']) : $ff['value'] ;
			}


		}}

		/*************************************

			markup

		*************************************/
		/*
			ini array
		*/
		$markup = array();
		/*
			get markup
		*/
		if(file_exists( WPPIZZA_TEMPLATE_DIR . '/markup/global/formfields.values.php')){
			require(WPPIZZA_TEMPLATE_DIR.'/markup/global/formfields.values.php');
		}else{
			require(WPPIZZA_PATH.'templates/markup/global/formfields.values.php');
		}
		/*
			apply filter if required and implode for output
		*/
		$markup = apply_filters('wppizza_filter_formfields_values', $markup, $formfields, $page);
		$markup = implode('', $markup);



	return $markup;

	}

	/********************************************************************************************
		get user meta data (if logged in)
		used in self and markup.pages
		return array or false
	********************************************************************************************/
	function user_meta($user_id = false, $single_key = ''){

		/** userid set */
		if($user_id){

			$user_metadata=get_user_meta( $user_id, $single_key, true);

			/* returning single value only */
			if(!empty($single_key)){
				return $user_metadata;
			}

			/* return as single dimension array */
			$metadata = array();
			foreach($user_metadata as $meta_key=>$meta_value){
				$metadata[$meta_key] = $meta_value[0];
			}

			/*
				selectively override cemail with registered email address
			*/
			$registered_user_data = get_userdata($user_id);
			$registered_user_email = $registered_user_data -> user_email;
			//add/override cemail with user account registered email unless it exists as distinct meta data
			$metadata[WPPIZZA_SLUG.'_cemail'] = !empty($metadata[WPPIZZA_SLUG.'_cemail']) ? $metadata[WPPIZZA_SLUG.'_cemail'] : $registered_user_email ;


		return $metadata;
		}

		/** user logged in, get id and metadata from there */
		if(is_user_logged_in()){
			$user_id = get_current_user_id();
			$user_metadata = get_user_meta( $user_id, $single_key, true);

			/* returning single value only */
			if(!empty($single_key)){
				return $user_metadata;
			}

			/* return as single dimension array */
			$metadata = array();
			foreach($user_metadata as $meta_key=>$meta_value){
				$metadata[$meta_key] = $meta_value[0];
			}

			/*
				selectively override cemail with registered email address
			*/
			$registered_user_data = get_userdata($user_id);
			$registered_user_email = $registered_user_data -> user_email;
			//add/override cemail with user account registered email unless it exists as distinct meta data
			$metadata[WPPIZZA_SLUG.'_cemail'] = !empty($metadata[WPPIZZA_SLUG.'_cemail']) ? $metadata[WPPIZZA_SLUG.'_cemail'] : $registered_user_email ;

		return $metadata;
		}


		/** no user id and not logged in */
		if(!$user_id && !is_user_logged_in()){
				$user_metadata = false;
		return $user_metadata;
		}

	}

	/********************************************************************************************
		Due to legacy reasons, added meta data by the plugin to the user profiles are stored
		as the actual values (instead of indexes) for selects, radios, checkboxes
		since v3.16 however the form data on the checkout now uses indexes (i.e numbers) as values
		that are being submitted for thios input types. To prefill the checkout form - if and when required -
		we need to map these values stored in th eprofile back to their indexes

	@param array
	@param bool (to revert to values when choosing to update profile from orderpage)
	@return array
	@since 3.16
	********************************************************************************************/
	function map_user_meta($values = array()){

		/*
			keep the original only overwriting as required below
		*/
		$mapped = $values;

		/**
			enabled form fields
		**/
		$formfields = WPPIZZA() -> helpers -> enabled_formfields();


		/**
			loop trough form fields enabled for registration
		**/
	    foreach( $formfields as $ffKey => $field ) {


			/*************
				key ident
			*************/
			$keyId = WPPIZZA_SLUG.'_'.$ffKey;

	    	/**
	    		only listen deal with fields that were enabled to be used for registration
	    		the value for this key is not empty
	    		and there is some index in the values of the formfield to start off with
	    	**/
	    	if(!empty($field['onregister']) && isset($values[$keyId]) && !empty( array_filter($field['value']))) {

	    		/*
	    			current value as stored in meta data
	    			skip if empty (but allow for 0 !)
	    		*/
	    		$metaValue = $values[$keyId];
	    		if($metaValue === null || $metaValue == '' || $metaValue === false ){
	    			break;
	    		}


				/*
					map if necessary
				*/
				if(in_array($field['type'], array('select', 'radio', 'multicheckbox'))){

	    			/*
	    				select / radio
	    				convert saved string to associated numeric index
	    			*/
	    			if(in_array($field['type'], array('select', 'radio'))){

						//decode all entities and trim for more reliable comparison
						$savedValue = wppizza_decode_entities_trim($metaValue);

						foreach($field['value'] as $idx => $val){

							if($val === null || $val == '' || $val === false ){
								break ;
							}


							//decode all entities and trim for more reliable comparison
							$setValue = wppizza_decode_entities_trim($val);

							//found the index
							if($savedValue == $setValue){

								//map the index
								$mapped[$keyId] = $idx;

							break ;
							}

						}

	    			}

	    			/*
	    				select / radio
	    				convert saved string to associated numeric index
	    			*/
	    			if(in_array($field['type'], array('multicheckbox'))){

	    				//should be saved as a serialised array
	    				$savedValue = maybe_unserialize($metaValue);
	    				if(!is_array($savedValue)){
	    					break ;
	    				}
	    				//comma separated indexes
	    				$mapped[$keyId] = implode(',',array_keys($savedValue));

	    			}
				}
	    	}
	    }

	return $mapped;
	}

}
?>