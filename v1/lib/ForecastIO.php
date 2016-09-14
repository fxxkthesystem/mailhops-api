<?php
/** ForecastIO Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0.0
 */

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

class ForecastIO {

	private $api_key		= '';

	private $client			= null;

	private $units			= 'us';//us or uk

	public function __construct($args=array()){

		//get api key
		if(getenv('FORECASTIO_API_KEY')){
			$this->api_key = getenv('FORECASTIO_API_KEY');
		} else if(!empty($args['api_key'])){
			$this->api_key = $args['api_key'];
		}

		$this->client = new Client();
		CacheSubscriber::attach($this->client);

		if(!empty($args['unit']) && $args['unit']=='km'){
			$this->units = 'uk';
		}
	}

	public function getForecast($lat,$lng){
		//if no api key return empty string
		if(empty($this->api_key))
			return '';

		try {
			$res = $this->client->request('GET','https://api.forecast.io/forecast/'.$this->api_key.'/'.$lat.','.$lng.'?units='.$this->units);

			if($res->getStatusCode() == 200)
			{
				$return = json_encode($res->getBody());

				if(!empty($return['currently']))
					return array(
							'time'=>$return['currently']['time']
							,'icon'=>$return['currently']['icon']
							,'summary'=>$return['currently']['summary']
							,'temp'=>$return['currently']['temperature']
						);
			}
		} catch(GuzzleHttp\Exception\ClientException $ex){
			MError::setError('ForecastIO Error.  Please verify or remove your ForecastIO API Key.');
		}
		return '';
	}
}
?>
