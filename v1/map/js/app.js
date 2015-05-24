var mailHops = angular.module('mailHops',['leaflet-directive'])
.controller('mainController', ['$scope', 'leafletData', function($scope,leafletData) {
        
        function addCommas(nStr)
        {
            nStr += '';
            var x = nStr.split('.');
            var x1 = x[0];
            var x2 = x.length > 1 ? '.' + x[1] : '';
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(x1)) {
                x1 = x1.replace(rgx, '$1' + ',' + '$2');
            }
            return x1 + x2;
        }

        var hopLines = [];
        var prevHopFocused;

        $scope.markers = [];        
        $scope.route = mailRoute.response.route;
        $scope.distance = '';

        if(map_unit=='k')
            $scope.distance = addCommas(Math.round(mailRoute.response.distance.kilometers))+' ki';            
        else
            $scope.distance = addCommas(Math.round(mailRoute.response.distance.miles))+' mi';

        angular.forEach($scope.route, function(r) {
            if(r.lat){
                var message = '#'+r.hopnum+' ';
                message += (r.city !='')?r.city+', '+r.state:r.countryName;                
                //add hop to the markers
                $scope.markers[r.hopnum] = {
                    lat: r.lat,
                    lng: r.lng,
                    message: message
                };
                //add hop to the polyline
                hopLines.push([r.lat,r.lng]);
            }
        });
                
        angular.extend($scope, {
                london: {
                    lat: 51.505,
                    lng: -0.09,
                    zoom: 8
                },
                markers: $scope.markers
            });

        leafletData.getMap('map').then(function(map) {
            L.tileLayer.provider(map_provider).addTo(map);

            if(hopLines.length>0){
                var polyline = L.geodesicPolyline(hopLines, {color: '#428bca'}).addTo(map);
                map.fitBounds(polyline.getBounds());                
            }
        });

        $scope.showMarker = function(hopnum){
            if(prevHopFocused)
                $scope.markers[prevHopFocused].focus = false;                

            if($scope.markers[hopnum]){
                $scope.markers[hopnum].focus = true;
                prevHopFocused = hopnum;                
            }
        };
}]);