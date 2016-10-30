<?php
/** MailHops API Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

use GeoIp2\Database\Reader;

if(file_exists('Net/DNSBL.php'))
	require_once 'Net/DNSBL.php';

class MailHops{

	const IMAGE_URL 			= 'https://api.mailhops.com/images/';

	//Use opendns, as google dns does not resolve DNSBL and Net/DNSBL is using a deprecated Net/DNS lib
	const DNS_SERVER 			= '208.67.222.222';

	private $ips;

	private $json_route;

	private $last_location		= null;

	private $total_miles		= 0;

	private $total_kilometers	= 0;

	private $trip_time_milliseconds = 0;

	private $reverse_host		= true;

	private $client_ip				= null;

	private $gi 				= null;

	private $gi6 				= null;

	private $dnsbl 				= null;

	private $w3w				= null;

	private $forecast			= null;

	private $language 			= 'en';

	private $unit 				= 'mi';

	private $config				= null;

	protected $google			= null;

	protected $connection 		= null;

	protected $account 		= null;

	public function __construct($account=null){

		if(file_exists(realpath(__DIR__.'/../../config.json'))){
			//read config file
			$config = @file_get_contents(realpath(__DIR__.'/../../config.json'));
			$this->config = @json_decode($config);
		}

		// Setup MongoDB Connection
		$this->connection = new Connection(!empty($this->config->mongodb) ? $this->config->mongodb : null);

		//unset the connection of Connect fails
		if($this->connection && !$this->connection->Connect())
			$this->connection = null;

		$this->client_ip = Util::getRealIpAddr();

		//setup account
		$this->account = $account;

		//init google
		$this->google = new Google;

		//setup geoip
		try {
			if(file_exists(realpath(__DIR__."/../../geoip/GeoLite2-City.mmdb")))
				$this->gi = new Reader(realpath(__DIR__."/../../geoip/GeoLite2-City.mmdb"));
		} catch(Exception $ex){
			if(file_exists(realpath(__DIR__."/../../geoip/GeoLite2-City2.mmdb")))
				$this->gi = new Reader(realpath(__DIR__."/../../geoip/GeoLite2-City2.mmdb"));
		}

		//setup dnsbl
		if(function_exists('Net_DNSBL')){
			$this->dnsbl = new Net_DNSBL();
			$this->dnsbl->setBlacklists(array('zen.spamhaus.org'));
		}

		//set variables
		$this->unit = (!empty($_GET['u']) && in_array($_GET['u'], array('mi','km')))?$_GET['u']:'mi';

		if(!empty($_GET['r']))
			$this->ips = explode(',',$_GET['r']);

		if(!empty($_GET['l']) && in_array($_GET['l'], array('de','en','es','fr','ja','pt-BR','ru','zh-CN') ))
			$this->language = $_GET['l'];

		$this->total_miles=0;
		$this->total_kilometers=0;
		$this->trip_time_milliseconds = (!empty($_GET['t']) && is_numeric($_GET['t'])) ? (int)$_GET['t'] : 0;

		$app_version = isset($_GET['app'])?$_GET['app']:'';
		if(empty($app_version))
			$app_version=isset($_GET['a'])?$_GET['a']:'';

		// Set W3W
		if(!empty($this->config->w3w->api_key))
			$this->w3w = new What3Words(array('api_key'=>$this->config->w3w->api_key, 'lang'=>$this->language));

		// Set DarkSky
		if(!empty($_GET['fkey']))
			$this->forecast = new DarkSky(array('api_key'=>$_GET['fkey'],'unit'=>$this->unit));
		else if(!empty($this->config->darksky->api_key))
			$this->forecast = new DarkSky(array('api_key'=>$this->config->darksky->api_key,'unit'=>$this->unit));

		//log the app and version, keep a daily count for stats
		if(!empty($app_version)){
			self::logApp($app_version);
		}
	}

	public function getVersion(){
		return 2;
	}

	public function setReverseHost($show){
		$this->reverse_host=$show;
	}

	public function getRoute(){

	$show_client = !isset($_GET['c'])?true:Util::toBoolean($_GET['c']);
	$whois = isset($_GET['whois'])?true:false;
	//track start time
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$start = $time;
	$got_weather = false;

	$mail_route=array();
	$client_route=null;

	//loop through IPs
	$hopnum=1;
	$origin=1;

	if(!empty($this->ips)){

		//get IP to check DNSBL
		$dnsbl_ip = self::getDNSBL_IP();
		$dnsbl_checked = false;

		foreach($this->ips as $ip){

			if(!self::isValid($ip))
				continue;

			if(!self::isPrivate($ip)){

				$route = self::getLocation($ip,$hopnum);

				$hostname=self::getRHost($ip);
				if(!empty($hostname))
					$route['host']=$hostname;
				if($whois || (!$dnsbl_checked && $ip==$dnsbl_ip)){
					$dnsbl_checked=true;
					$route['dnsbl']=self::getDNSBL($ip);
				}

				if(!empty($route['countryCode'])){
					if(empty($route['countryName']))
						$route['countryName']=self::getCountryName($route['countryCode']);
					if(file_exists(__DIR__.'/../../images/flags/'.strtolower($route['countryCode']).'.png'))
						$route['flag']=self::IMAGE_URL.'flags/'.strtolower($route['countryCode']).'.png';
				}

				if(!empty($route['countryCode'])){
					self::logCountry($route['countryCode'],$origin);
					if($route['countryCode']=='US' && !empty($route['state']))
						self::logState($route['state'],$origin);
				}

				//just get the weather for the sender location
				if(!$got_weather
						&& $this->forecast
						&& !empty($route['coords'])
						&& ($weather = $this->forecast->getForecast($route['coords'][1],$route['coords'][0])) !== false){
					$route['weather']=$weather;
					$got_weather=true;
				}
				$origin++;
			} else {
				$route = array('ip'=>$ip,'private'=>true,'local'=>true);
			}

			$route['hopnum']=$hopnum;
			$route['image']=self::IMAGE_URL;
			$route['image'].=$hopnum==1?'email_start.png':'email.png';

			if(!empty($route['dnsbl']) && $route['dnsbl']['listed']==true && file_exists(__DIR__.'/../../images/auth/bomb.png'))
				$route['image'] = self::IMAGE_URL.'auth/bomb.png';

			$mail_route[]=$route;

			$hopnum++;
		}
	}

	//get current location
	if(!empty($this->client_ip)){

		$route=array();
		if(!empty($this->client_ip) && !self::isPrivate($this->client_ip)){
			$route = self::getLocation($this->client_ip,$hopnum);

			if(!empty($route)){
				if(!empty($route['countryCode'])){
					if(empty($route['countryName']))
						$route['countryName']=self::getCountryName($route['countryCode']);
					if(file_exists(__DIR__.'/../../images/flags/'.strtolower($route['countryCode']).'.png'))
						$route['flag']=self::IMAGE_URL.'flags/'.strtolower($route['countryCode']).'.png';
				}
				$hostname=self::getRHost($this->client_ip);
				if(!empty($hostname))
					$route['host']=$hostname;
				$route['hopnum']=$hopnum;
				$route['image']=self::IMAGE_URL.'email_end.png';
				$route['client']=true;
				$client_route=$route;
			}
		} else if(self::isPrivate($this->client_ip)) {
			$client_route=array('ip'=>$this->client_ip
													,'private'=>true
													,'local'=>true
													,'client'=>true
													,'image'=>self::IMAGE_URL.'email_end.png'
													,'hopnum'=>$hopnum
												);
		}
	}
	//track end time
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);

	$this->logTraffic($mail_route,$client_route,$total_time);

	if($show_client==true && !empty($client_route))
		$mail_route[]=$client_route;

	//json_encode the route
	return array(
		'meta'=>array(
			'code'=>200
			,'time'=>$total_time
			,'host'=>!empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:''
		),'response'=>array(
			'distance'=>array(
				'miles'=>$this->total_miles
				,'kilometers'=>$this->total_kilometers
				,'milliseconds'=>$this->trip_time_milliseconds
			)
			,'route'=>$mail_route)
		);
	}

	// TODO support IPV6
	private function getDNSBL($ip)
	{
		$return = array('listed'=>false);

		if(self::isIPV6($ip))
			return $return;

		try {
			if($this->dnsbl && $this->dnsbl->isListed($ip,false)){
				$results = $this->dnsbl->getDetails($ip);
				if(isset($results) && substr($results['record'],0,7)=='127.0.0')
					$return = array('listed'=>true,'record'=>$results['record']);
			}
		} catch(Exception $ex){

		}

		return $return;
	}

	private function getDNSBL_IP(){
		//loop through the IPs starting from the last one to touch the message
		//this is the IP that we want to check against Spamhaus
		foreach(array_reverse($this->ips) as $ip){
			if(!self::isPrivate($ip))
				return $ip;
		}
		return false;
	}

	public function getRHost($ip,$format=true)
	{
		if(isset($this) && !$this->reverse_host)
			return '';

		if(self::isIPV6($ip))
			$output = exec('host -6 '.$ip.' '.self::DNS_SERVER);
		else
			$output = exec('host -4 '.$ip.' '.self::DNS_SERVER);

		if(!empty($output) && strstr($output,'name pointer')){
			//parse host
			$host = substr($output,strpos($output,'name pointer')+13);
			//return host
			if($format)
				return substr($host,0,strlen($host)-1);
			else
				return $output;
		}
		return '';
	}
	/*
		OrgName: AT&T Internet Services
		OrgId: SIS-80
		Address: 2701 N. Central Expwy # 2205.15
		City: Richardson
		StateProv: TX
		PostalCode: 75080
		Country: US
	*/
	public function getWhoIs($loc_array)
	{
		exec('whois -h whois.arin.net n '.$loc_array['ip'], $output);
		if(!empty($output)){
			for($i=0;$i<count($output);$i++){

				if(empty($loc_array['countryCode']) && strstr($output[$i],'Country:'))
					$loc_array['countryCode']=trim(str_replace('Country:','',$output[$i]));

				if(empty($loc_array['city']) && strstr($output[$i],'City:'))
					$loc_array['city']=trim(str_replace('City:','',$output[$i]));

				if(empty($loc_array['state']) && strstr($output[$i],'StateProv:'))
					$loc_array['state']=trim(str_replace('StateProv:','',$output[$i]));

				if(empty($loc_array['zip']) && strstr($output[$i],'PostalCode:'))
					$loc_array['zip']=trim(str_replace('PostalCode:','',$output[$i]));

			}
		}
		return $loc_array;
	}

	private function getLocation($ip,$hopnum)
	{
		$loc_array=array('ip'=>"$ip");

		try{
			$loc_array=self::getLocationMaxMind($ip,$hopnum);
		}
		catch(Exception $ex){
			error_log($ex->getMessage().' MaxMind '.$ip);
		}

		return $loc_array;
	}

	private function getLanguageValue($field){
		if(isset($field[$this->language]))
			return $field[$this->language];
		else if(isset($field['en']))
			return $field['en'];
		return '';
	}

	private function parseCityFromTimeZone($timeZone){
		if(isset($timeZone)){
			$end = explode('/', $timeZone);
			$city = end($end);
			return str_replace('_', ' ', $city);
		}
		return '';
	}

	private function getLocationMaxMind($ip,$hopnum)
	{
		$loc = '';
		$loc_array=array('ip'=>"$ip");

		if(!$this->gi || self::isPrivate($ip))
			return $loc_array;

		try {
				$location = $this->gi->city($ip);
				if(!empty($location)){

					$loc_array=array('ip'=>"$ip"
									,'coords'=>[$location->location->longitude,$location->location->latitude]
									,'city'=>(self::getLanguageValue($location->city->names) != '') ? self::getLanguageValue($location->city->names) : self::parseCityFromTimeZone($location->location->timeZone)
									,'state'=>(!self::displayState($location->mostSpecificSubdivision->isoCode) && self::getLanguageValue($location->country->names) !='')
										?utf8_encode(self::getLanguageValue($location->country->names))
										:utf8_encode($location->mostSpecificSubdivision->isoCode)//shouldn't do display logic here but...
									,'zip'=>!empty($location->postal->code)?$location->postal->code:''
									,'countryName'=>self::getLanguageValue($location->country->names)
									,'countryCode'=>!empty($location->country->isoCode)?$location->country->isoCode:''
								);
								if($this->w3w && ($words = $this->w3w->getWords($location->location->latitude,$location->location->longitude)) !== false){
									$loc_array['w3w'] = $words;
								}
					}
			} catch(Exception $ex) {
				// IP not found, continue and check whois
				// error_log($ex->getMessage());
			}

			//get city from whois
			if(empty($loc_array['countryCode']) || empty($loc_array['city']))
				$loc_array = self::getWhoIs($loc_array);

			//get ip from google
			if(!empty($loc_array['countryCode'])
				&& !empty($loc_array['city'])
				&& empty($loc_array['coords'])){
					$loc_array = $this->google->GeoCode($loc_array);
			}
			//get distance from last location
			if(!empty($this->last_location)
				&& !empty($loc_array['city'])
				&& !empty($loc_array['coords'])){
					if($this->last_location['city']!=$loc_array['city']){
						$distance = Util::getDistance($this->last_location['coords'],$loc_array['coords']);
						if(!empty($distance)){
							$this->total_kilometers += $distance;
							$this->total_miles += ($distance/1.609344);
						}
						$loc_array['distance']=array('from'=>array('hopnum'=>($hopnum-1)),'miles'=>!empty($distance)?($distance/1.609344):0,'kilometers'=>$distance);
					} else {
						$loc_array['distance']=array('from'=>array('hopnum'=>($hopnum-1)),'miles'=>0,'kilometers'=>0);
					}
					$this->last_location=$loc_array;
			} else if(!empty($loc_array['coords'])){
				$this->last_location=$loc_array;
			}
		return $loc_array;
	}

	private function displayState($state){

		if(is_numeric($state))
			return false;
		else if(strlen($state)==2){
			if(is_numeric(substr($state,0,1)) || is_numeric(substr($state,1,1)))
				return false;
		}
		return true;
	}

	public function isIPV6($ip){
		if(strstr($ip, ":"))
			return true;
		return false;
	}
	/*
	NetRange: 240.0.0.0 - 255.255.255.255
	CIDR: 240.0.0.0/4
	NetName: SPECIAL-IPV4-FUTURE-USE-IANA-RESERVED
	NetHandle: NET-240-0-0-0-0
	Addresses starting with 240 or a higher number have not been allocated and should not be used, apart from 255.255.255.255, which is used for "limited broadcast" on a local network.
	*/
	public function isValid($ip){
		return (int)substr($ip,0,strpos($ip,'.')) < 240;
	}

	public function isPrivate($ip){
		return preg_match('/(^127\.)|(^192\.168\.)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(^::1$)|(^[fF][cCdD])/',$ip);
	}

	private function logTraffic($route,$client,$total_time){
		if(!$this->connection)
			return false;

		//append the client hop
		if(!empty($client))
			$route[]=$client;

		$query = array('date'=>(int)date('U')
									,'route'=>$route
									,'time'=>$total_time
									,'distance'=>array(
										'miles'=>$this->total_miles
										,'kilometers'=>$this->total_kilometers
										,'milliseconds'=>$this->trip_time_milliseconds
									)
			);

		$collection = $this->connection->getConn()->traffic;
		$collection->insertOne($query);

		if($this->account){
			$this->account->logTraffic($query);
		}

	}

	public function getTraffic($since=''){
		if(!$this->connection)
			return false;

		$query = [];
		$options = ['sort'=>['date'=>-1]];

		if(!empty($since) && is_numeric($since))
			$query['date'] = ['$gte'=>(int)$since];
		else if(!empty($_GET['since']) && is_numeric($_GET['since']))
      $query['date'] = ['$gte'=>(int)$_GET['since']];

		if(!empty($_GET['country']))
      $query['route.countryName'] = new MongoDB\BSON\Regex('/*'.$_GET['country'].'*/','i');

		if(!empty($_GET['country_code']))
			$query['route.countryCode'] = new MongoDB\BSON\Regex('/*'.$_GET['country_code'].'*/','i');

		if(!empty($_GET['city']))
			$query['route.city'] = new MongoDB\BSON\Regex($_GET['city'],'i');

		if(!empty($_GET['state']))
			$query['route.state'] = new MongoDB\BSON\Regex('/*'.$_GET['state'].'*/','i');

		if(!empty($_GET['host']))
			$query['route.host'] = new MongoDB\BSON\Regex('/*'.$_GET['host'].'*/','i');

		if(!empty($_GET['ll'])){
			$latlng = explode(',',$_GET['ll']);
			if(count($latlng)==2 && is_float($latlng[0]) && is_float($latlng[1])){
				$query['coords'] = ['$near'=>[(float)$latlng[1],(float)$latlng[0]]
													 ,'$maxDistance'=>(!empty($_GET['radius']) && is_numeric($_GET['radius'])) ?$_GET['radius'] : 1000];
			}
		}

    if(!empty($_GET['limit']) && is_numeric($_GET['limit']))
      $options['limit']=(int)$_GET['limit'];
    if(!empty($_GET['skip']) && is_numeric($_GET['skip']))
      $options['skip']=(int)$_GET['skip'];

		$collection = $this->connection->getConn()->traffic;
		$cursor = $collection->find($query,$options);
		$cursor->setTypeMap(['array' => 'array']);

		if(!empty($cursor))
			return $cursor->toArray();
		return [];
	}

	private function logApp($version){
		if(!$this->connection)
			return false;

		$collection = $this->connection->getConn()->stats;
		$collection->updateOne(array('version'=>$version,'day'=>(int)date('Ymd'))
			,array('$inc'=>array("count"=>1)
					,'$set'=>array('day'=>(int)date('Ymd')))
			,array('upsert'=>true,'w'=>0,'multiple'=>false));
	}

	private function logCountry($iso,$origin){
		if(!$this->connection)
			return false;

		$field = $origin==1?"origin_count":"count";
		$collection = $this->connection->getConn()->countries;
		$query = array('iso'=>strtoupper($iso));
		if(strlen($iso)==3)
			$query = array('iso3'=>strtoupper($iso));

		$collection->updateOne($query
			,array('$inc'=>array("$field"=>1))
			,array('upsert'=>false,'w'=>0,'multiple'=>false));
	}

	private function logState($state_abbr,$origin){
		if(!$this->connection)
			return false;

		$field = $origin==1?"origin_count":"count";
		$collection = $this->connection->getConn()->states;
		$collection->updateOne(array('abbr'=>strtoupper($state_abbr))
			,array('$inc'=>array("$field"=>1))
			,array('upsert'=>false,'w'=>0,'multiple'=>false));
	}

	private function getCountryCode($country){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->countries;
		$countryCode = $collection->findOne(array('name'=>strtoupper($country)),array('iso'=>1));

		if(empty($countryCode->iso))
			return false;
		else
			return $countryCode->iso;
	}

	private function getCountryName($iso){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->countries;
		$query = array('iso'=>strtoupper($iso));
		if(strlen($iso)==3)
			$query = array('iso3'=>strtoupper($iso));

		$countryName = $collection->findOne($query,array('printable_name'=>1));

		if(empty($countryName->printable_name))
			return false;
		else
			return $countryName->printable_name;
	}

	private function isUnitedStates($state_abbr){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->states;
		$stateName = $collection->findOne(array('abbr'=>strtoupper($state_abbr)),array('name'=>1));

		if(empty($stateName->name))
			return false;
		else{
			return $stateName->name;
		}
	}
}
?>
