var mailHops = angular.module('mailHops',['leaflet-directive'])
.controller('mainController', ['$scope', 'leafletData', function($scope,leafletData) {
        
        $scope.route = mailRoute.response.route;
        $scope.map_unit = mapUnit;
        $scope.map_provider = mapProvider;

        $scope.markers = [];     
        $scope.templates = [];   
        $scope.distance = '';

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

        if($scope.map_unit=='k')
            $scope.distance = addCommas(Math.round(mailRoute.response.distance.kilometers))+' ki';            
        else
            $scope.distance = addCommas(Math.round(mailRoute.response.distance.miles))+' mi';

        angular.forEach($scope.route, function(r) {
            if(r.lat){
                r.focus = false;
                var message = '<strong>#'+r.hopnum+'</strong> ';
                message += (r.city !='')?r.city+', '+r.state:r.countryName;
                //add hop to the markers
                
                $scope.markers[r.hopnum] = { lat: r.lat
                                            , lng: r.lng
                                            , message: message
                                            , icon: local_icons.div_icon
                                            , hopnum: r.hopnum
                                            , focus: false
                                        };
                //add hop to the polyline
                hopLines.push([r.lat,r.lng]);
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
            L.tileLayer.provider($scope.map_provider).addTo(map);

            if(hopLines.length>0){
                var polyline = L.geodesicPolyline(hopLines, {color: '#428bca'}).addTo(map);
                map.fitBounds(polyline.getBounds());                
            }
        });

        $scope.changeTemplate = function(template){
            $scope.map_provider = template;
            leafletData.getMap('map').then(function(map) {
                L.tileLayer.provider($scope.map_provider).addTo(map);
            });
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
}]);