<?php
namespace Websocket;

class Writeer extends \Worker {

	private $user = null;

	function __construct(User &$user, Websocket $ws){
		$this->user = $user;
		$this->ws = $ws;
	}

	public function run(){
		//Pull messages from SQS for this user and write them to them
	}
}
