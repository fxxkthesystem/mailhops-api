<?php

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$json_map = '';

if(!isset($_GET['api_key'])){

	$json_map = json_encode(array(
			'meta'=>array(
				'code'=>400
				,'host'=>$_SERVER['SERVER_NAME']),
			'error'=>array('message'=>'Missing api_key parameter')));

} else {

	$json_map = json_encode(array(
					'meta'=>array(
						'code'=>500
						,'host'=>$_SERVER['SERVER_NAME']),
					'error'=>array('message'=>'Server Error')));

	try{
    $account = new Account();
    if(($user = $account->isValidAPIKey($_GET['api_key'])) == false){
      $json_map = json_encode(array(
    				'meta'=>array('code'=>400
              ,'message'=>'Invalid API Key')
          ));
    } else {
      $json_map = json_encode(array(
    				'meta'=>array('code'=>200)
            ,'account'=>$user
          ));
    }
	}
	catch(Exception $ex){
		error_log($ex->getMessage());
	}
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . $json_map . ');';
else
	echo $json_map;
?>
