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
	 * @var ConnectionPermanence
	 */
	public $permanence;

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
	 * @param ConnectionPermanence $permanence
	 */
	function __construct($id, Connections &$connections, $application_secrets, ConnectionPermanence &$permanence){
		$this->id = $id;
		$this->application_secrets = $application_secrets;
		$this->connections = $connections;
		$this->permanence = $permanence;
	}

	/**
	 * @throws \Exception
     */
	public function run(){
		while(true){
			try {
				if (!$this->read()) {
					usleep(500000);
				}
			} catch (\Exception $e){
				var_dump($e);
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
			if(is_resource($socket)){
				$livingSockets[] = $socket;
			}
		}

		echo "Reduced to " . count($livingSockets) . " open sockets\n";

		echo "CONNECTIONS: " . implode(',', $this->connections->getAllSocketsNamesByThread($this->id)). "\n";

		$actuallyHadData = false;

		$write = NULL;
		$except = NULL;
		if ($livingSockets && stream_select($livingSockets, $write, $except, 0, 500000) > 0) {
			echo "thread #". $this->id . " Select found data in " . count($livingSockets) . " sockets\n";
			foreach($livingSockets as $c=>$socket){
//				echo "Processing #$c\n";
				if(!is_resource($socket)){
					echo "Skipping socket 1\n";
					continue;
				}
				$name = stream_socket_get_name($socket, true);
				$connection = $this->findByThreadIDAndName($name);
				if($connection == false){
					echo "Unable to find $name\n";
					continue;
				}

				$connection->socket = $socket;

				$firstRead = true;
				$remaining = 1;
				$contents = '';

				while ($remaining > 0) {
					if (feof($socket)) {
						echo "Close 2\n";
						$connection->close();
						continue 2;
					}
					$read = fread($socket, $remaining);

					if ($read === false) {
						echo "Close 3\n";
						$connection->close();
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
						echo "Processing of #$c complete with close 4\n";
						$connection->close();
						continue 2;
					}

					$metadata = stream_get_meta_data($socket);
					if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
						$remaining = $metadata['unread_bytes'];
					}
				}

				$connection->handleRead($this, $contents);

				$actuallyHadData = true;
			}
		} else {
			return false;
		}

		return $actuallyHadData;
	}

	/**
	 * @return array
     */
	public function getAllSockets(){
		return $this->connections->getAllSocketsByThread($this->id);
	}

	/**
	 * @param $name
	 * @return bool|Connection\ClientConnection
     */
	public function findByThreadIDAndName($name){
		return $this->connections->findByThreadIDAndName($this->id, $name);
	}

	/**
	 * @param $applicationID
	 * @param $channel
	 * @param $payload
	 * @param bool|Connection\ClientConnection $exclude
     */
	public function publish($applicationID, $channel, $payload, $exclude = false){
		$clients = $this->connections->getAllByAppIDAndChannel($applicationID, $channel);

		$payload = [
			'channel' => $channel,
			'payload' => $payload
		];

		foreach($clients as $client){
			if($client !== $exclude) {
				$client->writePayload('payload', $payload);
			}
		}

		$this->permanence->addPayloadBySubscription($channel, json_encode($payload));
	}

}
