<?php

/** DB Connection Class
 *
 * @package	mailhops
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	1.0
 */
class Connection
{
	/*MongoDB Connection info for use with MailHops
		Signup: mongolab.com
		Download: mongorestore binary is available from http://www.mongodb.org/downloads
		Run: mongorestore -h [host:port] -d mailhops -u [user] -p [pass] mailhops/v1/mongo/mailhops/
	*/
	protected $user = '';
	
	protected $pass = '';
	
	//host:port
	protected $host = '';
	
	protected $db 	= 'mailhops';
		
	/*
	 * General Connection settings
	 */
	
	protected $link; 
	
	protected $conn;
	
	protected $debug = false;
			
	public function __construct($db=null){
		if(!empty($db))
			$this->db=$db;		
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
		try
		{
			$link = new MongoClient("mongodb://".$this->user.":".$this->pass."@".$this->host.'/'.$this->db);
			
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