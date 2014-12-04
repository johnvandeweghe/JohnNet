<?php
class MySQLPing implements Action {

	private $ws;
	private $route;
	
	public function __construct(WebSocket $ws, $route){
		$this->ws = $ws;
		$this->route = $route;
	}

	public function run(){
		try {
			$this->ws->routes[$this->route]->con->query("select 1");
		} catch(PDOException $e){
			if (!defined('INCLUDED')) {
				define('INCLUDED', true);
			}
			require("../../config.php");
			$this->ws->routes[$this->route]->con = new PDO("mysql:host=$mysql_hostname;dbname=$mysql_database", $mysql_username, $mysql_password, $DB_DEFAULT_OPT);
		}
	}

}