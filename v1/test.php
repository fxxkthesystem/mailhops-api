<?php

if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

if(file_exists(__DIR__.'/../config.json')){
  //read config file
  $config = @file_get_contents(__DIR__.'/../config.json');
  $config = @json_decode($config);
}

echo '<h2>W3W</h2>';

if(!empty($config->w3w->api_key)){
  echo 'W3W API key found';
} else {
  echo '<span style="color:red">No W3W API key found</span>';
}

echo '<br/>';
echo '<h2>Forecast.io</h2>';

if(!empty($config->forecastio->api_key)){
  echo 'forecastio API key found';
} else {
  echo '<span style="color:red">No forecastio API key found</span>';
}

echo '<br/>';
echo '<h2>MongoDB</h2>';

  $connection = new Connection(!empty($config->mongodb) ? $config->mongodb : null);
  //unset the connection of Connect fails
  if($connection && $connection->Connect())
    echo 'Connected!';
  else if(Error::hasError())
      echo '<span style="color:red">'.Error::getError().'</span>';

?>
