<?php
/** Error Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

class MError
{

	private static $error=null;

	public static function setError($error,$concat_errors=false){

    if($concat_errors && !empty(self::$error))
  		self::$error.="\n".$error;
  	else
  		self::$error=$error;
	}

	public static function getError(){
		return self::$error;
	}

	public static function clearError(){
		self::$error=null;
	}

	public static function hasError($contains=''){
		if(!empty(self::$error) && !empty($contains) && strstr(self::$error,$contains))
			return true;
		else if(!empty(self::$error))
			return true;
		else
			return false;
	}
}
?>
