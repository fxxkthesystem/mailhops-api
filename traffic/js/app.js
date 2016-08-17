angular.module('mailHops',['ui.router'])
.config(function($stateProvider, $urlRouterProvider, $locationProvider, $logProvider) {

  $locationProvider.html5Mode(true);

  $stateProvider
    .state('world', {
      url: '/',
      controller: 'mainController',
      templateUrl: 'views/world.html'
    })
    .state('us', {
      url: '/us',
      controller: 'mainController',
      templateUrl: 'views/us.html'
    })
    .state('otherwise', {
      url: '*path',
      templateUrl: 'views/not-found.html'
    });
})
.filter('formatHost',function(){
  return function(input){
    if(input.indexOf('.') != -1){
      let host = input.split('.');
      input = host.length > 1 ? host[host.length-2]+'.'+host[host.length-1] : input;
    }
    return input;
  }
})
.controller('mainController', function ($scope, $filter, $state) {

  $scope.routes = [];
  $scope.units = 'mi';
  $scope.page = $state.current.name;
  $scope.event_source;

  var width = 960,
      height = 600;
  var path, projection;

  function d3_draw_US(el) {
      projection = d3.geo.albersUsa()
                           .scale(1000)
                           .translate([width / 2, height / 2]);

      path = d3.geo.path().projection(projection);

      let svg = el.append('svg')
                  .attr({
                    'width': width,
                    'height': height,
                    'viewBox': `0 0 ${width} ${height}`
                  });

      d3.json('/traffic/data/us-map.json', function(err, topology) {
        svg.append('path')
           .datum(topojson.feature(topology, topology.objects.land))
           .attr('d', path)
           .attr('class', 'land-boundary');

        svg.append('path')
           .datum(topojson.mesh(topology, topology.objects.states, (a, b) => a !== b))
           .attr('d', path)
           .attr('class', 'state-boundary');
      });
  }

  function d3_draw_WORLD(el) {
    projection = d3.geo.kavrayskiy7()
      .scale(200)
      .translate([width / 2, height / 2])
      .precision(.1);

    path = d3.geo.path().projection(projection);

    let svg = el.append('svg')
                  .attr({
                    'width': width,
                    'height': height,
                    'viewBox': `0 0 ${width} ${height}`
                  });

      svg.append("defs").append("path")
        .datum({type: "Sphere"})
        .attr("id", "sphere")
        .attr("d", path);

      d3.json('/traffic/data/world-50m.json', function(error, world) {
        if (error) throw error;

        var countries = topojson.feature(world, world.objects.countries).features,
            neighbors = topojson.neighbors(world.objects.countries.geometries);

        svg.selectAll(".country")
            .data(countries)
          .enter().insert("path", ".graticule")
            .attr("class", "country")
            .attr("d", path);

        svg.insert("path", ".graticule")
            .datum(topojson.mesh(world, world.objects.countries, function(a, b) { return a !== b; }))
            .attr("class", "boundary")
            .attr("d", path);
        });
  }

  function d3_traffic(el, hops, coords) {

      let colorScale = d3.scale.linear()
                         .domain(d3.extent(hops, c => c.ts))
                         .range([0, 0.8]);

      let hopSel = d3.select('svg').selectAll('circle')
                      .data(hops, (d) => d.lat)
                      .attr('fill-opacity', c => colorScale(c.ts));

      hopSel.enter().append('circle')
                     .attr({
                       'cx': (d) => projection([d.lng, d.lat])[0],
                       'cy': (d) => projection([d.lng, d.lat])[1]
                     });

      hopSel.attr({
               'r': 1,
               'opacity': 1e-6,
               'fill-opacity': 0.3,
               'fill': '#c4e3f3',
               'stroke': '#fff',
               'stroke-opacity': 1
             })
            .transition()
               .delay((d) => Math.floor((Math.random() * 1000) + 0))
               .duration(500)
               .ease('cubic-in-out')
               .attr({
                 'fill': '#c4e3f3',
                 'opacity': 1,
                 'r': 60,
                 'stroke-opacity': 0.4,
                 'stroke-width': '1px',
                 'stroke': '#361'
               })
             .each('end', function() {
                let dot = d3.select(this);

                dot.transition()
                    .duration(800)
                    .attr({
                      'fill': '#f2dede',
                      'opacity': 0.9,
                      'fill-opacity': 0.9,
                      'stroke-width': '1px',
                      'stroke': '#361',
                      'r': 2.2
                     })
                    .each('end', function() {
                      let point = d3.select(this);

                      point.transition()
                            .duration(5000)
                            .attr({
                              'fill': 'white'
                            })
                    });

                    hopSel.enter().append("path")
                                .datum({'type':'LineString','coordinates':coords})
                                .attr({
                                  'r': 1,
                                  'opacity': 1e-6,
                                  'fill-opacity': 0.3,
                                  'fill': '#c4e3f3',
                                  'stroke': '#fff',
                                  'stroke-opacity': 1
                                })
                                .attr({'d': path})
                                .style({
                                  'stroke': '#c4e3f3',
                                  'stroke-width': '1px'
                                })
                                .transition()
                                .delay((d) => Math.floor((Math.random() * 1000) + 0))
                                .duration(1000)
                                .ease('cubic-in-out')
                                .attr({
                                     'fill': '#c4e3f3',
                                     'opacity': 1,
                                     'r': 60,
                                     'stroke-opacity': 0.4,
                                     'stroke-width': '1px',
                                     'stroke': '#361'
                                   })
                                   .remove();

             });

  }

  if($scope.page == 'us')
    d3_draw_US(d3.select('#map'));
  else
    d3_draw_WORLD(d3.select('#map'));

  $scope.startMonitor = function(){
    //don't start more than once
    if(!!$scope.event_source && $scope.event_source.readyState<=1)
      return;

    if (!!window.EventSource && !$state.event_source) {
      $scope.event_source = new EventSource('/v2/traffic');
      var traffic, hops, coords, route;
      $scope.event_source.addEventListener('message', function(e) {
        traffic = JSON.parse(e.data);
        if(!!traffic){
          _.each(traffic,function(hops){
            route = hops.route.filter(function(h){
              return (!!h.lat && !!h.lng);
            });
            $scope.routes.push({
              id: e.lastEventId,
              number: route.length,
              distance: hops.distance,
              time: hops.time,
              date: $filter('date')(new Date(e.lastEventId*1000),'medium'),
              host: !!route[0].host ? route[0].host : '',
              firstHop: route[0],
              lastHop: route[route.length-1]
            });
            if($scope.routes.length > 10)
              $scope.routes.shift();
            $scope.$apply();
            coords = route.map(function(h){
              return [h.lng, h.lat];
            });
            d3_traffic(d3.select('#map'), route, coords);
          });
        }
      }, false);

      $scope.event_source.addEventListener('error', function(e) {
        $state.error=e;
      }, false);
    }
  };

  $scope.stopMonitor = function(){
    if(!!$scope.event_source && $scope.event_source.readyState<=1)
      $scope.event_source.close();
  };

  // Start the monitor
  $scope.startMonitor();

});
