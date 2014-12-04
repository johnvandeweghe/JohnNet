<?php
class HelpChatCommand implements ChatCommand{

	public function command(WebSocketChat $wsc, $message, WebSocketUser $user){
		$help = "";
		foreach($wsc->commands as $command => $handler)
			$help .= $handler->getDescription() . "\n";
		$wsc->ws->send($user, json_encode(array("type" => "server_message", "message" => nl2br(htmlentities($help)))));
	}
	
	public function getDescription(){
		return "/help	:	Shows a list of commands";
	}
}