<?php
namespace Websocket;

class Write extends \Worker {

	$this->user = null;
	
	function __construct(User &$user, Websocket $ws){
		$this->user = $user;
		$this->ws = $websocket;
	}

	public function run(){
		//Pull messages from SQS for this user and write them to them
	}
}