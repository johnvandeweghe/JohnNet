<?php
namespace WebSocket;

class User extends \Stackable {
	public $id;
	private $socket;
	public $handshake = false;
	public $closed = false;
	public $channels = [];
	public $cookie;
	public $extensions = [];
	private $application;

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
			echo "User #" . $this->id . " lost connection!\n";
			return false;
		}

		$firstRead = true;
		$remaining = 1;
		$contents = '';

		$changed = [$this->socket];
		$write = NULL;
		$except = NULL;
		if (stream_select($changed, $write, $except, 5) > 0) {
			while ($remaining > 0) {
				if (feof($changed[0])) {
					$this->close();
					return $contents;
				}
				$read = fread($changed[0], $remaining);

				if ($read === false) {
					$this->close();
					return $contents;
				}

				$contents .= $read;

				//SSL bug, only can read 1 byte first read
				if($firstRead && strlen($read) == 1){
					$firstRead = false;
					$remaining = 1400;
				} else {
					$remaining = 0;
				}

				if (feof($changed[0])) {
					$this->close();
					return $contents;
				}

				$metadata = stream_get_meta_data($changed[0]);
				if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
					$remaining = $metadata['unread_bytes'];
				}
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

	public function register(\Application $application){
		$this->channels = [];
		$this->application = $application;
	}

	public function registered(){
		return $this->application;
	}
}
