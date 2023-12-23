<?php

/**
 * MySQL Class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

class MySQL {

	/**
	 * SQL Host
	 * @var String
	 */
	private $MYSQL_HOST 		= MYSQL_HOST;

	/**
	 * SQL User
	 * @var String
	 */
	private $MYSQL_USER 		= MYSQL_USER;

	/**
	 * SQL Database
	 * @var String
	 */
	private $MYSQL_DATABASE		= MYSQL_DATABASE;

	/**
	 * SQL Password
	 * @var String
	 */
	private $MYSQL_PASSWD		= MYSQL_PASSWD;

	/**
	 * SQL Port
	 * @var Int
	 */
	private $MYSQL_PORT 		= MYSQL_PORT;

	/**
	 * Last req
	 * @var Object
	 */
	private $LAST_REQ = null;

	/**
	 * PDO BDD
	 * @var PDO
	 */
	private $bdd = null;

	/**
	 * Get SQL Connexion PDF
	 * @return PDO         	PDO Connexion
	 */
	function getSqlConnexion(){

		// Check if bdd is not saved in local
		if($this->bdd != null) return $this->bdd;

		try {
			// Init BDD
		  $this->bdd = new PDO('mysql:host='.$this->MYSQL_HOST.';port='.$this->MYSQL_PORT.';dbname='.$this->MYSQL_DATABASE, $this->MYSQL_USER, $this->MYSQL_PASSWD, array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
		} catch (Exception $e) {
		  throw new Exception($e->getMessage(), 1);
		  die();
		}
		return $this->bdd;
	}

	/**
	 * Fetch data in database
	 * @param  String          $query SQL Query ("SELECT * FROM ... WHERE ...")
	 * @param  Array          $def   SQL Def ['id_key' => 'xxxx', ...]
	 *
	 * @return Array                SQL Result
	 */
	function querySqlRequest($query, $def = []){
		$req = $this->getSqlConnexion()->prepare($query);
		$req->execute($def);
		$r = $req->fetchAll(\PDO::FETCH_ASSOC);
		$req->closeCursor();
		return $r;
	}

	/**
	 * Count SQL
	 * @param  String          $query SQL Query ("SELECT * FROM ... WHERE ...")
	 * @param  Array          $def   SQL Def ['id_key' => 'xxxx', ...]
	 *
	 * @return Int                 Row counted
	 */
	function countSqlRequest($query, $def = []){
		$req = $this->getSqlConnexion()->prepare($query);
		$req->execute($def);
		$r = $req->rowCount();
		$req->closeCursor();
		return $r;
	}

	/**
	 * Execute SQL Request (INSERT, UPDATE, DELETE, ...)
	 * @param  String          $query SQL Query ("SELECT * FROM ... WHERE ...")
	 * @param  Array          $def   SQL Def ['id_key' => 'xxxx', ...]
	 *
	 * @return Boolean                True = SQL Request passsed, False = Fail SQL
	 */
	function execSqlRequest($query, $def = []){
		$req= $this->getSqlConnexion()->prepare($query);
		$status = $req->execute($def);
		$this->LAST_REQ = $req;
		$req->closeCursor();
		return $status;
	}

	/**
	 * Get last error detect in SQL PDO
	 * @return String       PDO Error
	 */
	public function getLastError(){
		return $this->LAST_REQ->errorInfo();
	}

}

?>
