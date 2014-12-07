<?php
namespace WebSocket;

class User extends \Stackable {
	public $id;
	public $socket;
	public $handshake = false;
	public $closed = false;
	public $channels = [];
	public $cookie;
	public $extensions = [];

	public function __construct(&$socket){
		$this->socket = $socket;
	}

	//Send a message to a specific user, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
	public function write($payload, $opcode=0x1){
		if(!$this->handshake)
			return;

		$payload = WebSocket::frame($payload, $opcode);
		$this->write_raw($payload);
	}

	public function write_raw($payload){
		fwrite($this->socket, $payload, strlen($payload));
	}

	public function read(){
		if(!is_resource($this->socket)){
			echo "User #" . $this->id . " lost connection!";
			return false;
		}

		$remaining = 1;
		$contents = '';

		while($remaining > 0) {
			if (feof($this->socket)) {
				$this->close();
				return $contents;
			}
			$read = fread($this->socket, $remaining);

			if($read === false){
				$this->close();
				return $contents;
			}

			$contents .= $read;
			$remaining -= strlen($read);

			if (feof($this->socket)) {
				$this->close();
				return $contents;
			}

			$metadata = stream_get_meta_data($this->socket);
			if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
				$remaining = $metadata['unread_bytes'];
			}
		}
		return $contents;
	}

	public function close(){
		$this->closed = true;
		if(is_resource($this->socket)) {
			fclose($this->socket);
		}
	}

	public function isAuthenticated(){
		return $this->handshake;
	}
}
