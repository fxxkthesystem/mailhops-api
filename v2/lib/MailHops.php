<?php
/** MailHops API Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

date_default_timezone_set('America/Denver');

use GeoIp2\Database\Reader;

if(file_exists('Net/DNSBL.php'))
	require_once 'Net/DNSBL.php';

class MailHops{

	private $ips;

	private $json_route;

	private $last_location		= null;

	private $total_miles		= 0;

	private $total_kilometers	= 0;

	private $reverse_host		= true;

	private $gi 				= null;

	private $gi6 				= null;

	private $dnsbl 				= null;

	private $w3w				= null;

	private $forecast			= null;

	private $connection 		= null;

	private $influxdb 		= null;

	private $language 			= 'en';

	private $unit 				= 'mi';

	private $config				= null;

	private $account 		= null;

	const IMAGE_URL 			= 'https://api.mailhops.com/images/';

	//path from DOCUMENT_ROOT
	const IMAGE_DIR 			= '/images/';

	//Use opendns, as google dns does not resolve DNSBL and Net/DNSBL is using a deprecated Net/DNS lib
	const DNS_SERVER 			= '208.67.222.222';

	public function __construct(){

		if(file_exists(__DIR__.'/../../config.json')){
			//read config file
			$config = @file_get_contents(__DIR__.'/../../config.json');
			$this->config = @json_decode($config);
		}

		$this->unit = (!empty($_GET['u']) && in_array($_GET['u'], array('mi','km')))?$_GET['u']:'mi';

		if(!empty($_GET['r']))
			$this->ips = explode(',',$_GET['r']);

		if(!empty($_GET['l']) && in_array($_GET['l'], array('de','en','es','fr','ja','pt-BR','ru','zh-CN') ))
			$this->language = $_GET['l'];

		$this->total_miles=0;
		$this->total_kilometers=0;

		$app_version = isset($_GET['app'])?$_GET['app']:'';
		if(empty($app_version))
			$app_version=isset($_GET['a'])?$_GET['a']:'';

		//setup geoip
		if(file_exists(__DIR__."/../../geoip/GeoLite2-City.mmdb"))
			$this->gi = new Reader(__DIR__."/../../geoip/GeoLite2-City.mmdb");

		//setup dnsbl
		if(function_exists('Net_DNSBL')){
			$this->dnsbl = new Net_DNSBL();
			$this->dnsbl->setBlacklists(array('zen.spamhaus.org'));
		}

		//setup config

		// Set W3W
		if(!empty($this->config->w3w->api_key))
			$this->w3w = new What3Words(array('api_key'=>$this->config->w3w->api_key, 'lang'=>$this->language));

		// Set ForecastIO
		if(!empty($_GET['fkey']))
			$this->forecast = new ForecastIO(array('api_key'=>$_GET['fkey'],'unit'=>$this->unit));
		else if(!empty($this->config->forecastio->api_key))
			$this->forecast = new ForecastIO(array('api_key'=>$this->config->forecastio->api_key,'unit'=>$this->unit));

		// Setup MongoDB Connection
		$this->connection = new Connection(!empty($this->config->mongodb) ? $this->config->mongodb : null);
		//unset the connection of Connect fails
		if($this->connection && !$this->connection->Connect())
			$this->connection = null;

		// Setup InfluxDB Connection
		if(!empty($this->config->influxdb)){
			$this->influxdb = new Stats($this->config->influxdb);
			//unset the influxdb of Connect fails
			if($this->influxdb && !$this->influxdb->Connect())
				$this->influxdb = null;
		}

		if($this->connection && !empty($_GET['api_key'])){
			$this->account = new Account($_GET['api_key'],$this->connection);
		}

		//log the app and version, keep a daily count for stats
		if(!empty($app_version)){
			self::logApp($app_version);
		}
	}

	public function setReverseHost($show){
		$this->reverse_host=$show;
	}

	public function getRoute(){

	$show_client = isset($_GET['c'])&&!Util::toBoolean($_GET['c'])?false:Util::toBoolean($_GET['c']);

	$client_ip=self::getRealIpAddr();
	$is_mailhops_site = isset($_GET['test'])?true:false;
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
				if($whois || (!$is_mailhops_site && !$dnsbl_checked && $ip==$dnsbl_ip)){
					$dnsbl_checked=true;
					// $route['dnsbl']=self::getDNSBL($ip);
				}

				if(!empty($route['countryCode'])){
					if(empty($route['countryName']))
						$route['countryName']=self::getCountryName($route['countryCode']);
					if(file_exists($_SERVER['DOCUMENT_ROOT'].self::IMAGE_DIR.'flags/'.strtolower($route['countryCode']).'.png'))
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
						&& isset($route['lat'])
						&& isset($route['lng'])
						&& ($weather = $this->forecast->getForecast($route['lat'],$route['lng'])) !=''){
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

			if(!empty($route['dnsbl']) && $route['dnsbl']['listed']==true && file_exists($_SERVER['DOCUMENT_ROOT'].self::IMAGE_DIR.'auth/bomb.png'))
				$route['image'] = self::IMAGE_URL.'auth/bomb.png';

			$mail_route[]=$route;

			$hopnum++;
		}
	}

	//get current location
	if(!empty($client_ip)){

		$route=array();
		if(!empty($client_ip) && !self::isPrivate($client_ip)){
			$route = self::getLocation($client_ip,$hopnum);

			if(!empty($route)){
				if(!empty($route['countryCode'])){
					if(empty($route['countryName']))
						$route['countryName']=self::getCountryName($route['countryCode']);
					if(file_exists($_SERVER['DOCUMENT_ROOT'].self::IMAGE_DIR.'flags/'.strtolower($route['countryCode']).'.png'))
						$route['flag']=self::IMAGE_URL.'flags/'.strtolower($route['countryCode']).'.png';
				}
				$hostname=self::getRHost($client_ip);
				if(!empty($hostname))
					$route['host']=$hostname;
				$route['hopnum']=$hopnum;
				$route['image']=self::IMAGE_URL.'email_end.png';
				$route['client']=true;
				//$route['dnsbl']=self::getDNSBL($ip);
				$client_route=$route;
			}
		} else if(self::isPrivate($client_ip)) {
			$client_route=array('ip'=>$client_ip,'private'=>true,'local'=>true,'client'=>true,'image'=>self::IMAGE_URL.'email_end.png','hopnum'=>$hopnum);
		}
	}
	//track end time
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);

	// if($this->config && !empty($this->config->routes) && $this->account){
	// 	$this->account->saveRoute(
	// 		array('distance'=>array(
	// 			'miles'=>$this->total_miles
	// 			,'kilometers'=>$this->total_kilometers)
	// 		,'route'=>$mail_route);
	// 	);
	// }

	// if(!empty($this->influxdb)){
	// 	$this->influxdb->saveStat(count($mail_route));
	// }

	$this->logTraffic($mail_route,$client_route);

	if($show_client==true && !empty($client_route))
		$mail_route[]=$client_route;

	//json_encode the route
	return json_encode(array(
		'meta'=>array(
			'code'=>200
			,'time'=>$total_time
			,'host'=>$_SERVER['SERVER_NAME']),
		'response'=>array(
			'distance'=>array(
				'miles'=>$this->total_miles
				,'kilometers'=>$this->total_kilometers)
			,'route'=>$mail_route))
		);
	}

	//used to get the final hop
	public function getRealIpAddr()
	{
	    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    else
	    {
	      $ip=$_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
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
	public function getWhoIs($ip,$format=true)
	{
		$return = array();

		exec('whois -h whois.arin.net n '.$ip, $output);
		if(!empty($output)){
			for($i=0;$i<count($output);$i++){

			if(strstr($output[$i],'descr:') && empty($return['descr']))
				$return['descr']=trim(str_replace('descr:','',$output[$i]));

			if(strstr($output[$i],'netname:'))
				$return['netname']=trim(str_replace('netname:','',$output[$i]));

			if(strstr($output[$i],'abuse-mailbox:'))
				$return['abuse-mailbox']=trim(str_replace('abuse-mailbox:','',$output[$i]));

			if(strstr($output[$i],'country:'))
				$return['countryCode']=trim(str_replace('country:','',$output[$i]));

			if(strstr($output[$i],'phone:') && strstr($output[$i-1],'address:'))
				$return['countryName']=trim(str_replace('address:','',$output[$i-1]));
			}
		}
		if($format)
			return $return;
		else
			return $output;
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
			$city = end(explode('/', $timeZone));
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

		if(!empty($ip) && $this->gi)
		{
			$location = $this->gi->city($ip);

			try{
				if(!empty($location)){

					$loc_array=array('ip'=>"$ip"
									,'lat'=>$location->location->latitude
									,'lng'=>$location->location->longitude
									,'city'=>(self::getLanguageValue($location->city->names) != '') ? self::getLanguageValue($location->city->names) : self::parseCityFromTimeZone($location->location->timeZone)
									,'state'=>(!self::displayState($location->mostSpecificSubdivision->isoCode) && self::getLanguageValue($location->country->names) !='')
										?utf8_encode(self::getLanguageValue($location->country->names))
										:utf8_encode($location->mostSpecificSubdivision->isoCode)//shouldn't do display logic here but...
									,'zip'=>!empty($location->postal->code)?$location->postal->code:''
									,'countryName'=>self::getLanguageValue($location->country->names)
									,'countryCode'=>!empty($location->country->isoCode)?$location->country->isoCode:''
									,'w3w'=>($this->w3w)?$this->w3w->getWords($location->location->latitude,$location->location->longitude):""
								);

					if(!empty($this->last_location)){
						if($this->last_location['city']!=$loc_array['city']){
							$distance = self::getDistance($this->last_location,$loc_array);
							if(!empty($distance)){
								$this->total_kilometers += $distance;
								$this->total_miles += ($distance/1.609344);
							}
							$loc_array['distance']=array('from'=>array('hopnum'=>($hopnum-1)),'miles'=>!empty($distance)?($distance/1.609344):0,'kilometers'=>$distance);
						} else {
							$loc_array['distance']=array('from'=>array('hopnum'=>($hopnum-1)),'miles'=>0,'kilometers'=>0);
						}
					}
					$this->last_location=$loc_array;
				}
			}
			catch(Exception $ex)
			{
				error_log($ex->getMessage());
			}
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

	private function getDistance($from, $to, $unit='k') {
		$lat1 = $from['lat'];
		$lon1 = $from['lng'];
		$lat2 = $to['lat'];
		$lon2 = $to['lng'];

		$lat1 *= (pi()/180);
		$lon1 *= (pi()/180);
		$lat2 *= (pi()/180);
		$lon2 *= (pi()/180);

		$dist = 2*asin(sqrt( pow((sin(($lat1-$lat2)/2)),2) + cos($lat1)*cos($lat2)*pow((sin(($lon1-$lon2)/2)),2))) * 6378.137;

		if ($unit=="m") {
			$dist = ($dist / 1.609344);
		}

		return $dist;
	}

	private function logTraffic($route,$client){
		if(!$this->connection)
			return false;

		//append the client hop
		if(!empty($client))
			$route[]=$client;

		$collection = $this->connection->getConn()->traffic;
		$test = $collection->insert(array('date'=>(int)date('U'),'route'=>$route));
	}

	public function getTraffic($since){
		if(!$this->connection)
			return false;

		$query = array('date'=>array('$gte'=>(int)$since));

		$collection = $this->connection->getConn()->traffic;
		$cursor = $collection->find($query);
		$results = iterator_to_array($cursor,false);
		return json_encode($results);
	}

	private function logApp($version){
		if(!$this->connection)
			return false;

		$collection = $this->connection->getConn()->stats;
		$collection->update(array('version'=>$version,'day'=>(int)date('Ymd'))
			,array('$inc'=>array("count"=>1)
					,'$set'=>array('day'=>(int)date('Ymd')))
			,array('upsert'=>true,'w'=>0,'multiple'=>false));

	}

	private function logCountry($country_code,$origin){
		if(!$this->connection)
			return false;

		$field = $origin==1?"origin_count":"count";
		$collection = $this->connection->getConn()->countries;
		$collection->update(array('iso'=>new MongoRegex('/^'.$country_code.'$/i'))
			,array('$inc'=>array("$field"=>1))
			,array('upsert'=>false,'w'=>0,'multiple'=>false));
	}

	private function logState($state_abbr,$origin){
		if(!$this->connection)
			return false;

		$field = $origin==1?"origin_count":"count";
		$collection = $this->connection->getConn()->states;
		$collection->update(array('abbr'=>new MongoRegex('/^'.$state_abbr.'$/i'))
			,array('$inc'=>array("$field"=>1))
			,array('upsert'=>false,'w'=>0,'multiple'=>false));
	}

	private function getCountryCode($country){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->countries;
		$cursor = $collection->find(array('name'=>new MongoRegex('/^'.$country.'$/i')),array('iso'=>1))->limit(1);
		$results = iterator_to_array($cursor,false);

		if(Error::hasError() || empty($results[0]['iso']))
			return false;
		else{
			return true;
		}
	}

	private function getCountryName($iso){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->countries;
		$cursor = $collection->find(array('iso'=>new MongoRegex('/^'.$iso.'$/i')),array('printable_name'=>1))->limit(1);
		$results = iterator_to_array($cursor,false);

		if(Error::hasError() || empty($results[0]['printable_name']))
			return false;
		else{
			return $results[0]['printable_name'];
		}
	}

	private function isUnitedStates($state){
		if(!$this->connection)
			return false;

		$results = array();
		$collection = $this->connection->getConn()->states;
		$cursor = $collection->find(array('abbr'=>new MongoRegex('/^'.$state.'$/i')),array('name'=>1))->limit(1);
		$results = iterator_to_array($cursor,false);

		if(Error::hasError() || empty($results[0]['name']))
			return false;
		else{
			return true;
		}
	}
}
?>
