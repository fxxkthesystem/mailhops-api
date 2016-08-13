<!DOCTYPE html>
<html ng-app="mailHops">
    <head>
        <meta charset="utf-8">
        <title>MailHops API Traffic Map</title>
				<script src="//cdn.jsdelivr.net/lodash/4.11.2/lodash.min.js"></script>
        <script src="//d3js.org/d3.v3.min.js"></script>
        <script src="//d3js.org/topojson.v1.min.js"></script>
        <script src='/traffic/js/app.js'></script>
				<link rel="stylesheet" href="/traffic/css/style.css">
    </head>
    <body ng-controller="mainController">
      <div id="map"></div>
      <script>

			var width = 960,
					height = 500;

			var projection = d3.geo.albersUsa()
														 .scale(1000)
														 .translate([width / 2, height / 2]);

			var path = d3.geo.path()
									 .projection(projection);

			d3_draw(d3.select('#map'));

			if (!!window.EventSource) {
				var source = new EventSource('/v2/traffic');
				var traffic, hops, coords;
				source.addEventListener('message', function(e) {
					traffic = JSON.parse(e.data);
					if(!!traffic){
						_.each(traffic,function(hops){
						 	hops = hops.route.filter(function(h){
								return (!!h.lat && !!h.lng);
						 	});
              coords = hops.map(function(h){
								return [h.lng, h.lat];
						 	});
              d3_traffic(d3.select('#map'), hops, coords);
					 	});
					}
				}, false);
			}
      </script>
    </body>
</html>
