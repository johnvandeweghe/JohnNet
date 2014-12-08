<?php
namespace WebSocket;

class Reader extends \Worker {

	public $user = null;
	private $buffer = '';

	function __construct(User &$user){
		$this->user = $user;
	}

	public function run(){
		while(!$this->user->closed){
			$buffer = $this->user->read();
			if($buffer === false) {
				$this->close();
			} elseif($buffer == '') {
				continue;
			} else {
				if(!$this->user->isAuthenticated()){
					$handshake = $this->handshake($buffer);
					if($handshake !== true){
						$err = '';
						if($handshake == 400){
							$err = "HTTP/1.1 400 Bad Request";
						} elseif($handshake == 404){
							$err = "HTTP/1.1 404 Not Found";
						}
						$this->user->write_raw($err);
						$this->close();
					}
				} else {
					$opcode = ord($buffer[0]) & 15;
					$data = WebSocket::unframe($buffer);
					if($opcode >= 0x8 && $opcode <= 0xF){
						switch($opcode){
							case 0x8: //Close
								if($this->user->closed){
									$this->close();
								} else {
									$this->close($data);
								}
								break;
							case 0x9: //Ping
								$this->user->write($data, 0XA);
								break;
							case 0xA: //Pong
								break;
							default:
								$this->close("Unknown control frame received");
								break;
						}
					} else {
						if($opcode === 0x0){
							$this->buffer .= $data;
						} else {
							$payload = json_decode($this->buffer . $data, true);
							$this->buffer = '';

							if($payload && isset($payload['channel']) && isset($payload['payload']) && $opcode === 0x1){
								if($payload['payload'] === 'subscribe'){
									echo "Subscribe request for channel: " . $payload['channel'] . "\n";
									//onSubscribe($user, $channel);
								} else {
									//onPublish($user, $channel, $payload);
								}
							} else {
								//invalid payload
							}
						}
					}
				}
			}
		}
	}

	private function handshake($buffer){
		$temp = explode("\r\n", str_replace("\r\n\r\n", "", $buffer));

		$get = str_replace(array("GET "," HTTP/1.1"), "", array_shift($temp));

		$headers = array();
		foreach($temp as $header){
			list($key, $value) = explode(": ", $header);
			$headers[$key] = $value;
		}

		if(!isset($headers['Host']) ||
			!(isset($headers['Upgrade']) && strtolower($headers['Upgrade']) == 'websocket') ||
			!(isset($headers['Connection']) && stristr($headers['Connection'], 'upgrade') !== false) ||
			!isset($headers['Sec-WebSocket-Key']) ||
			!(isset($headers['Sec-WebSocket-Version']) && $headers['Sec-WebSocket-Version'] == 13)
		){
			return 400;
		}

		if(isset($headers['Sec-WebSocket-Extensions']) && $headers['Sec-WebSocket-Extensions']){
			$this->user->extensions = explode('; ', $headers['Sec-WebSocket-Extensions']);
		}

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
					"Upgrade: websocket\r\n" .
					"Connection: Upgrade\r\n" .
					"Sec-WebSocket-Accept: " . base64_encode(sha1($headers["Sec-WebSocket-Key"] . WebSocket::GUID, true)) . "\r\n" .
					"\r\n";

		$this->user->write_raw($upgrade);
		$this->user->handshake=true;

		if(isset($headers["Cookie"])){
			$cookies = explode("; ", $headers["Cookie"]);
			if($cookies){
				foreach($cookies as $cookie){
					list($key, $value) = explode("=", $cookie);
					$this->user->cookie[$key] = $value;
				}
			}
		}

		return true;
	}

	//Mark a user closed and send them $msg as the reason
	private function close($msg = '', $force = false){
		if($this->user->isAuthenticated() && !$this->user->closed) {
			$this->user->write($msg, 0x8);
		}
		$this->user->close();
		if(!$force){
			//event
		}
	}

}
