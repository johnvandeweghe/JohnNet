<?php
namespace JohnNet;

/**
 * Class ConnectionHandler
 * @package JohnNet
 */
class ConnectionHandler extends \Thread {

	/**
	 * @var Connections
     */
	public $connections;

	/**
	 * @var
     */
	public $application_secrets;

	/**
	 * @var
     */
	private $id;

	/**
	 * @param $id
	 * @param Connections $connections
	 * @param $application_secrets
     */
	function __construct($id, Connections &$connections, $application_secrets){
		$this->id = $id;
		$this->application_secrets = $application_secrets;
		$this->connections = $connections;
	}

	/**
	 * @throws \Exception
     */
	public function run(){
		while(true){
			if(!$this->read()){
				sleep(1);
			}
		}
	}


	/**
	 * @return bool
	 * @throws \Exception
     */
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
			echo "thread #". $this->id . " Select found data in " . count($livingSockets) . " sockets\n";
			foreach($livingSockets as $c=>$socket){
				$connection = $this->findByThreadIDAndSocket($socket);
				if($connection === false){
					throw new \Exception('Couldnt find socket: ' . $socket);
				}
				$realSocket = $connection->socket;
				$connection->socket = $socket;

				try {
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
						if ($firstRead && strlen($read) == 1) {
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
				} catch(\Exception $e){
					var_dump('Exception in main read loop', $e);
				} finally {
					$connection->socket = $realSocket;
				}
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * @return Connection\ClientConnection[]
     */
	public function getAllSockets(){
		return $this->connections->getAllSocketsByThread($this->id);
	}

	/**
	 * @param $socket
	 * @return bool|Connection\ClientConnection
     */
	public function findByThreadIDAndSocket($socket){
		return $this->connections->findByThreadIDAndSocket($this->id, $socket);
	}

	/**
	 * @param $applicationID
	 * @param $channel
	 * @param $payload
	 * @param bool $exclude
     */
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
