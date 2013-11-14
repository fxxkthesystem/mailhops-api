<?php include '../lib/load.php';
$mailhops = new MailHops();
$mailhops->setReverseHost(true);

$map_type=isset($_GET['map'])?$_GET['map']:'goog';
$map_type=isset($_GET['m'])?$_GET['m']:$map_type;

$map_unit=isset($_GET['unit'])?$_GET['unit']:'mi';
$map_unit=isset($_GET['u'])?$_GET['u']:$map_unit;

$show_weather=isset($_GET['weather'])?(string)Util::toBoolean($_GET['weather']):'false';
$show_weather=isset($_GET['w'])?$_GET['w']:$show_weather;

?>
<!DOCTYPE html>   
<head>
	<meta charset="utf-8"> 
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>MailHops | Route Map</title>
	<meta name="description" content="MailHops.com maps the hops that a message took to get to you.">
	<meta name="author" content="MailHops.com">
	<meta name="viewport" content="width=device-width; initial-scale=1.0">
	<!-- !CSS -->
	<link rel="stylesheet" href="style.css?v=2">
	<? if($map_type=='bing'){?>
		<script charset="UTF-8" type="text/javascript" src="//ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<? } else {?>
		<script type="text/javascript" src="//maps.google.com/maps/api/js?sensor=false"></script>
	<? } ?>
	<script>
		//json encoded mail route
		var mailRoute = <?=$mailhops->getRoute();?>;
		var showWeather = <?=$show_weather;?>;
	</script>
</head>
<!-- !Body -->
<body> 
	<div id="route"><ul></ul></div>
	<div id="map"></div>	
	<div id="milage" data-unit="<?=$map_unit?>"></div>
	<? if($map_type=='bing'){?>
	<div id="infoBox">
        <div id="infoboxText">
            <b id="infoboxTitle"></b>
            <img id="infoboxClose" src="close.gif" alt="close" onclick="closeInfoBox()"/>
            <a id="infoboxDescription"></a>
        </div>
      </div>
    <? } ?>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<? if($map_type=='bing'){?>
		<script type="text/javascript" src="/v1/map/bing.min.js?v=1"></script>
	<? } else {?>
		<script type="text/javascript" src="/v1/map/goog.min.js?v=1"></script>
	<? } ?>
	<script>
	jQuery(document).ready(function($) {
		
		initMap(mailRoute);
			
	});
	</script>
	
</body>
</html>
