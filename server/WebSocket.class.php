<?php
namespace Websocket;

//Attempts to implement RFC6455 http://datatracker.ietf.org/doc/rfc6455/?include_text=1
class WebSocket{
	private $master;

	private $users = [];

	private $readers = [];

	private $writers = [];

	const GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

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
		echo "Listening on: " . $this->address . ":" . $this->port . "\n";

		while(true){
			$changed = [$this->master];
			$write = NULL;
			$except = NULL;
			if(socket_select($changed,$write,$except,5) > 0){
				$client = socket_accept($this->master);
				if($client < 0){
					continue;//socket accept failure
				} else {
					$user = new User($client);
					$this->users[] = $user;
					end($this->users);
					$user->id = key($this->users);

					$reader = new Reader($user, $this);
					$writer = new Writer($user, $this);

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
