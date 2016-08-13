<!DOCTYPE html>
<html ng-app="mailHops">
    <head>
        <meta charset="utf-8">
        <title>MailHops API Traffic Map</title>
        <link rel="stylesheet" href="/traffic/css/bootstrap.min.css">
        <link rel="stylesheet" href="/traffic/css/style.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.8/angular.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.slim.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
				<script src="//cdn.jsdelivr.net/lodash/4.11.2/lodash.min.js"></script>
        <script src="//d3js.org/d3.v3.min.js"></script>
        <script src="//d3js.org/topojson.v1.min.js"></script>
    </head>
    <body ng-controller="mainController">
      <div class="navbar navbar-default navbar-fixed-top">
     <div class="container">
       <div class="navbar-header">
         <a href="../" class="navbar-brand">MailHops API</a>
         <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
         </button>
       </div>
       <div class="navbar-collapse collapse" id="navbar-main">
         <ul class="nav navbar-nav">
           <li class="active">
             <a href="/traffic/us">U.S.</a>
           </li>
           <li>
             <a href="/traffic/world">World</a>
           </li>
         </ul>

         <ul class="nav navbar-nav navbar-right">
           <li><a href="https://github.com/avantassel/mailhops-api" target="_blank">Github</a></li>
           <li><a href="https://twitter.com/mailhops" target="_blank">Twitter</a></li>
         </ul>

       </div>
     </div>
   </div>


   <div class="container">

     <div class="page-header" id="banner">
       <div class="row">
         <div class="col-lg-12 col-md-8">
            <h1>Traffic</h1>
            <p class="lead">Realtime hits</p>
          </div>
           <div class="col-lg-12 col-md-8">
              <div id="map"></div>
            </div>
            <div class="col-lg-12">
              <label class="label units" ng-class="{'label-primary': units=='mi', 'label-default': units=='km'}" ng-click="units='mi'">Miles</label>
              <label class="label units" ng-class="{'label-primary': units=='km', 'label-default': units=='mi'}" ng-click="units='km'">Kilometers</label>
              <table class="table table-striped table-hover ">
                <thead>
                  <tr>
                    <th>Hops</th>
                    <th>Distance</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                  </tr>
                </thead>
                <tbody>
                  <tr ng-repeat="hop in routes | orderBy: '-id'">
                    <td>{{hop.number}}</td>
                    <td>{{(units=='mi') ? (hop.distance.miles | number : 0) : (hop.distance.kilometers | number : 0) }} {{units}}</td>
                    <td>{{hop.date}}</td>
                    <td>{{hop.firstHop.city || hop.firstHop.countryName}}, {{hop.firstHop.state || hop.firstHop.countryCode}}</td>
                    <td>{{hop.lastHop.city || hop.lastHop.countryName}}, {{hop.lastHop.state || hop.lastHop.countryCode}}</td>
                  </tr>
                </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

  </body>
  <script src='/traffic/js/app.js'></script>
</html>
