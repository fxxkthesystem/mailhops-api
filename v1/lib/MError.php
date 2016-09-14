<?php
/** Error Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0.0
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

	public static function hasError(){
		if(!empty(self::$error))
			return true;
		else
			return false;
	}
}
?>
