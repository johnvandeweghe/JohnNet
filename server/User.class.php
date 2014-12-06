<?php
namespace Websocket;

class User {
	public $id;
	private $socket;
	public $handshake = false;
	public $closed = false;
	public $channels = [];
	public $cookie;

	public function __construct($socket){
		$this->socket = $socket;
	}

	//Send a message to a specific user, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
	public function write($payload, $opcode=0x1){
		if(!$this->handshake)
			return;

		$payload = WebSocket::frame($payload, $opcode);
		socket_write($this->socket, $payload, strlen($payload));
	}

	public function write_raw($payload){
		socket_write($this->socket, $payload, strlen($payload));
	}

	public function read(&$buffer){
		$changed = [$this->socket];
		$write = NULL;
		$except = NULL;
		if(socket_select($changed,$write,$except,5) > 0) {
			return @socket_recv($this->socket, $buffer, 2048, 0);
		}
		return 'Nothing';
	}

	public function close(){
		socket_close($this->socket);
	}
}
