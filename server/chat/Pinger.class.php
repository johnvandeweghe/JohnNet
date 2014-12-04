<?php
class Pinger implements Action {

	private $ws;
	private $route;
	
	public function __construct(WebSocket $ws,$route){
		$this->ws = $ws;
		$this->route = $route;
	}

	public function run(){
		$this->ws->sendToRoute($this->route, "PING", 0x9);
	}

}