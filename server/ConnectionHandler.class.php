<?php
namespace JohnNet;

class ConnectionHandler extends \Worker {

	private $buffer = '';
	protected $connections;

	protected $application_secrets;

	function __construct($application_secrets){
		$this->connections = new Connections();
		$this->application_secrets = $application_secrets;
	}

	public function run(){

		while(true){
			$buffer = $this->user->read();
			if($buffer === false) {
				$this->close();
			} elseif($buffer == '') {
				continue;
			} else {
				$this->read();
			}
		}
	}


	public function read(){

		$sockets = $this->connections->getAllSockets();

		$livingSockets = [];

		foreach($sockets as &$socket){
			if(!is_resource($socket)){
				$this->connections->findBySocket($socket)->close();
			} else {
				$livingSockets[] = $socket;
			}
		}

		$sockets = $livingSockets;

		$write = NULL;
		$except = NULL;
		if (stream_select($sockets, $write, $except, 5) > 0) {
			foreach($sockets as &$socket){
				$connection = $this->connections->findBySocket($socket);

				$firstRead = true;
				$remaining = 1;
				$contents = '';

				while ($remaining > 0) {
					if (feof($socket)) {
						$connection->close();
						$connection->handleRead($contents);
					}
					$read = fread($socket, $remaining);

					if ($read === false) {
						$connection->close();
						$connection->handleRead($contents);
					}

					$contents .= $read;

					//SSL bug, only can read 1 byte first read
					if($firstRead && strlen($read) == 1){
						$firstRead = false;
						$remaining = 1400;
					} else {
						$remaining = 0;
					}

					if (feof($socket)) {
						$connection->close();
						$connection->handleRead($contents);
					}

					$metadata = stream_get_meta_data($socket);
					if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
						$remaining = $metadata['unread_bytes'];
					}
				}
				$connection->handleRead($contents);
			}
		}
	}



}
