<?php
if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$mailhops = new MailHops();
$mailhops->setReverseHost(true);

$map_unit = (!empty($_GET['u']) && in_array($_GET['u'], array('mi','km')))?$_GET['u']:'mi';
$fkey     = !empty($_GET['fkey'])?$_GET['fkey']:'';
$map_provider=isset($_GET['mp'])?$_GET['mp']:'';

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
  <link rel="stylesheet" href="/node_modules/leaflet/dist/leaflet.css">
  <link rel="stylesheet" href="/node_modules/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="/bower_components/weather-icons/css/weather-icons.min.css">
	<link rel="stylesheet" href="dashboard.css">
  <script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.8/angular.min.js"></script>
	<script>
		var mailRoute = <?php echo $mailhops->getRoute();?>
			, mapUnit = '<?php echo $map_unit;?>'
			, mapProvider = '<?php echo $map_provider;?>';
	</script>

</head>
<!-- !Body -->
<body ng-controller="mainController">

	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <span class="navbar-brand"><a><img src="/images/mailhops-32.png" width="32"> MailHops</a></span>
        </div>
        <div>
	      	<ul class="nav navbar-nav">
	        	<li class="active" ng-if="distance"><a>This message traveled {{distance | number:0}} <?php echo $map_unit;?></a></li>
            <li role="presentation" class="dropdown">
              <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                Map Templates <span class="caret"></span>
              </a>
              <ul class="dropdown-menu scrollable-menu" role="menu">
                <li ng-repeat="t in templates track by $index">
                  <a ng-click="changeTemplate(t.name)" ng-class="{'active':map_provider==t.name}">{{t.name}}</a>
                </li>
              </ul>
            </li>
	        </ul>
    	   </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <div id="route" class="col-sm-4 col-md-3 sidebar">
          <ul class="nav nav-sidebar hops">
          	<li class="active head">
          		<a>{{route.length}} hops</a>
          	</li>
          	<li class="hop" ng-repeat="r in route track by $index" ng-class="{'active':r.focus}" ng-click="showMarker(r.hopnum)">
          			<div class="btn btn-circle"><strong>{{r.hopnum}}</strong></div>
          			<div ng-if="r.private">
          				<span>Private<br/></span>
                  <span>{{r.ip}}</span>
                  <br/>
          			</div>
          			<div ng-if="!r.private">
                  <span ng-if="r.countryName"><img ng-src="{{r.flag}}"/> {{r.countryName}} ({{r.countryCode}})<br/></span>
                  <span ng-if="r.city">{{r.city}}<span ng-if="r.state">, {{r.state}}</span><br/></span>

                  <span ng-if="r.weather"><i class="{{r.weather.icon | weather}}"></i> {{r.weather.temp | number:0}}&deg; {{r.weather.summary}}<br/></span>

          				<span class="host" ng-click="open('','//www.mailhops.com/whois/'+r.ip,'whois')">{{r.ip}} <i class="fa fa-bomb" ng-if="r.dnsbl"></i><br/></span>
          				<span ng-if="r.host" class="host" ng-click="open('','//www.mailhops.com/whois/'+r.ip+'?from=app','whois')">{{r.host}}<br/></span>
          				<span ng-if="r.w3w" class="words" ng-click="open('',r.w3w.url,'what3words')">{{r.w3w.words.join('.')}}</span>
          			</div>

          	</li>
          </ul>
      </div>
      <div class="col-sm-8 col-sm-offset-4 col-md-9 col-md-offset-3 main">
          <div class="row ">
          	<leaflet center="boulder" markers="markers" width="100%" height="665px"></leaflet>
          </div>
  	 </div>
  	</div>
  </div>

  <script src="/node_modules/jquery/dist/jquery.min.js"></script>
	<script src="/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/node_modules/leaflet/dist/leaflet.js"></script>
	<script src="/node_modules/leaflet-providers/leaflet-providers.js"></script>
	<script src="/node_modules/angular-leaflet-directive/dist/angular-leaflet-directive.min.js"></script>
	<script src="/bower_components/angular-cookies/angular-cookies.min.js"></script>
  <script src="/bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
  <script src="/bower_components/Leaflet.Geodesic/src/L.Geodesic.js"></script>
  <script src="/bower_components/twitter-timeline-angularjs/src/twitter-timeline.js"></script>
	<script src="js/app.js?v=1.3.0"></script>
</body>
</html>
