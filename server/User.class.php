<?php
namespace Websocket;

class User {
	public $id;
	private $socket;
	public $handshake = false;
	public $closed = false;
	public $channels = [];
	public $cookie;

	//Send a message to a specific user, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
	public function send($payload, $opcode=0x1){
		if(!$this->handshake)
			return;

		$payload = WebSocket::frame($payload, $opcode);
		socket_write($this->socket, $payload, strlen($payload));
	}
}