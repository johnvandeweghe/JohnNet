<?php
namespace JohnNet;

class ConnectionHandler extends \Worker {

	public $user;
	private $buffer = '';
	private $sqs;

	function __construct(User &$user){
		$this->user = $user;
	}

	public function run(){

		$db = false;

		while(!$this->user->closed){
			$buffer = $this->user->read();
			if($buffer === false) {
				$this->close();
			} elseif($buffer == '') {
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
								$this->user->write($data, 0xA);
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

							if($payload && isset($payload['type']) && isset($payload['payload']) && $opcode === 0x1){
								switch($payload['type']) {
									case 'register':
										//Register user to application
										if (!isset($payload['payload']['app_id']) || !isset($payload['payload']['app_secret'])) {
											$this->user->write(json_encode([
													'type' => 'register',
													'payload' => [
															'status' => 'failed',
															'message' => 'Missing app id or app secret'
													],
											]));
											break;
										}


										if($this->user->register($payload['payload']['app_id'], $payload['payload']['app_secret'], $db)) {
											$this->user->write(json_encode([
												'type' => 'register',
												'payload' => [
													'status' => 'success',
													'message' => 'Registered'
												],
											]));
											break;
										}

										$this->user->write(json_encode([
											'type' => 'register',
											'payload' => [
												'status' => 'failed',
												'message' => 'Incorrect credentials (credential failure logged and reported)'
											],
										]));
										break;
									case 'subscribe':
										if($application = $this->user->registered()){
											if($this->user->subscribe($payload['payload']['channel'])) {
												$this->user->write(json_encode([
													'type' => 'subscribe',
													'payload' => [
														'status' => 'success',
														'message' => 'Subscribed to channel'
													],
												]));
											} else {
												$this->user->write(json_encode([
													'type' => 'subscribe',
													'payload' => [
														'status' => 'failed',
														'message' => 'Access to channel denied'
													],
												]));
											}
										} else {
											$this->user->write(json_encode([
												'type' => 'subscribe',
												'payload' => [
													'status' => 'failed',
													'message' => 'Not yet registered'
												],
											]));
										}
										break;
									case 'publish':
										if($application = $this->user->registered()){
											if($sub = $this->user->isSubscribed($payload['payload']['channel'])){
												$this->sqs->sendMessage([
													'QueueUrl'    => SQS_QUEUE_PREFIX . 'channel-broadcast',
													'MessageBody' => json_encode([
														'channel' => $sub->channel->channel_id,
														'payload' => $payload['payload']['payload']
													]),
												]);
												$this->user->write(json_encode([
													'type' => 'publish',
													'payload' => [
														'status' => 'success',
														'message' => 'Payload published to channel'
													],
												]));
											} else {
												$this->user->write(json_encode([
													'type' => 'publish',
													'payload' => [
														'status' => 'failed',
														'message' => 'Not subscribed to channel'
													],
												]));
											}
										} else {
											$this->user->write(json_encode([
												'type' => 'publish',
												'payload' => [
														'status' => 'failed',
														'message' => 'Not yet registered'
												],
											]));
										}
										break;
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
		//If the conditions are right to send a message (handshake completed, not closed) send a close message
		if($this->user->handshake && !$this->user->closed) {
			$this->user->write($msg, 0x8);
		}
		$this->user->close();
		if(!$force){
			//event
		}
	}

}
