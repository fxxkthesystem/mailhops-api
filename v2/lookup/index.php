<?php
if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$json_map = '';
$account = null;

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
  if(class_exists('Account')){
    $account = new Account();

  }

  if(MError::hasError('Invalid API Key')){
    header('HTTP/1.1 401 Unauthorized', true, 401);
    $json_map = json_encode(array('error'=>array('code'=>401,'message'=>MError::getError())));
  } else if(MError::hasError('Rate Limit')){
    if($account && $account->getRateLimit() == 100){
      header('HTTP/1.1 402 Payment Required', true, 402);
      $json_map = json_encode(array('error'=>array('code'=>402,'message'=>MError::getError())));
    } else {
      header('HTTP/1.1 429 Too Many Requests', true, 429);
      $json_map = json_encode(array('error'=>array('code'=>429,'message'=>MError::getError())));
    }
  } else if(MError::hasError()){
    header('HTTP/1.1 400 Bad Request', true, 400);
    $json_map = json_encode(array('error'=>array('code'=>400,'message'=>MError::getError())));
  }
  else {
    $mailhops = new MailHops($account);
    $json_map = $mailhops->getRoute();
    if(MError::hasError()){
      header('HTTP/1.1 400 Bad Request', true, 400);
      $json_map = json_encode(array('error'=>array('code'=>400,'message'=>MError::getError())));
    }
  }
}

if(!empty($account)){
  header("X-Rate-Limit-Limit: ".$account->getRateLimit());
  header("X-Rate-Limit-Remaining: ".$account->getRateRemaining());
  header("X-Rate-Limit-Reset: ".$account->getRateReset());
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . $json_map . ');';
else
	echo $json_map;
?>
