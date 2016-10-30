<?php
/** DarkSky Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

class DarkSky {

	private $api_key		= '';

	private $client			= null;

	private $units			= 'us';//us or uk

	public function __construct($args=array()){

		//get api key
		if(getenv('DARKSKY_API_KEY')){
			$this->api_key = getenv('DARKSKY_API_KEY');
		} else if(!empty($args['api_key'])){
			$this->api_key = $args['api_key'];
		}

		// Create default HandlerStack
		$stack = HandlerStack::create();

		// Add this middleware to the top with `push`
		$stack->push(new CacheMiddleware(), 'cache');

		// Initialize the client with the handler option
		$this->client = new Client(['handler' => $stack]);

		if(!empty($args['unit']) && $args['unit']=='km'){
			$this->units = 'uk';
		}
	}

	public function getForecast($lat,$lng){
		//if no api key return empty string
		if(empty($this->api_key))
			return false;

		try {
			$res = $this->client->request('GET','https://api.darksky.net/forecast/'.$this->api_key.'/'.$lat.','.$lng.'?units='.$this->units);

			if($res->getStatusCode() == 200)
			{
				$return = json_decode($res->getBody());

				if(!empty($return->currently))
					return array(
							'time'=>$return->currently->time
							,'icon'=>$return->currently->icon
							,'summary'=>$return->currently->summary
							,'temp'=>$return->currently->temperature
						);
			}
		} catch(GuzzleHttp\Exception\ClientException $ex){
			MError::setError('DarkSky Error.  Please verify or remove your DarkSky API Key.');
		}
		return false;
	}
}
?>
