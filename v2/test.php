<?php

if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

if(file_exists(__DIR__.'/../config.json')){
  //read config file
  $config = @file_get_contents(__DIR__.'/../config.json');
  $config = @json_decode($config);

  if(!empty($config->w3w->api_key)){
    echo '* W3W API key found';
  } else {
    echo '* <span style="color:red">No W3W API key found</span>';
  }

  echo '<br/>';

  if(!empty($config->forecastio->api_key)){
    echo '* forecastio API key found';
  } else {
    echo '* <span style="color:red">No forecastio API key found</span>';
  }

  echo '<br/>';

  if(!empty($config->mongodb->host)){
    echo '* Database connection found';
    echo '<br/>';

    $connection = new Connection($config->mongodb);
    //unset the connection of Connect fails
    if($connection && $connection->Connect())
      echo '* Database Connected!';
    else if(Error::hasError())
        echo '* <span style="color:red">'.Error::getError().'</span>';

  } else {
    echo '* <span style="color:red">No database connection found</span>';
  }

} else {
  echo '<span style="color:red">Missing config.json file</span>';
}

?>
