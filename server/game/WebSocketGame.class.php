<?php
class WebSocketGame implements WebSocketEventHandler {

	public $ws;
	
	public $con;
	public $memcache;
	
	public $games = array();
	
	public static $version = 0.0;
	
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
	
	public function onMessage(WebSocketUser $user, $msg, $opcode=0x1){
		$msg = json_decode($msg);
		if($msg === null || !isset($msg->type))
			return;
			
		switch($msg->type){
			case "start_campaign":
				if($user->campaignLevel == $msg->level && $user->status == ""){
					$user->status = "unit_select_campaign_" . $user->campaignLevel;
					$data = $this->con->query("select `map_data`,`max_player_units` from `websocket_game_campaign_maps` where `name` = 'level_1'")->fetch();
					$level = new Map(json_decode($data["map_data"], true));
					$this->games[$user->id] = new Game($level);
					//$units = array();
					//$units[$user->id] = $user->units;
					$this->ws->send($user, json_encode(array("type" => "start_campaign", "level" => $user->campaignLevel, 'max_player_units' => (int)$data['max_player_units'])));
				}
				break;
			case "units_selected":
				if($user->campaignLevel == $msg->level && $user->status == ("unit_select_campaign_" . $user->campaignLevel)){
					$user->status = "campaign_" . $user->campaignLevel;
				}
				break;
			default:
				$this->ws->close($user, "Unknown message type received");
				break;
		}
	}
	public function onConnect(WebSocketUser $user){
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
							$ps = $this->con->prepare("select `campaignLevel` from `websocket_game_savedata` where `id` = :id");
							$ps->execute(array(':id' => (int)$user->session["account"]["id"]));
							$data = $ps->fetch();
							if(!$data){
								$ps = $this->con->prepare("INSERT INTO `websocket_game_savedata` (`id`) VALUES (:id)");
								$ps->execute(array(':id' => (int)$user->session["account"]["id"]));
								$user->campaignLevel = 1;
							} else {
								$user->campaignLevel = $data["campaignLevel"];
							}
							$user->status = "";
							$ps = $this->con->prepare("select `id`,`user`,`name`,`type` from `websocket_game_units` where `user` = :id");
							$ps->execute(array(':id' => (int)$user->session["account"]["id"]));
							
							$this->ws->send($user, json_encode(array("type" => "login", "you" => $user->id, "campaignLevel" => $user->campaignLevel, 'units' => $ps->fetchAll())));
						}
					} catch(Exception $e){
						echo "Unknown user " . $user->session["account"]["id"] . " found in session (site crosstalk?)" . $e . "\n";
					}
				 } else {
					$this->ws->close($user, "You need to login to the site first");
					return;
				 }
			}
		}
		
	}
	public function onClose(WebSocketUser $user){
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