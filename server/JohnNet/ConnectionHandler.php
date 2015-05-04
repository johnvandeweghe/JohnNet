<?php
namespace JohnNet;

class ConnectionHandler extends \Thread {

	public $connections;

	public $application_secrets;

	private $id;

	function __construct($id, Connections &$connections, $application_secrets){
		$this->id = $id;
		$this->application_secrets = $application_secrets;
		$this->connections = $connections;
	}

	public function run(){
		while(true){
			if(!$this->read()){
				sleep(3);
			}
		}
	}


	public function read(){

		$sockets = $this->getAllSockets();

		echo "thread #". $this->id . " found " . count($sockets) . " sockets\n";

		$livingSockets = [];

		foreach($sockets as $socket){
			if(!is_resource($socket)){
				echo "Close 1\n";
				$connection = $this->findByThreadIDAndSocket($socket);
				$connection->close();
				$this->connections->remove($connection);
			} else {
				$livingSockets[] = $socket;
			}
		}

		//echo "Reduced to " . count($livingSockets) . " open sockets\n";

		$write = NULL;
		$except = NULL;
		if ($livingSockets && stream_select($livingSockets, $write, $except, 3) > 0) {
			echo "Select found data in " . count($livingSockets) . " sockets\n";
			foreach($livingSockets as $c=>$socket){
				$connection = $this->findByThreadIDAndSocket($socket);
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

	public function findByThreadIDAndSocket($socket){
		return $this->connections->findByThreadIDAndSocket($this->id, $socket);
	}

	public function publish($applicationID, $channel, $payload, $exclude = false){
		$clients = $this->connections->getAllByAppIDAndChannel($applicationID, $channel);

		foreach($clients as $client){
			if($client !== $exclude) {
				$client->writePayload('payload', [
					'channel' => $channel,
					'payload' => $payload
				]);
			}
		}
	}

}
