<?php
/** DB Connection Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0.0
 */

class Connection
{
	/*MongoDB Connection info for use with MailHops
		Signup: mlab.com
		Download: mongorestore binary is available from http://www.mongodb.org/downloads
		Run: mongorestore -h [host:port] -d mailhops -u [user] -p [pass] v1/mongo/mailhops/
	*/
	protected $user = '';

	protected $pass = '';

	protected $host = '';

	protected $port = '27017';

	protected $db 	= 'mailhops';

	protected $connectionString = '';

	/*
	 * General Connection settings
	 */

	protected $link = null;

	protected $conn = null;

	protected $debug = false;

	public function __construct($config){

		// this takes precendence
		if(getenv('MONGO_CONNECTION'))
			$this->connectionString = getenv('MONGO_CONNECTION');
		else if(!empty($config->connectionString))
			$this->connectionString = $config->connectionString;

		if(getenv('MONGO_HOST'))
			$this->host = getenv('MONGO_HOST');
		if(!empty($config->host))
			$this->host = $config->host;

		if(getenv('MONGO_PORT'))
			$this->port = getenv('MONGO_PORT');
		else if(!empty($config->port))
			$this->port = $config->port;

		if(getenv('MONGO_USER'))
			$this->user = getenv('MONGO_USER');
		else if(!empty($config->user))
			$this->user = $config->user;

		if(getenv('MONGO_PASS'))
			$this->pass = getenv('MONGO_PASS');
		else if(!empty($config->pass))
			$this->pass = $config->pass;

		if(getenv('MONGO_DB'))
			$this->db = getenv('MONGO_DB');
		else if(!empty($config->db))
			$this->db = $config->db;
	}

	public function getConn()
	{
		return $this->conn;
	}

	public function getLink()
	{
		return $this->link;
	}

	/*
	 * Connection functions
	 * allow these to be called to allow for multiple queries per connection
	 */

	public function Connect()
	{
		if(!empty($this->conn))
			return true;

		try
		{
			if(!empty($this->connectionString) && !empty($this->connectionString))
				$link = new MongoDB\Client($this->connectionString);
			else if(!empty($this->user) && !empty($this->pass))
				$link = new MongoDB\Client("mongodb://".$this->user.":".$this->pass."@".$this->host.':'.$this->port.'/'.$this->db);
			else
				$link = new MongoDB\Client("mongodb://".$this->host.':'.$this->port.'/'.$this->db);

			if(!empty($link)){
				$link->listDatabases();//test the connection
				$this->link=$link;
				$this->conn=$link->selectDatabase($this->db);
				return true;
			}
			else
				return false;
		}
		catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e)
		{
			Error::setError('Error connecting to server. '.$e->getMessage());
		}
		catch (MongoDB\Driver\Exception\Exception $e)
		{
		  Error::setError('Error: ' . $e->getMessage());
		}

		return false;
	}
}
