<?php
	if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
	    die('Project dependencies missing.  Run composer.');
	}
?>
<!DOCTYPE html>
<html ng-app="mailHops">
    <head>
        <meta charset="utf-8">
        <title>MailHops API real time map of events</title>
				<script src="//cdn.jsdelivr.net/lodash/4.11.2/lodash.min.js"></script>
        <script src="//d3js.org/d3.v3.min.js"></script>
        <script src="//d3js.org/topojson.v1.min.js"></script>
        <script src='/js/app.js'></script>
				<link rel="stylesheet" href="/css/style.css">
    </head>
    <body ng-controller="mainController">
      <div id="map"></div>
      <script>

			let width = 960,
					height = 500;

			let projection = d3.geo.albersUsa()
														 .scale(1000)
														 .translate([width / 2, height / 2]);

			let path = d3.geo.path()
									 .projection(projection);

			d3_draw(d3.select('#map'));

			if (!!window.EventSource) {
				var source = new EventSource('/v2/hops');
				var hops;
				source.addEventListener('message', function(e) {
					hops = JSON.parse(e.data);
					if(!!hops.route){
						 hops = hops.route.filter(function(h){
							return (!!h.lat && !!h.lng);
						 });
						 d3_hop(d3.select('#map'), hops);
					}
				}, false);
			}      
      </script>
    </body>
</html>
