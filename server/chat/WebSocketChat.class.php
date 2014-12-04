<?php
class WebSocketChat implements WebSocketEventHandler {

	public $ws;
	
	public $con;
	public $memcache;
	
	public $commands;
	
	public function __construct(WebSocket $ws){
		$this->ws = $ws;
		
		if (!defined('INCLUDED')) {
			define('INCLUDED', true);
		}
		require("../../config.php");

		try {
			$this->con = new PDO("mysql:host=$mysql_hostname;dbname=$mysql_database", $mysql_username, $mysql_password, $DB_DEFAULT_OPT);

			$this->memcache = new Memcache;
			if(!$this->memcache->connect('localhost', 11211))
				throw new Exception("Memcache connection failure");
		} catch(Exception $e){
			var_dump($e);
			//TODO: Actual error handling
		}
	}
	
	public function addCommand($command, ChatCommand $commandHandler){
		$this->commands[$command] = $commandHandler;
	}
	
	public function onMessage(WebSocketUser $user, $msg, $opcode=0x1){
		$msg = json_decode($msg);
		if($msg === null || !isset($msg->type))
			return;
			
		switch($msg->type){
			case "message":
				if($msg->message[0] == "/") {
					$command = explode(" ", $msg->message);
					$command = $command[0];
					if(isset($this->commands[$command]))
						$this->commands[$command]->command($this, $msg->message, $user);
					else
						$this->ws->send($user, json_encode(array("type" => "server_message", "message" => "Command not found")));
				} else {
					$this->ws->sendToRouteExcluding($user->route, $user, json_encode(array("type"=>"message", "user" => htmlentities($user->id), "message" => htmlentities($msg->message))));
				}
				break;
			default:
				$this->ws->close($user, "Unknown message type received");
				break;
		}
	}
	public function onConnect(WebSocketUser $user){
		$user->id = "Guest #" . $user->id;
		
		//Attempt to check if user is logged in on main site, then set their username if they are
		if(isset($user->cookie["PHPSESSID"])){
			$mem = $this->memcache->get($user->cookie["PHPSESSID"]);
			if($mem !== false){
				$user->session = self::unserialize_php($mem);
				if(isset($user->session["account"]) && isset($user->session["account"]["id"])){
					try {
						$id = (new User($this->con, $user->session["account"]["id"]))->username;
						if($this->ws->getUserByID($id)){
							$this->ws->close($user, "Already connected", true);
							return;
						} else {
							$user->id = $id;
						}
					} catch(Exception $e){
						echo "Unknown user found in session (site crosstalk?)\n";
					}
				}
			}
		}
		
		$this->ws->sendToRouteExcluding($user->route, $user, json_encode(array("type" => "join", "user" => $user->id)));
		$users = $this->ws->getUsersByRoute($user->route);
		array_walk($users, function(&$value){ $value = $value->id; });
		$this->ws->send($user, json_encode(array("type" => "users", "users" => $users, "you" => $user->id)));
	}
	public function onClose(WebSocketUser $user){
		$this->ws->sendToRouteExcluding($user->route, $user, json_encode(array("type" => "leave", "user" => $user->id)));
	}
	
	//http://stackoverflow.com/a/9843773/1204508
	private static function unserialize_php($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), "|")) {
				throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
			}
			$pos = strpos($session_data, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
}