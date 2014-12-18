<?php
namespace JohnNet;

class ConnectionHandler extends \Worker {

	protected $connections;

	protected $application_secrets;

	private $id;

	function __construct($id, &$connections, $application_secrets){
		$this->id = $id;
		$this->connections = $connections;
		$this->application_secrets = $application_secrets;
	}

	public function run(){
		while(true){
			echo "thread #". $this->id . " is running!\n";
			if(!$this->read()){
				sleep(3);
			}
		}
	}


	public function read(){

		$sockets = $this->connections->getAllSockets();

		echo "Found " . count($sockets) . " sockets\n";

		$livingSockets = [];

		foreach($sockets as &$socket){
			if(!is_resource($socket)){
				$this->connections->findBySocket($socket)->close();
			} else {
				$livingSockets[] = $socket;
			}
		}

		$sockets = $livingSockets;

		//echo "Reduced to " . count($sockets) . " open sockets\n";

		$write = NULL;
		$except = NULL;
		if ($sockets && stream_select($sockets, $write, $except, 5) > 0) {
			echo "Select found data in " . count($sockets) . " sockets\n";
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
		} else {
			return false;
		}

		return true;
	}



}
