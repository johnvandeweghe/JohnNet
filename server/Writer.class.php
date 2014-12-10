<?php
namespace WebSocket;

class Writer extends \Worker {

	private $user;
	private $sqs;

	function __construct(User &$user){
		$this->user = $user;
		$this->sqs = \Aws\Sqs\SqsClient::factory(array(
			'key' => AWS_ACCESS_KEY_ID,
			'secret' => AWS_SECRET_ACCESS_KEY,
			'region'  => 'us-east-1'
		));
	}

	public function run(){
		while(!$this->user->closed){
			if($this->user->registered()){
				\ActiveRecord\Config::initialize(function($cfg)
				{
					$cfg->set_model_directory('models');
					$cfg->set_connections(array(
							'development' => MYSQL_CONNECTION_STRING
					));
				});
				$this->sqs->createQueue(array('QueueName' => 'websocket-user-broadcast-' . $this->user->id));
				break;
			}
			sleep(1);
		}

		while(!$this->user->closed) {
			$result = $this->sqs->receiveMessage(array(
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
