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
			, mapUnit = '<?=$map_unit?>'
			, mapProvider = '<?=$map_provider?>';
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
            <li role="presentation" class="dropdown">
              <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                Templates <span class="caret"></span>
              </a>
              <ul class="dropdown-menu" role="menu">
                <li ng-repeat="t in templates track by $index">
                  <a ng-click="changeTemplate(t.name)">{{t.name}} <i class="fa fa-check fa-lg" ng-show="map_provider==t.name"></i></a>
                </li>
              </ul>
            </li>
	        </ul>
    	   </div>          
          <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="https://twitter.com/intent/tweet?text=This email traveled {{distance}}. <?='http://api.mailhops.com'.$_SERVER['REQUEST_URI']?> @MailHops"><i class="fa fa-lg fa-twitter"></i></a></li>
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
          	<li class="hop" ng-repeat="r in route track by $index" ng-class="{'active':r.focus}"><a ng-click="showMarker(r.hopnum)">
          			<strong>{{r.hopnum}}</strong> <img src="/images/hop.svg" width="20">
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
          	<leaflet width="100%" height="400px" center="boulder" markers="markers"></leaflet>
          </div>
  	 </div>
  	</div>
  </div>

  <script type="text/javascript" async src="//platform.twitter.com/widgets.js"></script>   
	<script src="/node_modules/jquery/dist/jquery.min.js"></script>
	<script src="/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/node_modules/leaflet/dist/leaflet.js"></script>
	<script src="/node_modules/leaflet-providers/leaflet-providers.js"></script>
	<script src="/node_modules/angular-leaflet-directive/dist/angular-leaflet-directive.min.js"></script>
	<script src="js/L.Geodesic.js"></script>
	<script src="js/app.js"></script>	
</body>
</html>
