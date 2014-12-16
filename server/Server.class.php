<?php
namespace JohnNet;

//Attempts to implement RFC6455 http://datatracker.ietf.org/doc/rfc6455/?include_text=1
class Server {
	public $listeners = [];

	private $users = [];

	private $connectionHandlers = [];

	const GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

	private $address, $port;

	private $application_secrets;

	function __construct($address, $port=443, $application_secrets=false){
		$this->address = $address;
		$this->port = $port;
		$this->application_secrets = $application_secrets;
	}

	public function live($clientThreads = 4, $nodeAddress = ''){
		ob_implicit_flush();

		for($i = 0; $i < $clientThreads; $i++){
			$handler = new ConnectionHandler($this->application_secrets);
			$handler->start();
			$this->connectionHandlers[] = $handler;
		}

		$ctx = stream_context_create(
			array('ssl' =>
				array(
					"local_cert" => "server.pem",
					"allow_self_signed" => true,
					"verify_peer" => false,
					"passphrase" => "password",
				)
			),
			array()
		);

		if($nodeAddress) {
			$this->makeFriends($nodeAddress);
		}

		$this->listeners[] = stream_socket_server('tls://' . $this->address . ':' . $this->port, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $ctx);
		if(!$this->$this->listeners[0] || !$ctx || $errno || $errstr){
			exit('I tried to move in, but I think someone else was already living there? (Check the port)');
		}

		echo "I moved into my new address at: wss://" . $this->address . ":" . $this->port . "\n";


		while(true) {
			try {
				$changed = $this->listeners;
				$write = NULL;
				$except = NULL;
				if (stream_select($changed, $write, $except, 5) > 0) {
					$client = stream_socket_accept($changed[0], 0);
					//add to existing pools created above
					if ($client < 0) {
						continue;//socket accept failure
					} else {
						$user = new User($client);

						$reader = new ConnectionHandler($user);
						$writer = new Writer($user);

						$reader->start();
						$writer->start();

						$this->readers[] = &$reader;
						$this->writers[] = &$writer;
						$this->users[] = &$user;
					}
				}
			} catch(\Exception $e){
				var_dump($e);
			}
		}
	}

	public static function unframe($payload) {
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

	public static function frame($text, $opcode = 0x1)
	{
		// 0x1 text frame (FIN + opcode)
		$b1 = 0x80 | ($opcode & 0x0f);

		if ($opcode == 0x8)
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

	public function makeFriends($nodeAddress){
		$node = stream_socket_client('tls://' . $nodeAddress, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
		if(!$node || !$ctx || $errno || $errstr){
			exit('Are you sure this address is someone who wants to make friends? ' . $nodeAddress);
		}

		$node = new ServerConnection($node);

		if(!$node->writeRaw($this->key)){
			exit('I couldn\'t get a hold of the person you wanted to introduce me to, something about fwrite failing...?');
		}

		if(!($response = $node->readOnce())){
			exit('I couldn\'t get a hold of the person you wanted to introduce me to, something about fread failing...?');
		}

		//Process results (should be list of servers to connect to)
		if(!($servers = json_decode($response, true))){
			exit('I couldn\'t make friends today, are you sure you introduced me correctly?');
		}

		$this->connectionHandlers[0]->connections[] = $node;

		foreach($servers as $server){
			$node = stream_socket_client('tls://' . $server, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
			if(!$node || !$ctx || $errno || $errstr){
				exit('I met the friend you introduced me to, but their other friend wasn\'t interested in me. :( ' . $nodeAddress);
			}

			$node = new ServerConnection($node);

			if(!$node->writeRaw($this->key)){
				exit('I couldn\'t get a hold of the person you wanted to introduce me to, something about fwrite failing...?');
			}

			if(!($response = $node->readOnce())){
				exit('I couldn\'t get a hold of the person you wanted to introduce me to, something about fread failing...?');
			}

			$this->connectionHandlers[0]->connections[] = $node;
		}

		echo "Successfully made " . count($this->connectionHandlers[0]->connections) . " friends\n";
	}
}
