<?php include '../lib/load.php';

$maintenance = '';
$json_map = '';

if(!empty($maintenance)){
	$json_map = json_encode(array(
				'meta'=>array(
					'code'=>410
					,'host'=>$_SERVER['SERVER_NAME']),
				'error'=>array('message'=>$maintenance)));
} else if(isset($_GET['watchmouse'])){
	$json_map = json_encode(array(
				'meta'=>array(
					'code'=>200
					,'message'=>'MailHops API Service Up'
					,'host'=>$_SERVER['SERVER_NAME']))
				);	
} else{

	if(!isset($_GET['route']) && !isset($_GET['r'])){
	
		$json_map = json_encode(array(
				'meta'=>array(
					'code'=>400
					,'host'=>$_SERVER['SERVER_NAME']),
				'error'=>array('message'=>'Missing route parameter')));
				
	} else {
		$mailhops = new MailHops();
		
		$json_map = json_encode(array(
						'meta'=>array(
							'code'=>500
							,'host'=>$_SERVER['SERVER_NAME']),
						'error'=>array('message'=>'Server Error')));
		
		try{
			$json_map = $mailhops->getRoute();
		}
		catch(Exception $ex){
			error_log($ex->getMessage());
		}
	}
}

header('Content-type: application/json');
	
if(isset($_GET['callback']))
	echo $_GET['callback'] . ' (' . $json_map . ');';	
else if(isset($_GET['tb']) || isset($_GET['pb']))
	echo $json_map;
else
	echo ' (' . $json_map . ')';	
?>