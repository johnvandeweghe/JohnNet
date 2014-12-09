<?php
namespace WebSocket;

class Writer extends \Worker {

	private $user = null;

	function __construct(User &$user){
		$this->user = $user;
		$this->sqs = \Aws\Sqs\SqsClient::factory(array(
			'region'  => 'us-east-1'
		));
	}

	public function run(){
		while(!$this->user->closed) {
			$result = $this->client->receiveMessage(array(
				'QueueUrl' => SQS_QUEUE_PREFIX . 'websocket-user-broadcast-' . $this->user->arUser->id,
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
