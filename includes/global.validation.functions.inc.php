<?php if ( ! defined( 'ABSPATH' ) ) exit;/*Exit if accessed directly*/ ?>
<?php
/*****************************************************
* validate and convert characters in string  using internal wordpress functions
* Note: we shouldnt really ever pass an array to this function
* @str the string to check
* @htmlAllowed  whether or not html should be escaped/stripped
******************************************************/
function wppizza_validate_string($str, $htmlAllowed=false) {
	if($str === null || $str === false  || trim($str) == ''){
		return '';
	}
	$str=convert_chars($str);
	if(!$htmlAllowed){
		$str=esc_html($str);
	}
return $str;
}
/*****************************************************
* Validates integer
* @str the input to check
* @arr some args we can pass on (currenly only max/min supported)
/******************************************************/
function wppizza_validate_int_only($str, $args = false){
	$str=(int)(preg_replace("/[^0-9]/","",$str));

	if(isset($args['min']) && is_numeric($args['min'])){
		$str=min($str, (int)$args['min']);
	}
	if(isset($args['max']) && is_numeric($args['max'])){
		$str=max($str, (int)$args['max']);
	}
	if(isset($args['allow_empty']) && empty($str) ){
		$str='';
	}


return $str;
}
/*****************************************************
* Validates an ip address or (registered) wordpress user id
* return empty str if 0 is entered (to not return unregistered(0) user id's)
* @str the input to check
* @return str
* @since 3.12.4
/******************************************************/
function wppizza_validate_ip_or_userid($str){
	/* explode by comma to make it an array to validate each*/
	$arr = explode(',', $str);
	$validated = array();
	foreach($arr as $val){
		$val = preg_replace("/[^a-zA-Z0-9:\.]/","",$val);
		$val = !empty($val) ? $val : '' ;
		if(!empty($val)){
			$validated[] = $val;
		}
	}
	/* implode back to comma separated string */
	$res = implode(',',$validated);

return $res;
}
/*****************************************************
* Validates ISO currency
* @str the input to check
/******************************************************/
function wppizza_validate_iso_currency($str){
	$str=strtoupper($str);
	$str=preg_replace("/[^A-Z]/","",$str);
	$str=substr($str,0,3);
return $str;
}
/*****************************************************
* Simple phonenumber validation allowing for +
* @str the input to check
* @ret sanitised phone
* @since 3.13.3
******************************************************/
function wppizza_validate_phone($str){

	$str=(preg_replace("/[^0-9\+]/","",$str));

	/* check for + */
	$prefix = substr($str, 0, 1) == '+' ? '+' : '';

	/* now remove all non-numeric */
	$str =(int)(preg_replace("/[^0-9]/","",$str));

	// simple sanity check for min and max
	$num_length = strlen($str);
	if($num_length < 7 || $num_length > 15 ){
		return '';
	}

	/* prefix with + again if set */
	$str = ''.$prefix . $str.'';


return $str;
}
/*****************************************************
* Validates css declarations #a-zA-Z0-9% no spaces or commas etc
* @str the input to check
******************************************************/
function wppizza_validate_simple_css($str){
	$str=(preg_replace("/[^a-zA-Z0-9#%]/","",$str));
	$str=strtolower($str);
	return $str;
}
/*****************************************************
* Validates boolean
* @str the input to check
******************************************************/
function wppizza_validate_boolean($inp = null){
	$bool=filter_var($inp, FILTER_VALIDATE_BOOLEAN);
return $bool;
}
/*****************************************************
* Validates url
* @str the input to check
******************************************************/
function wppizza_validate_url($inp){
	$url=filter_var($inp, FILTER_VALIDATE_URL);
return $url;
}
/*****************************************************
* Validates latitude, longitude coordinates
* @since 3.13.3
* @str
* @type false to validate combined lat lng separated by comma
* else lat or lng to validate lat or lng
******************************************************/
function wppizza_validate_latlng($inp, $type = false){


	/* sanitise value somewhat to start off with*/
	$inp = preg_replace("/[^0-9-,.]/","",$inp);

	/* allow for lat lng in one comma separated str */
	if($type === false){

		$xPlode = explode(',',$inp);

		if(count($xPlode) != 2){
			return '';
		}
		if(!wppizza_is_valid_latitude($xPlode[0])){
			return '';
		}
		if(!wppizza_is_valid_longitude($xPlode[1])){
			return '';
		}
		$lat_lng = implode(',',$xPlode);
	return $lat_lng;
	}
	/*
		lat
	*/
	elseif($type === 'lat'){
		$coordinates = wppizza_is_valid_latitude($inp) ? $inp : '' ;
		return $coordinates;
	}
	/*
		lng
	*/
	elseif($type === 'lng'){
		$coordinates = wppizza_is_valid_longitude($inp) ? $inp : '' ;
		return $coordinates;
	}

/*
	none of the above
*/
return '';
}
/*****************************************************
* Validates latitude
* @since 3.13.3
* @param latitude
* @return bool
******************************************************/
function wppizza_is_valid_latitude($latitude){
	if (preg_match("/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d{1,20}$/", $latitude)) {
		return true;
	} else {
		return false;
	}
}
/*****************************************************
* Validates longitude
* @since 3.13.3
* @param longitude
* @return bool
******************************************************/
function wppizza_is_valid_longitude($longitude){
	if(preg_match("/^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d{1,20}$/", $longitude)) {
		return true;
	} else {
		return false;
	}
}

/*****************************************************
* Validates html element id's. reasonably loose,
* but gets rid of completely invalids
* @str the input to check
******************************************************/
function wppizza_validate_element_id($str){
	$str=preg_replace('/[^a-zA-Z|0-9\-_.]*/','',$str);/*first get  rid of all chrs that should definitely not be in there*/
return $str;
}
/*****************************************************
* Validates float [no negatives]
* @str the input to check, @round [int] to round
* save as float, regardless of what seperators/locale were used
* (also mainly to make it work with legacy versions of plugin)
******************************************************/
function wppizza_validate_float_only($str, $round='', $omitDecimals=false){
	$str=preg_replace('/[^0-9.,]*/','',$str);/*first get  rid of all chrs that should definitely not be in there*/
	$str=str_replace(array('.',','),'#',$str);/*make string we can explode*/
	$floatArray=explode('#',$str);/*explode so we know the last bit might be decimals*/
	$exLength=count($floatArray);

	/**************************************************************************************************
		a bit of a hack to find out if the last part IS actually decimals (as we might be omitting them)
		if it is not decimals (ie 1.300 or 1,300 depending on locale), it will be strlen==3
	**************************************************************************************************/
	if($exLength>0 && strlen($floatArray[$exLength-1])==3){
		$omitDecimals=true;
	}

	$str='';
	for($i=0;$i<$exLength;$i++){
		if($i>0 && $i==($exLength-1) && !$omitDecimals){
		$str.='.';//add decimal point if needed
		}
		$str.=''.$floatArray[$i].'';
	}
	$str=(float)$str;/* cast to float */
	if(is_int($round)){$str=round($str,$round);}
return $str;
}

/*** currently this is just a fix to deal with percentages/sales tax that have 3 decimals as otherwsie it would be recognised with the function above as being 8625% instead of 8.625% ***/
/*** i need to write something else to take care of all these scenarios (i.e also when people choose to not display decimals etc)***/
/*** for now , the below will have to do for the salestax**/
function wppizza_validate_float_pc($str,$round=5){
	$str=preg_replace('/[^0-9.,]*/','',$str);/*first get  rid of all chrs that should definitely not be in there*/
	$str=str_replace(array('.',','),'#',$str);/*make string we can explode*/
	$floatArray=explode('#',$str);/*explode so we know the last bit might be decimals*/
	$exLength=count($floatArray);
	$str='';
	for($i=0;$i<$exLength;$i++){
		if($i>0 && $i==($exLength-1)){
			$str.='.';//add decimal point if needed
		}
		$str.=''.$floatArray[$i].'';
	}
	$str=(float)$str;/* cast to float */
	if(is_int($round)){$str=round($str,$round);}
return $str;
}
/*****************************************************
* Validates a-zA_Z
* @str the input to check, @limit to limit length of output
******************************************************/
function wppizza_validate_letters_only($str,$limit=''){
	$str=preg_replace("/[^a-zA-Z]/","",$str);
	if($limit>0){$str=substr($str,0,$limit);}
return $str;
}
/*****************************************************
* Validates a-zA-Z0-9\-_
* @parameter $str  string -> the input to check
* @return string
******************************************************/
function wppizza_validate_alpha_only($str, $allow_whitespace = false){
	
	//avoid some potential php notices
	if(empty($str)){
		return $str;
	}
	
	if(empty($allow_whitespace)){
		$str=(preg_replace("/[^a-zA-Z0-9\-_]/","",$str));
	}else{
		/*allow whitespaces, but trim*/
		$str=trim(preg_replace("/[^a-zA-Z0-9\-_ ]/","",$str));
	}
return $str;
}
/*****************************************************
* Alias of wppizza_validate_alpha_only , allowing whitespace
* @since 3.17.2
* @return string
******************************************************/
function wppizza_validate_alpha_only_ws($str){
	$str = wppizza_validate_alpha_only($str, true);
return $str;
}
/*****************************************************
* Validates a-zA-Z0-9\-_:|;,
* extended character set of wppizza_validate_alpha_only
* (maybe to be added to in the future)
* @parameter $str  string -> the input to check
* @return string
******************************************************/
function wppizza_validate_alpha_extend($str, $allow_whitespace = false){
	if(empty($allow_whitespace)){
		$str=(preg_replace("/[^a-zA-Z0-9\-_,:|#~@';]/","",$str));
	}else{
		/*allow whitespaces, but trim*/
		$str=trim(preg_replace("/[^a-zA-Z0-9\-_:|; ]/","",$str));
	}
return $str;
}
/*****************************************************
* Alias of wppizza_validate_alpha_extend , allowing whitespace
* @since 3.17.2
* @return string
******************************************************/
function wppizza_validate_alpha_extend_ws($str){
	$str = wppizza_validate_alpha_extend($str, true);
return $str;
}
/*****************************************************
* Validates a-zA-Z0-9 and cast to lowercase
* @str the input to check, allow for whitespaces
* @param str
* @param array
* @return str
* @since 3.9
******************************************************/
function wppizza_latin_lowercase($str, $args = false){

	/*allow whitespaces, but trim*/
	if(isset($args['allow_whitespace'])){
		$str=trim(preg_replace("/[^a-zA-Z0-9 ]/","",$str));
	}else{
		$str=preg_replace("/[^a-zA-Z0-9]/","",$str);
	}
	/* force lowercase */
	$str=strtolower($str);

return $str;
}

/*****************************************************
* Validates a-zA-Z0-9 and cast to lowercase
* @str the input to check, allow for whitespaces
* @param str
* @param array
* @return str
* @since 3.9
******************************************************/
function wppizza_latin_uppercase($str, $args = false){
	/*allow whitespaces, but trim*/
	if(isset($args['allow_whitespace'])){
		$str=trim(preg_replace("/[^a-zA-Z0-9 ]/","",$str));
	}else{
		$str=preg_replace("/[^a-zA-Z0-9]/","",$str);
	}

	/* force uppercase */
	$str=strtoupper($str);

return $str;
}

/*****************************************************
* Validates a-zA-Z0-9\-_ with additional args
* allowing for whitespace, truncating to max length
* @parameter $str  string -> the input to check
* @return string
******************************************************/
function wppizza_alpha_only($str, $args = false){

	/*allow whitespaces, but trim*/
	if(isset($args['allow_whitespace'])){
		$str=trim(preg_replace("/[^a-zA-Z0-9\-_ ]/","",$str));
	}else{
		$str=(preg_replace("/[^a-zA-Z0-9\-_]/","",$str));
	}

	if(isset($args['max_length'])){
		$str=substr($str, 0, (int)$args['max_length']);
	}

return $str;
}

/*****************************************************
* alias of wppizza_alpha_only with arguments set
* @since 3.7
* @parameter $str  string -> the input to check
* @return string
******************************************************/
function wppizza_sanitize_hash($hash){
	$hash = wppizza_alpha_only($hash);
return $hash;
}
/*****************************************************
* sanitise get variables 
* @since 3.17.2
* @parameter array
* @return void
******************************************************/
function wppizza_sanitize_get_vars($array){
	$processed = 0;
	foreach($array as $variable_name => $sanitise_function){
		if(function_exists(''.$sanitise_function.'') && isset($_GET[$variable_name])){
			$_GET[$variable_name] = $sanitise_function($_GET[$variable_name]);	
			$processed ++; 
		}
	}
return $processed;
}
/*****************************************************
* simple compare for title in order vs. title of post.
* Validates a-zA-Z0-9
* @parameter $str  string -> the input to check
* @return string
******************************************************/
function wppizza_compare_title($str){
	// decode entities first
	$str = wppizza_decode_entities($str);
	$str=(preg_replace("/[^a-z0-9]/","",strtolower($str)));
return $str;
}

/*****************************************************
* Validate and returns 24 hour time (02:55)
* @str the input to check
******************************************************/
function wppizza_validate_24hourtime($str){
	$t=explode(":",$str);
	/**first make them abs int*/
	$hr=abs((int)$t[0]);
	$min=abs((int)$t[1]);
	/*make sure we dont have an hour above 24*/
	if($hr>24){$hr=23;}
	/*make sure we dont have a minute above 59*/
	if($min>59){$min=59;}
	/**output format**/
	$str=''.sprintf('%02d',$hr).':'.sprintf('%02d',$min).'';
return $str;
}
/*****************************************************
* Validate and returns a date according to format
* @str the input to check, @format what date format
******************************************************/
function wppizza_validate_date($str,$format){
	$str=date($format,strtotime($str));
return $str;
}
/*****************************************************
* return comma separated string as array
* @str the input to check
* @since 3.16 the values get trimmed as well
******************************************************/
function wppizza_strtoarray($str){
	$str=explode(",",$str);
	$array=array();
	foreach($str as $s){
		$array[] = trim(wppizza_validate_string($s));
	}
return $array;
}
/*****************************************************
* return array
* @arr the input array to validate
* @validation_function_value the function to use for validating each arr item
* @validation_function_key the function to use for validating each arr key (if set)
******************************************************/
function wppizza_validate_array($arr = array(), $validation_function_value = 'wppizza_validate_alpha_only', $validation_function_key = false){

	$array = array();

	/**
		if $arr is in fact not an array, but a str
		we assume it's comma separated and explode it into array
	**/
	if(!is_array($arr) && trim($arr) != '' ){
		$arr = explode(',',$arr);
	}

	if(is_array($arr)){
	foreach($arr as $k=>$s){
		if($validation_function_key){/*set a different validation method for the key */
			$validated_key=$validation_function_key($k);
		}else{
			$validated_key=$validation_function_value($k);
		}
		$array[''.$validated_key.'']=''.$validation_function_value($s).'';
	}}

return $array;
}
/*****************************************************
* check and return comma separated string of EMAILS as array
* @str the input to check, emails split by comma
******************************************************/
function wppizza_validate_email_array($str){
	$str=explode(",",$str);
	$email=array();
	foreach($str as $s){
		$s=trim($s);
		if(wppizza_validEmail($s)){
			$email[]=$s;
		}
	}
return array_unique($email);
}
/*****************************************************
* check format of email
* @email the email to check
******************************************************/
function wppizza_validEmail($email){
   $isValid = true;
   if(empty($email)){
   	return false;
   }
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex){
      $isValid = false;
   }else{
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64){
         $isValid = false;	         // local part length exceeded
      }
      else if ($domainLen < 1 || $domainLen > 255){
         $isValid = false;	         // domain part length exceeded
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.'){
         $isValid = false;	         // local part starts or ends with '.'
      }
      else if (preg_match('/\\.\\./', $local)){
         $isValid = false;	         // local part has two consecutive dots
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)){
         $isValid = false;	         // character not valid in domain part
      }
      else if (preg_match('/\\.\\./', $domain)){
         $isValid = false;	         // domain part has two consecutive dots
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))){
         // character not valid in local part unless
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))){
            $isValid = false;
         }
      }
   }
return $isValid;
}
/*****************************************************
* sanitize all costomer order page post vars
* returns serialized value no html etc
******************************************************/
/** set serialize to true to serialize the resulting array to store somewhere */
function wppizza_sanitize_post_vars($arr, $serialize = false){
	if(is_array($arr)){
		array_walk_recursive($arr, 'wppizza_sanitize_post_vars_recursive');
	}
	if($serialize){
		return esc_sql(serialize($arr));
	}else{
		return $arr;
	}
}

/* recursively sanitize array above */
function wppizza_sanitize_post_vars_recursive(&$str) {
	//avoid some potential php notices/warning
	if($str === null || $str === false  || trim($str) == ''){
		$str = '';
	}else{
		$str = trim(stripslashes($str));
		$str = wppizza_email_decode_entities($str);
		$str = htmlentities($str, ENT_QUOTES, mb_internal_encoding());
		$str = wp_kses($str,array());//kses should be last here or the & replace will have been double escaped so to speak
		$str = str_replace('&amp;','&', $str);// allow &amp; to be &
	}
}

/******************************************************
	sanitize a posted string and - optionally -
	allow some or all html.
	set $kses array to also allow certain tags if !html
******************************************************/
function wppizza_sanitize_posted_var($str, $html = false,  $kses = array()){
	//avoid some potential php notices/warning
	if($str === null || $str === false  || trim($str) == ''){
		$str = '';
	}else{
		$str = trim(stripslashes($str));
		$str = wppizza_email_decode_entities($str);
		$str = ( false === $html ) ? wp_kses($str, $kses) : $str ;
		$str = ( false === $html ) ? htmlentities($str, ENT_QUOTES, mb_internal_encoding()) : $str ;
	}
return $str;
}

/**********************************************************************
*	sanitise any values added into a textbox (input or textarea)
*
*	@since 3.12.14
*	@param str
*	@param int	(max string length - 512 default should really be enough to add some info to an order)
*	@return sanitised str
**********************************************************************/
function wppizza_sanitise_textbox($str, $max_char = 512){

	// we want to keep linebreaks - sanitize_text_field will strip them and sanitize_textarea_field is only available since wp 4.7!
	// so we simple explode and implode
	$xp = explode(PHP_EOL, $str);
	$sanitized = array();
	foreach($xp as $i=>$part){
		// sanitise the wordpress way
		$is_sane = trim(sanitize_text_field($part));

		if(!empty($is_sane)){
			$sanitized[] = $is_sane;
		}
	}

	//implode with linebreaks
	$str = implode(PHP_EOL, $sanitized);


	// max of maxchar
	$str = substr($str, 0, $max_char);

return $str;
}

/**********************************************************************
*	sanitise localization strings that use %s (sprintf) for variables
*	to make sure not more that the allowed number of placeholders are used (or we'd gat fatal errors)
*
*	@since 3.16.5
*	@param str
*	@param int	maximum number sprintf's (%s) placeholders allowed in string
*	@param placeholder pattern we are looking for (i.e %s)
*	@return sanitised str
**********************************************************************/
function wppizza_sanitise_forsprintf($string, $max_occurances, $pattern = '/%s/') {

	/*
		spare us the rest if there's only the allowed max number of % here anyway
		or the string is even empty
	*/
	if(trim($string) == ''){
		return '';
	}
	$x = explode('%', $string);
	if( count($x) <= ($max_occurances + 1) ){
		return $string;
	}

	/*
		somethimg very very unlikely to be used in the original string as a placeholder that has no % in it
	*/
	$placeholder = '['.md5(WPPIZZA_SLUG).']';

	//replace the allowed number of occurrencas with a placeholder
	$string = preg_replace_callback($pattern,

		function($matches) use ($placeholder, $string, $max_occurances) {
			static $s = 0; $s++;
			return ($s > $max_occurances) ? $matches[0] : $placeholder ;
		},

		$string
	);
	// replace all %s that are left
	$string = str_ireplace('%s', '' , $string);
	// replace all % that are left
	$string = str_ireplace('%', '' , $string);
	// put the all %s that are allowed back in
	$string = str_ireplace($placeholder, '%s' , $string);

return $string;
}
/**********************************************************************
*	sanitise meta keys
*	to lowercase and sensible chars only
*	@since 3.18
*	@param str
*	@return sanitised str
**********************************************************************/
function wppizza_sanitize_meta_key($meta_key){
	$meta_key = strtolower(wppizza_validate_alpha_only($meta_key));
return $meta_key;
}
/**********************************************************************
*	sanitise email, tx id, order id values (typically from _GET['s'] in orderhistory
*
*	@since 3.18.14
*	@param str
*	@return sanitised str
**********************************************************************/
function wppizza_sanitise_email_tx_id($str){
	if($str == '' ){return '' ;}
	$str = wppizza_sanitize_posted_var(sanitize_text_field($str));//basic
	$str = html_entity_decode($str);//decode
	$str = str_replace(array('\'', '"', '<', '>', '\\', '(', ')', '{', '}', '}', ' ' ), '', $str);//some basic strip quotes etc , just for fun (maybe one day a regex)
	$str = htmlspecialchars($str);//and just to be sure
return $str;	
}
?>