<?php

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

class ForecastIO {

	private $api_key		= '';

	private $client			= null;

	private $units			= 'us';//us or uk

	public function __construct($args=array()){

		//get api key
		if(!empty($args['api_key'])){
			$this->api_key = $args['api_key'];
			$this->client = new Client();
			CacheSubscriber::attach($this->client);
		}

		if(!empty($args['unit']) && $args['unit']=='km'){
			$this->units = 'uk';
		}
	}

	public function getForecast($lat,$lng){
		//if no api key return empty string
		if(empty($this->api_key))
			return '';

		$res = $this->client->get('https://api.forecast.io/forecast/'.$this->api_key.'/'.$lat.','.$lng.'?units='.$this->units);

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
