<?php
namespace JohnNet;

class User extends \Stackable {
	private $socket;
	public $handshake = false;
	public $closed = false;
	public $subscriptions = [];
	public $cookie;
	public $extensions = [];
	private $application;

	public $sqs;

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



	public function close($db = false){
		$this->closed = true;
		if(is_resource($this->socket)) {
			fclose($this->socket);
		}
		if($this->id && $db){
			$u = new \WebSocket\Models\User($db, $this->id);
			$u->delete();
		}
	}

	public function register($app_id, $app_secret, &$db){
		try {
			$application = new \WebSocket\Models\Application($db, $app_id);
			var_dump($application);
		} catch(\Exception $e){
			var_dump($e);
			return false;
		}
		if($application->secret === $app_secret) {
			foreach ($this->subscriptions as $sub_id) {
				try {
					$sub = new \WebSocket\Models\Subscription($db, $sub_id);
					$sub->delete();
				} catch (Exception $e) {
					//Subscription removed previously (channel lost?)
				}
			}
			$this->subscriptions = [];
			$this->application = $application->id;
			return true;
		} else {
			return false;
		}
	}

	public function registered(){
		return $this->application;
	}

	public function subscribe($channel){
		$ch = null;
		if(!($ch = \Channel::find_by_application_id_and_name($this->application->id, $channel))){
			$ch = new \Channel([
				'application_id' => $this->application->id,
				'name' => $channel
			]);
			$ch->save();
		}

		$sub = null;
		if(!($sub = \Subscription::find_by_user_id_and_channel_id($this->id, $ch->id))){
			$sub = new \Subscription([
				'user_id' => $this->id,
				'channel_id' => $ch->id
			]);
			$sub->save();
		}

		$this->subscriptions[] = $sub->id;

		//False to deny access
		return true;
	}

	public function isSubscribed($channel){
		foreach($this->subscriptions as $sub_id){
			$sub = \Subscription::find($sub_id);
			if($sub && $sub->channel->name === $channel){
				return $sub;
			}
		}

		return false;
	}
}
