<?php
if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$json_map = '';
$account = null;

if(!isset($_GET['r'])){
  header('HTTP/1.1 400 Bad Request', true, 400);
	$json_map = array('error'=>array('code'=>400,'message'=>'Missing route parameter'));
} else {
  $mailhops = new MailHops();
  $json_map = $mailhops->getRoute();
  if(MError::hasError()){
    header('HTTP/1.1 400 Bad Request', true, 400);
    $json_map = array('error'=>array('code'=>400,'message'=>MError::getError()));
  }
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . json_encode($json_map) . ');';
else
	echo json_encode($json_map);
?>
