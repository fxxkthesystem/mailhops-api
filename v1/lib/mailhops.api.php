<?php
/** Autoloads PHP mailhops API classes
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0
 * 
 * 
 */
function __autoload($name){
	$filename = realpath(sprintf("%s/%s.php", dirname(__FILE__), $name));
	
	if(file_exists($filename) && is_file($filename)){
		require_once $filename;
	}
	else if(stripos($name, 'Zend') === false){
		throw new Exception("File '".$filename."' not found!");
	}

	if(stripos($name, 'Zend') === false && !class_exists($name, false) && !interface_exists($name, false)){
		throw new Exception("Class '".$name."' not found!");
	}	
}


