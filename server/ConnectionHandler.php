<?php
namespace JohnNet;

class ConnectionHandler extends \Thread {

	public $connections;

	public $application_secrets;

	private $id;

	function __construct($id, &$connections, $application_secrets){
		$this->id = $id;
		$this->application_secrets = $application_secrets;
		$this->connections = $connections;
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

		$sockets = $this->getAllSockets();

		echo "Found " . count($sockets) . " sockets\n";

		$livingSockets = [];

		foreach($sockets as $socket){
			if(!is_resource($socket)){
				echo "Close 1\n";
				$connection = $this->connections->findBySocket($socket);
				$connection->close();
				$this->connections->remove($connection);
			} else {
				$livingSockets[] = $socket;
			}
		}

		echo "Reduced to " . count($livingSockets) . " open sockets\n";

		$write = NULL;
		$except = NULL;
		if ($sockets && stream_select($livingSockets, $write, $except, 3) > 0) {
			echo "Select found data in " . count($livingSockets) . " sockets\n";
			foreach($livingSockets as $c=>$socket){
				$connection = $this->connections->findBySocket($socket);
				if($connection === false){
					throw new \Exception('Couldnt find socket: ' . $socket);
				}
				$realSocket = $connection->socket;
				$connection->socket = $socket;


				$firstRead = true;
				$remaining = 1;
				$contents = '';

				while ($remaining > 0) {
					if (feof($socket)) {
						echo "Close 2\n";
						$connection->close();
						$this->connections->remove($connection);
						continue 2;
					}
					$read = fread($socket, $remaining);

					if ($read === false) {
						echo "Close 3\n";
						$connection->close();
						$this->connections->remove($connection);
						continue 2;
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
						echo "Close 4\n";
						$connection->close();
						$this->connections->remove($connection);
						continue 2;
					}

					$metadata = stream_get_meta_data($socket);
					if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
						$remaining = $metadata['unread_bytes'];
					}
				}

				$connection->handleRead($this, $contents);
				$connection->socket = $realSocket;
			}
		} else {
			return false;
		}

		return true;
	}

	public function getAllSockets(){
		return $this->connections->getAllSocketsByThread($this->id);
	}

}
