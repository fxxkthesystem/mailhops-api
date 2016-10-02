angular.module('mailHops',['ui.router'])
.config(function($stateProvider, $urlRouterProvider, $locationProvider, $logProvider) {

  $locationProvider.html5Mode(true);

  $stateProvider
    .state('world', {
      url: '/',
      controller: 'mainController',
      templateUrl: 'views/main.html'
    })
    .state('us', {
      url: '/us',
      controller: 'mainController',
      templateUrl: 'views/main.html'
    })
    .state('map', {
      url: '/map',
      controller: 'mainController',
      templateUrl: 'views/map.html'
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
.factory('MailService', function($http, $q, $filter){
  var event_source;

  return {
    getEventSource: function(){
      return event_source;
    },
    setEventSource: function(e){
      return event_source = e;
    }
  }
})
.controller('mainController', function ($scope, $filter, $state, $timeout, MailService) {

  $scope.routes = [];
  $scope.units = 'mi';
  $scope.page = $state.current.name;

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

  $scope.monitor = function(force){
    if(MailService.getEventSource() && force){
      if(MailService.getEventSource().readyState == 2)
        $scope.startMonitor();
      else
        MailService.getEventSource().close();
    } else if(!MailService.getEventSource()){
      $scope.startMonitor();
    }
  };

  $scope.isMonitorRunning = function(){
    if(MailService.getEventSource() && MailService.getEventSource().readyState <= 1)
      return true;
    else
      return false;
  };

  $scope.startMonitor = function(){
    if (!!window.EventSource) {
      if(MailService.getEventSource())
        MailService.getEventSource().close();
      MailService.setEventSource( new EventSource('/v2/traffic') );
      MailService.getEventSource().addEventListener('message', function(e){ $scope.messageListener(e); }, false);
      MailService.getEventSource().addEventListener('error', function(e) {$state.error=e;}, false);
    }
  };

  $scope.messageListener = function(e){

      if(!e.data)
        return;

      traffic = JSON.parse(e.data);

      if(!!traffic){
        _.each(traffic,function(hops){
          route = _.filter(hops.route, function(h){
            return (!!h.lat && !!h.lng);
          });
          if(!route.length)
            return;
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
          if($scope.routes.length > 20)
            $scope.routes.shift();
          $scope.$apply();
          coords = route.map(function(h){
            return [h.lng, h.lat];
          });
          d3_traffic(d3.select('#map'), route, coords);
        });
      }
  };

  // Start the monitor
  if(!MailService.getEventSource() || MailService.getEventSource().readyState <= 1){
    $scope.startMonitor();
  }

});
