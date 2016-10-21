<?php
/** Cachet Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

class CachetIO {

  private $connection        = null;

  private $config				     = null;

  private $startMetricId     = 0;

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

    // set metric id
    self::getMetricId();
  }

  // this is run as part of the setup
  public function setupMetrics(){
    if(!$this->connection || empty($this->config->cachetio->url))
      return;
    $metrics = [
      ['name' => 'Emails'
        ,'description' => 'Total emails'
        ,'suffix' => 'emails'
        ,'default_value' => 0
        ,'display_chart' => 1
        ,'calc_type' => 0]
      ,['name' => 'Total Hops'
        ,'description' => 'Total hops per email'
        ,'suffix' => 'hops'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 0]
      ,['name' => 'Average Hops'
        ,'description' => 'Average hops per email'
        ,'suffix' => 'hops'
        ,'default_value' => 0
        ,'display_chart' => 1
        ,'calc_type' => 1]
      ,['name' => 'Total Miles'
        ,'description' => 'Total miles traveled'
        ,'suffix' => 'mi'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 0]
      ,['name' => 'Average Miles'
        ,'description' => 'Average miles traveled per email'
        ,'suffix' => 'mi'
        ,'default_value' => 0
        ,'display_chart' => 1
        ,'calc_type' => 1]
      ,['name' => 'Total Kilometers'
        ,'description' => 'Total kilometers traveled'
        ,'suffix' => 'km'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 0]
      ,['name' => 'Average Kilometers'
        ,'description' => 'Average kilometers traveled per email'
        ,'suffix' => 'km'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 1]
      ,['name' => 'Average Response Time'
        ,'description' => 'Average response time'
        ,'suffix' => 'seconds'
        ,'default_value' => 0
        ,'display_chart' => 1
        ,'calc_type' => 1]
      ,['name' => 'Total Trip Time'
        ,'description' => 'Total trip time per email'
        ,'suffix' => 'trip'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 1]
      ,['name' => 'Average Trip Time'
        ,'description' => 'Average trip time per email'
        ,'suffix' => 'trip'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 1]
      ,['name' => 'Total Trip Time Under 10 seconds'
        ,'description' => 'Total trip time per email under 10 seconds'
        ,'suffix' => 'trip'
        ,'default_value' => 0
        ,'display_chart' => 0
        ,'calc_type' => 1]
      ,['name' => 'Average Trip Time Under 10 seconds'
        ,'description' => 'Average trip time per email under 10 seconds'
        ,'suffix' => 'trip'
        ,'default_value' => 0
        ,'display_chart' => 1
        ,'calc_type' => 1]
    ];
    $client = new Client();

    try {
      foreach($metrics as $metric){
        $client->request('POST',$this->config->cachetio->url.'/api/v1/metrics',[
          'headers' => [
            'X-Cachet-Token' => $this->config->cachetio->api_key
          ]
          ,'form_params' => [
              'name' => $metric['name']
              ,'description' => $metric['description']
              ,'suffix' => $metric['suffix']
              ,'default_value' => $metric['default_value']
          ]
        ]);
      }
    } catch(GuzzleHttp\Exception\ClientException $ex){
      MError::setError('CachetIO Error.  Please verify or remove your CachetIO settings.');
    }
  }

  public function getMetricId(){
    $client = new Client();

      $res = $client->request('GET',$this->config->cachetio->url.'/api/v1/metrics',[
          'X-Cachet-Token' => $this->config->cachetio->api_key
      ]);

      if($res->getStatusCode() == 200)
      {
        $return = $res->getBody();
        $contents = json_decode($return->getContents());
        // if no metrics yet then create them
        if(empty($contents->meta->pagination->total)){
          self::setupMetrics();
        } else if(!empty($contents->data[0]->id)){
          // otherwise set the first metric id
          $this->startMetricId = $contents->data[0]->id;
        }
      }
  }

  public function createMetricPoint($metric_id,$value){

    $client = new Client();

    try {
      $res = $client->request('POST',$this->config->cachetio->url.'/api/v1/metrics/'.$metric_id.'/points',[
        'headers' => [
          'X-Cachet-Token' => $this->config->cachetio->api_key
        ]
        ,'form_params' => [
          'id' => $metric_id
          ,'value' => $value
          ,'timestamp' => date('U')
        ]
      ]);

      if($res->getStatusCode() == 200)
      {
        $return = json_encode($res->getBody());
      }

    } catch(GuzzleHttp\Exception\ClientException $ex){
      MError::setError('CachetIO Error.  Please verify or remove your CachetIO settings.');
    }
  }

  public function getMetrics(){

    if(!$this->connection || empty($this->config->cachetio->url) || empty($this->startMetricId))
      return false;

    // get metrics from this date
    $date = date('U');

    $collection = $this->connection->getConn()->traffic;
    $other_totals = $collection->aggregate([
      ['$match' => ['date' => ['$lte' => (int)$date]]]
      ,['$project' => [
          'miles' => '$distance.miles'
          ,'kilometers' => '$distance.kilometers'
          ,'milliseconds' => '$distance.milliseconds'
          ,'millisecondsLte10k' => ['$cond' => [ [['$gte' => ['$distance.milliseconds',0]],['$lte' => ['$distance.milliseconds',10000]]] ,'$distance.milliseconds',0]]
          ,'hops' => '$route'
          ,'response_time' => '$time'
        ]
      ]
      ,['$group' => [
          '_id' => '$item'
          ,'total_mi' => ['$sum' => '$miles']
          ,'avg_mi' => ['$avg' => '$miles']
          ,'total_km' => ['$sum' => '$kilometers']
          ,'avg_km' => ['$avg' => '$kilometers']
          ,'total_hops' => ['$sum' => ['$size'=>'$hops']]
          ,'avg_hops' => ['$avg' => ['$size'=>'$hops']]
          ,'total_emails' => ['$sum' => 1]
          ,'avg_response_time' => ['$avg' => '$response_time']
          ,'total_trip_time' => ['$sum' => '$milliseconds']
          ,'avg_trip_time' => ['$avg' => '$milliseconds']
          ,'total_trip_time_Lte10k' => ['$sum' => '$millisecondsLte10k']
          ,'avg_trip_time_Lte10k' => ['$avg' => '$millisecondsLte10k']
        ]
      ]
    ]);

    $metrics = $other_totals->toArray();

    // don't continue if there are no metrics
    if(empty($metrics)){
      MError::setError('No traffic found to create metrics.');
      return;
    }

    // post metrics from this date
    self::createMetricPoint($this->startMetricId,$metrics[0]['total_emails']);
    self::createMetricPoint($this->startMetricId+1,$metrics[0]['total_hops']);
    self::createMetricPoint($this->startMetricId+2,$metrics[0]['avg_hops']);
    self::createMetricPoint($this->startMetricId+3,$metrics[0]['total_mi']);
    self::createMetricPoint($this->startMetricId+4,$metrics[0]['avg_mi']);
    self::createMetricPoint($this->startMetricId+5,$metrics[0]['total_km']);
    self::createMetricPoint($this->startMetricId+6,$metrics[0]['avg_km']);
    self::createMetricPoint($this->startMetricId+7,$metrics[0]['avg_response_time']);

    // the following are only available in V2
    if(!empty($metrics[0]['total_trip_time'])){
      $metrics[0]['total_trip_time'] = $metrics[0]['total_trip_time']/1000; //to seconds
      self::createMetricPoint($this->startMetricId+8,$metrics[0]['total_trip_time']);
    }
    if(!empty($metrics[0]['avg_trip_time'])){
      $metrics[0]['avg_trip_time'] = $metrics[0]['avg_trip_time']/1000; //to seconds
      self::createMetricPoint($this->startMetricId+9,$metrics[0]['avg_trip_time']);
    }
    if(!empty($metrics[0]['total_trip_time_Lte10k'])){
      $metrics[0]['total_trip_time_Lte10k'] = $metrics[0]['total_trip_time_Lte10k']/1000; //to seconds
      self::createMetricPoint($this->startMetricId+10,$metrics[0]['total_trip_time_Lte10k']);
    }
    if(!empty($metrics[0]['avg_trip_time_Lte10k'])){
      $metrics[0]['avg_trip_time_Lte10k'] = $metrics[0]['avg_trip_time_Lte10k']/1000; //to seconds
      self::createMetricPoint($this->startMetricId+11,$metrics[0]['avg_trip_time_Lte10k']);
    }

    // clear metrics from this date
    self::clearMetrics($date);

    return $metrics[0];
  }

  public function clearMetrics($date){
		if(!$this->connection)
			return false;

		$this->connection->getConn()->traffic->deleteMany(['date' => ['$lte' => (int)$date]]);
	}
}
?>
