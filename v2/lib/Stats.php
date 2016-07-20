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

  protected $host = '';

	protected $port = '8086';

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
}
?>
