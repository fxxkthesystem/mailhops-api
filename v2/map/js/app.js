angular.module('mailHops',
['leaflet-directive'
,'ui.bootstrap'
,'ngCookies'])
.filter('weather',function(){
    return function(icon){
        var forecast_icons = {'clear-day': {'day':'wi-day-sunny', 'night':'wi-day-sunny'}
            , 'clear-night': {'day':'wi-night-clear', 'night':'wi-night-clear'}
            , 'rain': {'day':'wi-day-rain','night':'wi-night-alt-rain'}
            , 'snow': {'day':'wi-day-snow','night':'wi-night-alt-snow'}
            , 'sleet': {'day':'wi-sleet','night':'wi-night-alt-rain-mix'}
            , 'wind': {'day':'wi-day-cloudy-windy','night':'wi-night-alt-cloudy-windy'}
            , 'fog': {'day':'wi-day-fog','night':'wi-night-fog'}
            , 'cloudy': {'day':'wi-day-cloudy','night':'wi-night-cloudy'}
            , 'partly-cloudy-day': {'day':'wi-day-cloudy','night':'wi-day-cloudy'}
            , 'partly-cloudy-night': {'day':'wi-night-cloudy','night':'wi-night-cloudy'}
            , 'hail': {'day':'wi-day-hail','night':'wi-night-alt-hail'}
            , 'thunderstorm': {'day':'wi-day-thunderstorm','night':'wi-thunderstorm'}
            , 'tornado': {'day':'wi-tornado','night':'wi-tornado'}
        };
        var hr = (new Date).getHours();
        var time = (hr >= 4 && hr <= 18)?'day':'night';
        return 'wi '+forecast_icons[icon][time];
    };
})
.controller('mainController', function($scope, leafletData, $cookies) {

        $scope.route = mailRoute.response.route;
        $scope.map_unit = mapUnit;
        $scope.map_provider = $cookies.get('map_provider') || mapProvider;

        $scope.markers = [];
        $scope.templates = [];
        $scope.distance = '';
        
        var hopLines = [];
        var prevHopFocused;
        var local_icons = { div_icon: {
            iconUrl: '/images/hop.svg',
            iconSize: [35, 35],
            popupAnchor:  [0, 0]
        } };

        angular.extend($scope, {
            icons: local_icons
        });

        angular.forEach(L.TileLayer.Provider.providers, function(k,v){
            if(k.variants){
                angular.forEach(k.variants,function(key,variant){
                    $scope.templates.push({name:v+'.'+variant});
                })
            } else {
                $scope.templates.push({'name':v});
            }
        });

        if($scope.map_unit=='km')
            $scope.distance = mailRoute.response.distance.kilometers;
        else
            $scope.distance = mailRoute.response.distance.miles;

        angular.forEach($scope.route, function(r) {
            if(r.coords){
                r.focus = false;
                var message = '<strong>#'+r.hopnum+'</strong> ';
                if(r.city !='')
                    message += r.city+', ';
                message += (r.state !='') ? r.state : r.countryName;

                //add hop to the markers
                $scope.markers[r.hopnum] = { lat: r.coords[1]
                                            , lng: r.coords[0]
                                            , message: message
                                            , icon: local_icons.div_icon
                                            , hopnum: r.hopnum
                                            , focus: false
                                        };
                //add hop to the polyline
                hopLines.push([r.coords[1],r.coords[0]]);
            }
        });

        angular.extend($scope, {
                boulder: {
                    lat: 40.0274,
                    lng: -105.2519,
                    zoom: 8
                },
                markers: $scope.markers
            });

        leafletData.getMap('map').then(function(map) {

            if(hopLines.length>0){
                var polyline = L.geodesicPolyline(hopLines, {color: '#428bca'}).addTo(map);
                map.fitBounds(polyline.getBounds());
            }

            if($scope.map_provider && $scope.map_provider != '')
                L.tileLayer.provider($scope.map_provider).addTo(map);
        });

        $scope.changeTemplate = function(template){

            $scope.map_provider = template;
            leafletData.getMap('map').then(function(map) {
                L.tileLayer.provider($scope.map_provider).addTo(map);
            });

            $cookies.put('map_provider',template);
        };

        $scope.showMarker = function(hopnum){
            if(prevHopFocused){
                $scope.markers[prevHopFocused].focus = false;
            }

            if($scope.markers[hopnum]){
                $scope.markers[hopnum].focus = true;
                prevHopFocused = hopnum;
            }
            //route does not have index
            angular.forEach($scope.route,function(r){
                if(r.hopnum==hopnum)
                    r.focus = true;
                else
                    r.focus = false;
            });
        };

        $scope.$on('leafletDirectiveMarker.click', function (e, a){
            $scope.showMarker(a.model.hopnum);
          });
});
