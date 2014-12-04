<?php
class MeChatCommand implements ChatCommand{

	public function command(WebSocketChat $wsc, $message, WebSocketUser $user){
		$message = explode(" ", $message);
		array_shift($message);
		$message = implode(" ", $message);
		$wsc->ws->sendToRoute($user->route, json_encode(array("type" => "me", "user" => $user->id, "message" => $message)));
	}
	
	public function getDescription(){
		return "/me <message>	:	Send a message in the first person";
	}
}