<?php
namespace Websocket;

class Reader extends \Worker {

	private $user = null;

	function __construct(User &$user, Websocket $ws){
		$this->user = $user;
		$this->ws = $ws;
	}

	public function run(){
		while(!$this->user->closed){
			$buffer = null;
			$bytes = $this->user->read($buffer);
			if($bytes==0){
				$this->disconnect();
			} elseif($bytes === 'Nothing') {
				continue;
			} else {
				if(!$this->user->handshake){
					$handshake = $this->handshake($buffer);
					if($handshake !== true){
						$err = '';
						if($handshake == 400){
							$err = "HTTP/1.1 400 Bad Request";
						} elseif($handshake == 404){
							$err = "HTTP/1.1 404 Not Found";
						}
						$this->user->write_raw($err);
						$this->disconnect();
					}
				} else {
					$opcode = ord($buffer[0]) & 15;
					$data = Websocket::unframe($buffer);
					if($opcode >= 0x8 && $opcode <= 0xF){
						switch($opcode){
							case 0x8: //Close
								if($this->user->closed){
									$this->disconnect();
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
						$payload = json_decode($data, true);
						if($payload && isset($payload['channel']) && isset($payload['payload']) && $opcode === 0x1){
							if($payload === 'subscribe'){
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
		$this->user->close();
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

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
					"Upgrade: websocket\r\n" .
					"Connection: Upgrade\r\n" .
					"Sec-WebSocket-Accept: " . base64_encode(sha1($headers["Sec-WebSocket-Key"] . Websocket::GUID, true)) . "\r\n" .
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
	private function close($msg, $force = false){
		$this->user->closed = true;
		$this->user->write($msg, 0x8);
		if(!$force){
			//event
		}
	}

	private function disconnect(){
		if($this->user->handshake && !$this->user->closed){
			$this->close("disconnect");
		}
	}

}
