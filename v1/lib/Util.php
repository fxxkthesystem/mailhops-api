<?php
/** Utility Class
 *
 * @package	mailhops
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0
 */
class Util {
	
	public static function toString($var){
		return ($var && trim($var))?strval($var):'';
	}
	
	public static function toInteger($var){
		return ($var && trim($var))?intval($var):0;
	}

	public static function toFloat($var){
		return ($var && trim($var))?floatval($var):0.0;
	}
	
	public static function toBoolean($var){
		switch(Util::toString($var)){
			case 'true':
			case 't':
			case '1':	
					return true;
			case '':
			case 'false':
			case 'f':
			case '0':
					return false;
			default:
				return !!intval($var);
		}
	}
			
	public function getVersion($version){
	
		$version = end(explode(' ',$version));
		return str_replace('.','',$version);		
	
	}
}