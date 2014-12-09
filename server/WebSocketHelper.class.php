<?php
namespace WebSocket;

//For broadcasting channel payloads to users, and to stand as an extra service thread host (like websocket handling)
class WebSocketHelper {
	private $sqs;

	public function __construct(){
		$this->sqs = \Aws\Sqs\SqsClient::factory(array(
			'key' => AWS_ACCESS_KEY_ID,
			'secret' => AWS_SECRET_ACCESS_KEY,
			'region'  => 'us-east-1'
		));
	}

	//Not in constructor as infinite loop should be obvious
	public function start(){
		echo "Starting up WebSocket Helper\n";
		while(true){
			$result = $this->sqs->receiveMessage(array(
				'QueueUrl'        => SQS_QUEUE_PREFIX . 'websocket-channel-broadcast',
				'WaitTimeSeconds' => 20,
				'MaxNumberOfMessages' => 10,
			));

			if($messages = $result->get('Messages')){
				foreach ($messages as $message) {
					$payload = array_merge(['handle' => $message['ReceiptHandle']], json_decode($message['Body'], true));
					$subs = \Subscription::find_all_by_channel_id($payload['channel_id']);

					foreach($subs as $sub){
						$this->sqs->sendMessage([
							'QueueUrl'    => SQS_QUEUE_PREFIX . 'websocket-user-broadcast-' . $sub->user_id,
							'MessageBody' => json_encode([
								'type' => 'subscription_payload',
								'payload' => [
									'channel' => $sub->channel->channel_name,
									'payload' => $payload['payload']['payload']
								]
							]),
						]);
					}
				}
			}
		}
	}
}
