<?php
/** MailHops API Class
 *
 * @package	mailhops
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0
 */
class MailHops{

	private $ips;
	
	private $json_route;	
	
	private $last_location		= null;	
	
	private $total_miles		= 0;
	
	private $total_kilometers	= 0;
	
	private $show_weather		= false;
	
	private $reverse_host		= true;
	
	private $db_on				= false;
	
	const IMAGE_URL 			= 'http://api.mailhops.com/v1/images/';
	
	//path from DOCUMENT_ROOT
	const IMAGE_DIR 			= '/v1/images/';
	
	//Path to GeoIP dat file
	const GEOIP_FILE 			= '/var/www/geoip/GeoLiteCity.dat';
	
	//Google DNS server
	const DNS_SERVER 			= '8.8.8.8';
		
	public function __construct(){

		if(!empty($_GET['route']))
			$this->ips = explode(',',$_GET['route']);
		else if(!empty($_GET['r']))
			$this->ips = explode(',',$_GET['r']);
		
		$this->total_miles=0;
		$this->total_kilometers=0;
		
		$app_version = isset($_GET['app'])?$_GET['app']:'';
		if(empty($app_version))
			$app_version=isset($_GET['a'])?$_GET['a']:'';
		
		if(!empty($app_version)){
			if(isset($_GET['pb']) && Util::getVersion($app_version) <= '065')
				$this->ips=array_reverse($this->ips);
			else if(isset($_GET['tb']) && Util::getVersion($app_version) <= '05')
				$this->ips=array_reverse($this->ips);
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
	
	$show_client = isset($_GET['showclient'])&&!Util::toBoolean($_GET['showclient'])?false:true;
	$show_client = isset($_GET['c'])&&!Util::toBoolean($_GET['c'])?false:$show_client;
	$client_ip=self::getRealIpAddr();
	$is_mailhops_site = isset($_GET['test'])?true:false;
	$whois = isset($_GET['whois'])?true:false;
	//track start time
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$start = $time;
	
	$mail_route=array();

	//loop through IPs
	$hopnum=1;
	$origin=1;
	
	if(!empty($this->ips)){
	
		//get IP to check DNSBL
		$dnsbl_ip = self::getDNSBL_IP();
		$dnsbl_checked = false;
		
		foreach($this->ips as $ip){

			if(!self::isPrivate($ip)){
			
				$route = self::getLocation($ip,$hopnum);
				
				$hostname=self::getRHost($ip);
				if(!empty($hostname))
					$route['host']=$hostname;
				if($whois || (!$is_mailhops_site && !$dnsbl_checked && $ip==$dnsbl_ip)){
					$dnsbl_checked=true;
					$route['dnsbl']=self::getDNSBL($ip);					
				}	
				
				if(!empty($route['countryCode'])){
					if(empty($route['countryName']))
						$route['countryName']=self::getCountryName($route['countryCode']);
					if(file_exists($_SERVER['DOCUMENT_ROOT'].self::IMAGE_DIR.'flags/'.strtolower($route['countryCode']).'.png'))
						$route['flag']=self::IMAGE_URL.'flags/'.strtolower($route['countryCode']).'.png';
				}
				
				if($this->db_on){
					if(!empty($route['countryCode'])){
						self::logCountry($route['countryCode'],$origin);
						if($route['countryCode']=='US' && !empty($route['state']))
							self::logState($route['state'],$origin);
					}
				} 
				$origin++;
			}
			else
				$route = array('ip'=>$ip,'private'=>true,'local'=>true);
				
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
	if($show_client==true && !empty($client_ip)){

		$route=array();
		if(!empty($client_ip)){
			$route = self::getLocation($client_ip,$hopnum);
		}
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
			$mail_route[]=$route;
		}
	}
	//track end time
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);

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
	
	private function getHost($ip)
	{
		require_once 'Net/DNS2.php';
		
		$resolver = new Net_DNS2_Resolver();
		$response = $resolver->query('php.net', 'MX');
		if ($response) {
		  foreach ($response->answer as $rr) {
		    $rr->display();
		  }
		  if (count($response->additional)) {
		    foreach ($response->additional as $rr) {
		      $rr->display();
		    }
		  }
		}
	}
	
	private function getDNSBL($ip)
	{
		require_once 'Net/DNSBL.php';
		
		$dnsbl = new Net_DNSBL();
		$dnsbl->setBlacklists(array('zen.spamhaus.org'));
		if($dnsbl->isListed($ip,false)){
			$results = $dnsbl->getDetails($ip);
			if(substr($results['record'],0,7)=='127.0.0')
				return array('listed'=>true,'record'=>$results['record']);
			else
				return array('listed'=>false);
				
		}
		
		return array('listed'=>false);
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
		$output = exec('host '.$ip.' '.self::DNS_SERVER);
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
		$return=array();
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
	
	private function getLocationMaxMind($ip,$hopnum)
	{
		require_once 'Net/GeoIP.php';
		
		$loc = '';
		$loc_array=array('ip'=>"$ip");
		if(!empty($ip) && file_exists(self::GEOIP_FILE))
		{
			try{
				$geoip = Net_GeoIP::getInstance(self::GEOIP_FILE, Net_GeoIP::STANDARD);
				$location = $geoip->lookupLocation($ip);
				if(!empty($location->countryCode)){
					$loc_array=array('ip'=>"$ip"
									,'lat'=>$location->latitude
									,'lng'=>$location->longitude
									,'city'=>utf8_encode($location->city)
									,'state'=>!self::displayState($location->region)&&!empty($location->countryName)?utf8_encode($location->countryName):utf8_encode($location->region)
									,'countryName'=>utf8_encode($location->countryName)
									,'countryCode'=>$location->countryCode
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
		
	public function isPrivate($ip){
		
		if(substr($ip,0,3)=='10.' //Class A
			|| substr($ip,0,7)=='172.16.' //Class B
			|| substr($ip,0,7)=='172.17.' //Class B
			|| substr($ip,0,7)=='172.18.' //Class B
			|| substr($ip,0,7)=='172.19.' //Class B
			|| substr($ip,0,7)=='172.20.' //Class B
			|| substr($ip,0,7)=='172.21.' //Class B
			|| substr($ip,0,7)=='172.22.' //Class B
			|| substr($ip,0,7)=='172.23.' //Class B
			|| substr($ip,0,7)=='172.24.' //Class B
			|| substr($ip,0,7)=='172.25.' //Class B
			|| substr($ip,0,7)=='172.26.' //Class B
			|| substr($ip,0,7)=='172.27.' //Class B
			|| substr($ip,0,7)=='172.28.' //Class B
			|| substr($ip,0,7)=='172.29.' //Class B
			|| substr($ip,0,7)=='172.30.' //Class B
			|| substr($ip,0,7)=='172.31.' //Class B
			|| substr($ip,0,8)=='192.168.' //Class C
			|| substr($ip,0,8)=='169.254.' //Class M$
			|| substr($ip,0,4)=='127.' //Localhost
			|| substr($ip,0,4)=='255.'//BCast
			|| substr($ip,0,2)=='0.') //Whoops
		{
		return true;
		} else {
		return false;
		}
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
	
	
	private function logApp($version){
		if(!$this->db_on)
			return false;
			
		$connection = new Connection();
		if($connection->Connect()){
			$collection = $connection->getConn()->stats;
			$collection->update(array('version'=>$version,'day'=>(int)date('Ymd'))
				,array('$inc'=>array("count"=>1)
						,'$set'=>array('day'=>(int)date('Ymd')))
				,array('upsert'=>true,'safe'=>true,'multiple'=>false));	
			$connection->DisConnect();
		}
	}
	
	private function logCountry($country_code,$origin){
		if(!$this->db_on)
			return false;
			
		$field = $origin==1?"origin_count":"count";
		$connection = new Connection();
		if($connection->Connect()){
			$collection = $connection->getConn()->countries;
			$collection->update(array('iso'=>new MongoRegex('/^'.$country_code.'$/i'))
				,array('$inc'=>array("$field"=>1))
				,array('upsert'=>false,'safe'=>true,'multiple'=>false));	
			$connection->DisConnect();
		}
	}
	
	private function logState($state_abbr,$origin){
		if(!$this->db_on)
			return false;
			
		$field = $origin==1?"origin_count":"count";
		$connection = new Connection();
		if($connection->Connect()){
			$collection = $connection->getConn()->states;
			$collection->update(array('abbr'=>new MongoRegex('/^'.$state_abbr.'$/i'))
				,array('$inc'=>array("$field"=>1))
				,array('upsert'=>false,'safe'=>true,'multiple'=>false));	
			$connection->DisConnect();
		}
	}
	
	private function getCountryCode($country){
		if(!$this->db_on)
			return false;
		
		$connection = new Connection();
		$results = array();
		if($connection->Connect()){
			$collection = $connection->getConn()->countries;
			$cursor=$collection->find(array('name'=>new MongoRegex('/^'.$country.'$/i')),array('iso'=>1))->limit(1);
			$results = iterator_to_array($cursor,false);				
			$connection->DisConnect();
		}	
		if(Error::hasError() || empty($results[0]['iso']))
			return false;
		else{
			return true;	
		}
	}
	
	private function getCountryName($iso){		
		if(!$this->db_on)
			return false;
			
		$connection = new Connection();
		$results = array();
		if($connection->Connect()){
			$collection = $connection->getConn()->countries;
			$cursor=$collection->find(array('iso'=>new MongoRegex('/^'.$iso.'$/i')),array('printable_name'=>1))->limit(1);
			$results = iterator_to_array($cursor,false);				
			$connection->DisConnect();
		}	
		if(Error::hasError() || empty($results[0]['printable_name']))
			return false;
		else{
			return $results[0]['printable_name'];	
		}
	}
	
	private function isUnitedStates($state){
		if(!$this->db_on)
			return false;
		
		$connection = new Connection();
		$results = array();
		if($connection->Connect()){
			$collection = $connection->getConn()->states;
			$cursor=$collection->find(array('abbr'=>new MongoRegex('/^'.$state.'$/i')),array('name'=>1))->limit(1);
			$results = iterator_to_array($cursor,false);				
			$connection->DisConnect();
		}	
		if(Error::hasError() || empty($results[0]['name']))
			return false;
		else{
			return true;	
		}
	}	
}
?>