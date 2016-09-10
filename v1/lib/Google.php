<?php
class Google {

  public function __construct(){

  }

  public function GeoCode($loc_array){

		$address=str_replace(' ', '+', $loc_array['city'].','.$loc_array['countryCode']);
		$address=str_replace('%20', '+', $address);

		$res = Util::curlData("https://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=$address");

    $json_response = json_decode($res);

    if(!empty($json_response->error_message)){
			Error::setError(array('from'=>'Google Geocode','msg'=>$json_response->error_message));
			return $loc_array;
		}
		return self::GeoCodeFormatted($json_response,$loc_array);

	}

  private function GeoCodeFormatted($json_response,$loc_array){

		if(!empty($json_response->results)){
			foreach($json_response->results as $result){
				//get geo
				if(!empty($result->geometry->location->lat) && !empty($result->geometry->location->lng)){
					$loc_array['lat']=$result->geometry->location->lat;
					$loc_array['lng']=$result->geometry->location->lng;
				}
			}

		} else if(!empty($json_response->error_message)){
			Error::setError(array('from'=>'Google Geocode','msg'=>$json_response->error_message));
		}
		return $loc_array;
	}

}
?>
