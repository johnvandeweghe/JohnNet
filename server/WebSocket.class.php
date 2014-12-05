<?php
namespace Websocket;

//Attempts to implement RFC6455 http://datatracker.ietf.org/doc/rfc6455/?include_text=1
class WebSocket{
	private $master;
	private $sockets = array();
	private $channels = array();
	private $users   = array();

	private $GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

	private $address, $port;

	function __construct($address,$port){
		$this->address = $address;
		$this->port = $port;
	}

	public function listen(){
		ob_implicit_flush();

		$this->master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
		socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
		socket_bind($this->master, $this->address, $this->port)                    or die("socket_bind() failed");
		socket_listen($this->master,20)                                or die("socket_listen() failed");
		$this->sockets[] = $this->master;
		echo "Listening on: " . $this->address . ":" . $this->port . "\n";

		while(true){
			$changed = $this->sockets;
			$write = NULL;
			$except = NULL;
			if(socket_select($changed,$write,$except,5) > 0){
				foreach($changed as $socket){
					if($socket == $this->master){
						$client = socket_accept($this->master);
						if($client < 0){
							continue;//socket accept failure
						} else {
							$this->connect($client);
						}
					} else {
						$bytes = @socket_recv($socket,$buffer,2048,0);
						$user = $this->getUserBySocket($socket);
						if($bytes==0){
							$this->disconnect($socket);
							if(isset($user) && !$user->closed && $user->handshake)
								$this->onClose($user);
						} else {
							if(!$user->handshake){
								$handshake = $this->handshake($user,$buffer);
								if($handshake !== true){
									if($handshake == 400){
										$err = "HTTP/1.1 400 Bad Request";
									} elseif($handshake == 404){
										$err = "HTTP/1.1 404 Not Found";
									}
									socket_write($user->socket,$err,strlen($err));
									$this->disconnect($socket);
								}
							} else {
								$opcode = ord($buffer[0]) & 15;
								$data = $this->unframe($buffer);
								if($opcode >= 0x8 && $opcode <= 0xF){
									switch($opcode){
										case 0x8: //Close
											if($user->closed){
												$this->disconnect($user->socket);
											} else {
												$this->close($user, $data);
											}
											break;
										case 0x9: //Ping
											$pong = $this->frame($data, 0xA);
											socket_write($user->socket,$pong,strlen($pong));
											break;
										case 0xA: //Pong
											break;
										default:
											$this->close($user, "Unknown control frame received");
											break;
									}
								} else {
									$this->onMessage($user, $data, $opcode);
								}
							}
						}
					}
				}
			}
		}
	}
	
	//Add an event handler to run for users that connect on the path of $route
	public function addEventHandler(WebSocketEventHandler $handler){
		$this->handlers[] = $handler;
	}

	private function onMessage($user, $msg, $opcode=0x1){
		$this->routes[$user->route]->onMessage($user, $msg, $opcode);
	}
	
	private function onConnect($user){
		$this->routes[$user->route]->onConnect($user);
	}
	
	private function onClose($user){
		$this->routes[$user->route]->onClose($user);
	}

	//Send a message to a specific user, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
	public function send($user, $msg, $opcode=0x1){
		if(!$user->handshake)
			return;
		$msg = $this->frame($msg, $opcode);
		socket_write($user->socket, $msg, strlen($msg));
	}
	
	public function sendToRouteExcluding($route, $user, $msg, $opcode=0x1){
		foreach($this->users as $u)
			if($u->route == $route && $user->id != $u->id)
				$this->send($u, $msg, $opcode);
	}

	private function connect($socket){
		$user = new User();
		$user->socket = $socket;
		$this->users[] = $user;
		end($this->users);
		$user->id = key($this->users);
		$this->sockets[] = $socket;
	}
	
	//Mark a user closed and send them $msg as the reason
	public function close($user, $msg, $force = false){
		$user->closed = true;
		$close = $this->frame($msg, 0x8);
		socket_write($user->socket,$close,strlen($close));
		if(!$force)
			$this->onClose($user);
	}
	
	private function disconnect($socket){
		foreach($this->users as $i => $user){
			if($user->socket==$socket){
				if($this->users[$i]->handshake && !$this->users[$i]->closed){
					$this->close($this->users[$i], "disconnect");
					return;
				}
				unset($this->users[$i]);
			}
		}
		foreach($this->sockets as $i => $sock){
			if($socket == $sock){
				socket_close($socket);
				unset($this->sockets[$i]);
			}
		}
	}

	private function handshake($user,$buffer){		
		$temp = explode("\r\n", str_replace("\r\n\r\n", "", $buffer));

		$get = str_replace(array("GET "," HTTP/1.1"), "", array_shift($temp));

		$headers = array();
		foreach($temp as $header){
			list($key, $value) = explode(": ", $header);
			$headers[$key] = $value;
		}
		
		if(!isset($headers['Host']) || !(isset($headers['Upgrade']) && strtolower($headers['Upgrade']) == 'websocket') || !(isset($headers['Connection']) && stristr($headers['Connection'], 'upgrade') !== false) || !isset($headers['Sec-WebSocket-Key']) || !(isset($headers['Sec-WebSocket-Version']) && $headers['Sec-WebSocket-Version'] == 13))
			return 400;
		
		$upgrade  = "HTTP/1.1 101 Switching Protocols\r\n" .
					"Upgrade: websocket\r\n" .
					"Connection: Upgrade\r\n" .
					"Sec-WebSocket-Accept: " . base64_encode(sha1($headers["Sec-WebSocket-Key"].$this->GUID, true)) . "\r\n" .
					"\r\n";
		socket_write($user->socket,$upgrade,strlen($upgrade));
		$user->handshake=true;
		$user->route = $get;
		if(isset($headers["Cookie"])){
			$cookies = explode("; ", $headers["Cookie"]);
			foreach($cookies as $cookie){
				list($key, $value) = explode("=", $cookie);
				$user->cookie[$key] = $value;
			}
		}
		
		$this->onConnect($user);
		return true;
	}

	private function getUserBySocket($socket){
		$found=null;
		foreach($this->users as $user){
			if($user->socket==$socket){
				$found = $user;
				break;
			}
		}
		return $found;
	}

	public function getUserByID($id){
		$found=false;
		foreach($this->users as $user){
			if($user->id==$id && !$user->closed){
				$found = $user;
				break;
			}
		}
		return $found;
	}
	
	public function getUsersByRoute($route){
		$found=array();
		foreach($this->users as $user){
			if($user->route==$route && !$user->closed){
				$found[] = $user;
			}
		}
		return $found;
	}

	private function unframe($payload) {
		$length = ord($payload[1]) & 127;
		
		if ($length == 126) {
			$masks = substr($payload, 4, 4);
			$data = substr($payload, 8);
		} elseif ($length == 127) {
			$masks = substr($payload, 10, 4);
			$data = substr($payload, 14);
		} else {
			$masks = substr($payload, 2, 4);
			$data = substr($payload, 6);
		}

		$text = '';
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i % 4];
		}

		return $text;
	}

	private function frame($text, $opcode = 0x1) {
		// 0x1 text frame (FIN + opcode)
		$b1 = 0x80 | ($opcode & 0x0f);
		
		if($opcode == 0x8)
			$text = pack('n', 1000) . $text;
			
		$length = strlen($text);

		if ($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif ($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		else 
			$header = pack('CCN', $b1, 127, $length);
		return $header . $text;
	} 
}