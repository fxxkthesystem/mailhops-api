<?php

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$json_map = '';

$mailhops = new MailHops(!empty($_GET['since'])?$_GET['since']:'');
$traffic = $mailhops->getTraffic();
if(MError::hasError()){
  header('HTTP/1.1 400 Bad Request', true, 400);
  $json_map = json_encode(array('error'=>array('code'=>400,'message'=>MError::getError())));
} else {
  $json_map = json_encode(array(
    'meta'=>array('code'=>200)
    ,'traffic'=>$traffic
  ));
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . $json_map . ');';
else
	echo $json_map;
?>
