<?php
namespace JohnNet;

class ConnectionHandler extends \Thread {

	public $connections;

	public $application_secrets;

	private $id;

	function __construct($id, $application_secrets){
		$this->id = $id;
		$this->application_secrets = $application_secrets;
		$this->connections = [];
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
				$connection = $this->findBySocket($socket);
				$connection->close();
				$this->remove($connection);
				echo "Close 1\n";
			} else {
				$livingSockets[] = &$socket;
			}
		}

		echo "Reduced to " . count($livingSockets) . " open sockets\n";

		$write = NULL;
		$except = NULL;
		if ($sockets && stream_select($livingSockets, $write, $except, 5) > 0) {
			echo "Select found data in " . count($livingSockets) . " sockets\n";
			foreach($livingSockets as $c=>$socket){
				$connection = $this->connections[$c];

				$firstRead = true;
				$remaining = 1;
				$contents = '';

				while ($remaining > 0) {
					if (feof($socket)) {
						$connection->close();
						echo "Close 2\n";
						$connection->handleRead($this, $contents);
						$this->remove($connection);
					}
					$read = fread($socket, $remaining);

					if ($read === false) {
						$connection->close();
						echo "Close 3\n";
						$connection->handleRead($this, $contents);
						$this->remove($connection);
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
						echo "Close 4\n";
						$connection->handleRead($this, $contents);
						$this->remove($connection);
					}

					$metadata = stream_get_meta_data($socket);
					if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
						$remaining = $metadata['unread_bytes'];
					}
				}

				$connection->handleRead($this, $contents);
			}
		} else {
			return false;
		}

		return true;
	}


//	public function findBySocket(&$socket){
//		foreach($this->connections as &$connection){
//			if($connection->socket == $socket){
//				return $connection;
//			}
//		}
//
//		return false;
//	}


	public function remove($conn){
		$connections = [];
		foreach($this->connections as &$connection){
			if($connection != $conn){
				$connections[] = &$connection;
			}
		}
		$this->connections = $connections;
	}

	public function getAllSockets(){
		return $this->connections ? array_map(function(&$c){return $c->socket;}, $this->connections) : [];
	}

	public function add(&$connection){
		$connections = $this->connections;
		$connections[] = &$connection;
		$this->connections = $connections;
	}

}
