<?php

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

class ForecastIO {

	private $api_key		= '';

	private $client			= null;
	
	public function __construct($args=array()){		
		
		if(!empty($args['forecast_api_key'])){
			$this->api_key = $args['forecast_api_key'];			
		} 
		else if(file_exists(__DIR__.'/../../config.json')){
			//read config file
			$config = @file_get_contents(__DIR__.'/../../config.json');
			$config_json = @json_decode($config);

			//get w3w api key
			if(!empty($config_json->forecastio->api_key))
				$this->api_key = $config_json->forecastio->api_key;
			
			//setup caching
			$this->client = new Client();
			CacheSubscriber::attach($this->client);

		} else {
			error_log('Missing config.json file.');
			return null;
		}
	}

	public function getForecast($lat,$lng){
		if(empty($this->api_key))
			return '';

		$res = $this->client->get('https://api.forecast.io/forecast/'.$this->api_key.'/'.$lat.','.$lng);
		
		if($res->getStatusCode() == 200)
		{
			$return = $res->json();
			
			if(!empty($return['currently']))
				return array(
						'time'=>$return['currently']['time']
						,'icon'=>$return['currently']['icon']
						,'summary'=>$return['currently']['summary']
						,'temp'=>$return['currently']['temperature']
					);
		}
		return '';
	}
}
?>