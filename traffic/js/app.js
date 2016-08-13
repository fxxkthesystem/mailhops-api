angular.module('mailHops',[])
.controller('mainController', function ($scope, $filter) {

  $scope.routes = [];
  $scope.units = 'mi';

  var width = 960,
      height = 500;

  var projection = d3.geo.albersUsa()
                         .scale(1000)
                         .translate([width / 2, height / 2]);

  var path = d3.geo.path()
               .projection(projection);

  d3_draw(d3.select('#map'));

  function d3_draw(el) {
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

  if (!!window.EventSource) {
    var source = new EventSource('/v2/traffic');
    var traffic, hops, coords, route;
    source.addEventListener('message', function(e) {
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
            date: $filter('date')(new Date(e.lastEventId*1000),'medium'),
            firstHop: route[0],
            lastHop: route[route.length-1]
          });
          $scope.$apply();
          coords = route.map(function(h){
            return [h.lng, h.lat];
          });
          d3_traffic(d3.select('#map'), route, coords);
        });
      }
    }, false);
  }

});
