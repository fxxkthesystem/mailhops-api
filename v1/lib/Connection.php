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

	protected $host = 'localhost';

	protected $port = '27017';

	protected $db 	= 'mailhops';

	/*
	 * General Connection settings
	 */

	protected $link;

	protected $conn;

	protected $debug = false;

	public function __construct($config){

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
		$error='';
		if(empty($this->host))
			return false;

		try
		{
			if(!empty($this->user) && !empty($this->pass))
				$link = new MongoClient("mongodb://".$this->user.":".$this->pass."@".$this->host.':'.$this->port.'/'.$this->db);
			else
				$link = new MongoClient("mongodb://".$this->host.':'.$this->port.'/'.$this->db);

			if(!empty($link)){
				$this->link=$link;
				$this->conn=$link->selectDB($this->db);
				return true;
			}
			else
				return false;
		}
		catch (MongoConnectionException $e)
		{
			Error::setError('Error connecting to server. '.$e->getMessage());
		}
		catch (MongoException $e)
		{
		  	Error::setError('Error: ' . $e->getMessage());
		}

		return false;
	}
}
