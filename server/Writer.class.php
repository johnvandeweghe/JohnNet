<?php
namespace WebSocket;

class Writer extends \Worker {

	private $user = null;

	function __construct(User &$user){
		$this->user = $user;
	}

	public function run(){
		//Pull messages from SQS for this user and write them to them
	}
}
