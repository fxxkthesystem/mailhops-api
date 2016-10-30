<?php

if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

if(file_exists(realpath(__DIR__.'/../config.json'))){
  //read config file
  $config = @file_get_contents(realpath(__DIR__.'/../config.json'));
  $config = @json_decode($config);
}

echo '<h1>MailHops API V1</h1>';
echo '<h2>W3W</h2>';

if(!empty($config->w3w->api_key)){
  echo 'W3W API key found';
} else {
  echo '<span style="color:red">No W3W API key found</span>';
}

echo '<br/>';
echo '<h2>DarkSky/ForecastIO weather</h2>';

if(!empty($config->forecastio->api_key)){
  echo 'ForecastIO API key found';
} else {
  echo '<span style="color:red">No ForecastIO API key found</span>';
}

echo '<br/>';
echo '<h2>MongoDB</h2>';

$connection = new Connection(!empty($config->mongodb) ? $config->mongodb : null);
//unset the connection of Connect fails
if($connection && $connection->Connect()){
  echo 'Connected!';
} else if(Error::hasError()){
  echo '<span style="color:red">'.Error::getError().'</span>';
}

?>
