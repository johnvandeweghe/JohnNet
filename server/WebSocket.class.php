<?php
namespace WebSocket;

//Attempts to implement RFC6455 http://datatracker.ietf.org/doc/rfc6455/?include_text=1
class WebSocket{
	public $master;

	private $users = [];

	private $readers = [];

	private $writers = [];

	const GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

	private $address, $port;

	function __construct($address, $port=443){
		$this->address = $address;
		$this->port = $port;
	}

	public function listen(){
		ob_implicit_flush();

		$ctx = stream_context_create(
			array('ssl' =>
				array(
					"local_cert" => "cert.pem",
					"allow_self_signed" => true,
					"verify_peer" => false,
					"passphrase" => "",
				)
			)
		);

		$this->master = stream_socket_server('tls://' . $this->address . ':' . $this->port, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $ctx);
		if(!$this->master || !$ctx || $errno || $errstr){
			exit('Failed to start! (Port already in use?');
		}

		echo "Listening on: wss://" . $this->address . ":" . $this->port . "\n";

		while(true){
			$changed = [$this->master];
			$write = NULL;
			$except = NULL;
			if(stream_select($changed, $write, $except, 5) > 0){
				$client = stream_socket_accept($changed[0]);
				if($client < 0){
					continue;//socket accept failure
				} else {
					$user = new User($client);
					$this->users[] = $user;
					end($this->users);
					$user->id = key($this->users);

					$reader = new Reader($user);
					$writer = new Writer($user);

					$reader->start();
					$writer->start();

					$this->readers[] = $reader;
					$this->writers[] = $writer;
				}
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

	public static function frame($text, $opcode = 0x1) {
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
