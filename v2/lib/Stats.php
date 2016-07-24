<?php
/** Stats Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

class Stats{

  protected $user = '';

	protected $pass = '';

  protected $host = 'localhost';

	protected $port = '4444';

	protected $db 	= 'mailhops';

  public function __construct($config){

		if(getenv('INFLUXDB_HOST'))
			$this->host = getenv('INFLUXDB_HOST');
		if(!empty($config->host))
			$this->host = $config->host;

		if(getenv('INFLUXDB_PORT'))
			$this->port = getenv('INFLUXDB_PORT');
		else if(!empty($config->port))
			$this->port = $config->port;

    if(getenv('INFLUXDB_USER'))
			$this->user = getenv('INFLUXDB_USER');
		else if(!empty($config->user))
			$this->user = $config->user;

		if(getenv('INFLUXDB_PASS'))
			$this->pass = getenv('INFLUXDB_PASS');
		else if(!empty($config->pass))
			$this->pass = $config->pass;

    if(getenv('INFLUXDB_DB'))
			$this->db = getenv('INFLUXDB_DB');
		else if(!empty($config->db))
			$this->db = $config->db;

    }

    public function Connect()
  	{
      try {
        $client = new InfluxDB\Client($this->host, $this->port, $this->user, $this->pass);
        $client->setDriver(new \InfluxDB\Driver\UDP($client->getHost(), $this->port));
        if(!$client)
          return false;
        $database = $client->selectDB($this->db);
        //create the db if it doesn't exist
        if (!$database->exists()) {
          $database->create();
        }
        return $database;
      } catch(Exception $ex){
        Error::setError($ex->getMessage());
        return false;
      }
  	}

    private function saveStat($hops){
        $connection = $this->Connect();
        if(!$connection)
          return false;

        $points = [
            new Point(
                'hops',
                $hops,
                ['host' => $_SERVER['SERVER_ADDR']],
                null,
                exec('date +%s%N') // this will produce a nanosecond timestamp on Linux ONLY
            ),
            new Point(
                'client',
                1,
                ['host' => $_SERVER['SERVER_ADDR']],
                null,
                exec('date +%s%N') // this will produce a nanosecond timestamp on Linux ONLY
            )
        ];

        $result = $connection->writePoints($points);

        print_r($result);
    }
}
?>
