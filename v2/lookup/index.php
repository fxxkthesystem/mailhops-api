<?php

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$json_map = '';

if(isset($_GET['healthcheck'])){
	$json_map = json_encode(array(
				'meta'=>array(
					'code'=>200
					,'message'=>'MailHops API Service Up'
					,'host'=>$_SERVER['SERVER_NAME']))
				);
} else if(!isset($_GET['r'])){
  header('HTTP/1.1 400 Bad Request', true, 400);
	$json_map = json_encode(array('error'=>array('code'=>400,'message'=>'Missing route parameter')));

} else {
	$mailhops = new MailHops();
  if(Error::hasError('Invalid API Key')){
    header('HTTP/1.1 401 Unauthorized', true, 401);
    $json_map = json_encode(array('error'=>array('code'=>401,'message'=>Error::getError())));
  }
  else if(Error::hasError()){
    header('HTTP/1.1 400 Bad Request', true, 400);
    $json_map = json_encode(array('error'=>array('code'=>400,'message'=>Error::getError())));
  }
  else {
    $json_map = $mailhops->getRoute();
    if(Error::hasError()){
      header('HTTP/1.1 400 Bad Request', true, 400);
      $json_map = json_encode(array('error'=>array('code'=>400,'message'=>Error::getError())));
    }
  }
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . $json_map . ');';
else
	echo $json_map;
?>
