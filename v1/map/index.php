<?php 

if (!$loader = @include __DIR__ . '/../../vendor/autoload.php') {
    die('Project dependencies missing');
}

$mailhops = new MailHops();
$mailhops->setReverseHost(true);

$map_type=isset($_GET['map'])?$_GET['map']:'goog';
$map_type=isset($_GET['m'])?$_GET['m']:$map_type;

$map_unit=isset($_GET['unit'])?$_GET['unit']:'mi';
$map_unit=isset($_GET['u'])?$_GET['u']:$map_unit;

$map_provider=isset($_GET['mp'])?$_GET['mp']:'Stamen.Watercolor';

$show_weather=isset($_GET['weather'])?(string)Util::toBoolean($_GET['weather']):'false';
$show_weather=isset($_GET['w'])?$_GET['w']:$show_weather;

?>
<!DOCTYPE html>   
<html ng-app="mailHops">
<head>
	<meta charset="utf-8"> 
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>MailHops | Route Map</title>
	<meta name="description" content="MailHops.com maps the hops that a message took to get to you.">
	<meta name="author" content="MailHops.com">
	<link rel="stylesheet" href="/node_modules/bootstrap/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/node_modules/bootstrap/dist/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="dashboard.css">	
	<link rel="stylesheet" href="/node_modules/leaflet/dist/leaflet.css">
	<link rel="stylesheet" href="/node_modules/font-awesome/css/font-awesome.min.css">

	<script> 
		var mailRoute = <?=$mailhops->getRoute()?>
			, map_unit = '<?=$map_unit?>'
			, map_provider = '<?=$map_provider?>';
	</script>	
	<script src="/node_modules/angular/angular.min.js"></script>
</head>
<!-- !Body -->
<body ng-controller="mainController"> 
	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">          
          <span class="navbar-brand"><a><img src="/images/mailhops-logo.svg" width="32"> MailHops</a></span>
        </div>
        <div>
	      	<ul class="nav navbar-nav">
	        	<li class="active"><a>This message traveled {{distance}}</a></li>
	        </ul>
    	</div>          
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <div id="route" class="col-sm-3 col-md-2 sidebar">
          <ul class="nav nav-sidebar hops">  
          	<li class="active head">
          		<a>{{route.length}} hops</a>
          	</li>
          	<li ng-repeat="r in route"><a ng-click="showMarker(r.hopnum)">
          			{{r.hopnum}} <img src="/images/mailhops-logo.svg" width="20">
          			<div ng-if="r.private">
          				<span>{{r.ip}}</span>
          			</div>
          			<div ng-if="!r.private">
          				<span>{{r.ip}} <i class="fa fa-bomb" ng-if="r.dnsbl"></i></span>
          				<span ng-if="r.countryName"><br/><img ng-src="{{r.flag}}"/> {{r.countryName}} ({{r.countryCode}})</span>
          				<span ng-if="r.city"><br/>{{r.city}}, {{r.state}}</span>
          				<span ng-if="r.host"><br/>{{r.host}}</span>
          				<span ng-if="r.w3w"><br/>{{r.w3w.words.join('.')}}</span>
          			</div>
          		</a>
          	</li>          
          </ul>
      </div>
      <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <div class="row placeholders">
          	<leaflet width="100%" height="400px" markers="markers"></leaflet>
          </div>
  	 </div>
  	</div>
  </div>
	
	<? if($map_type=='bing'){?>
	<div id="infoBox">
        <div id="infoboxText">
            <b id="infoboxTitle"></b>
            <img id="infoboxClose" src="close.gif" alt="close" onclick="closeInfoBox()"/>
            <a id="infoboxDescription"></a>
        </div>
      </div>
    <? } ?>

	<script src="/node_modules/jquery/dist/jquery.min.js"></script>
	<script src="/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/node_modules/leaflet/dist/leaflet.js"></script>
	<script src="/node_modules/leaflet-providers/leaflet-providers.js"></script>
	<script src="/node_modules/angular-leaflet-directive/dist/angular-leaflet-directive.min.js"></script>
	<script src="js/L.Geodesic.js"></script>
	<script src="js/app.js"></script>
	<script type="text/javascript">
	   $(document).on('click', '.nav-sidebar li', function() {
	       $(".nav-sidebar li").removeClass("active");
	       $(this).addClass("active");
	   });
	</script>
</body>
</html>
