<?php
/** Accounts Class
 *
 * @package	mailhops-api
 * @author  Andrew Van Tassel <andrew@andrewvantassel.com>
 * @version	2.0.0
 */

class Accounts{

  private $connection = null;

  private $account = null;

  public function __construct($api_key,$connection){
    //check for a valid api key
    $this->connection = $connection;

    self::setUser($api_key);
  }

  private function setUser($api_key){

    $collection = $this->connection->getConn()->accounts;
    $this->account = $collection->findOne(array('_id'=>$api_key));
    
  }


  private function saveRoute($route){
    $collection = $this->connection->getConn()->routes;
    $collection->insert(array('$set'=>array('userId'=>$this->account->_id,'date'=>date('c'),'route'=>$route)));
  }
}
?>
