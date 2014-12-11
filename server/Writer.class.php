<?php
namespace WebSocket;

class Writer extends \Worker {

	private $user;

	function __construct(User &$user){
		$this->user = $user;

	}

	public function run(){
		while(!$this->user->closed){
			if($this->user->registered()){
				$this->user->sqs->createQueue(array('QueueName' => 'websocket-user-broadcast-' . $this->user->id));
				break;
			}
			sleep(1);
		}

		while(!$this->user->closed) {
			$result = $sqs->receiveMessage(array(
				'QueueUrl' => SQS_QUEUE_PREFIX . 'websocket-user-broadcast-' . $this->user->id,
				'WaitTimeSeconds' => 20,
				'MaxNumberOfMessages' => 10,
			));

			if ($messages = $result->get('Messages')) {
				foreach ($messages as $message) {
					$payload = json_decode($message['Body'], true);

					//Verification?

					$this->user->write(json_encode($payload));
				}
			}
		}
	}
}
