<?php

if (!$loader = @include __DIR__ . '/../../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

if(isset($_SERVER['HTTP_REFERER']) &&
  (strstr($_SERVER['HTTP_REFERER'],'mailhops.com') ||
    strstr($_SERVER['HTTP_REFERER'],'localhost:8081')
  )){
  header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_REFERER']);
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
  $traffic = '';
  $mailhops = new MailHops();
  $since = date('U')-3;

  try {
  	$traffic = $mailhops->getTraffic($since);
  } catch(Exception $ex){
  	error_log($ex->getMessage());
  }
  sendMsg($serverTime,json_encode($traffic));
}

?>
