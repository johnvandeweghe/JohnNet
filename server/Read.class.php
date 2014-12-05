<?php
namespace Websocket;

class Read extends \Worker {

	private $user = null;
	
	function __construct(User &$user, Websocket $ws){
		$this->user = $user;
		$this->ws = $websocket;
	}

	public function run(){
		while(!$this->user->closed){
			$changed = [$this->user->socket];
			$write = NULL;
			$except = NULL;
			if(socket_select($changed,$write,$except,5) > 0){
				$bytes = @socket_recv($this->user->socket,$buffer,2048,0);
				if($bytes==0){
					$this->disconnect();
				} else {
					if(!$this->user->handshake){
						$handshake = $this->handshake($buffer);
						if($handshake !== true){
							if($handshake == 400){
								$err = "HTTP/1.1 400 Bad Request";
							} elseif($handshake == 404){
								$err = "HTTP/1.1 404 Not Found";
							}
							socket_write($this->user->socket,$err,strlen($err));
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
									$pong = Websocket::frame($data, 0xA);
									socket_write($this->user->socket,$pong,strlen($pong));
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
		}
		socket_close($this->user->socket);
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

		socket_write($this->user->socket,$upgrade,strlen($upgrade));
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
		$close = Websocket::frame($msg, 0x8);
		socket_write($this->user->socket, $close, strlen($close));
		if(!$force){
			//event
		}
	}
	
	private function disconnect($socket){
		if($this->user->handshake && !$this->user->closed){
			$this->close("disconnect");
		}
	}

}