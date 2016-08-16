<?php

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");

function sendMsg($id, $msg) {
  echo "id: $id\n";
  echo "data: $msg\n";
  echo "retry: 3000\n";
  echo PHP_EOL;
  ob_flush();
  flush();
}

$serverTime = time();
$json_map = '';
$mailhops = new MailHops();
$since = date('U')-3;

try{
	$json_map = $mailhops->getTraffic($since);
}
catch(Exception $ex){
	error_log($ex->getMessage());
}
sendMsg($serverTime,$json_map);

?>
