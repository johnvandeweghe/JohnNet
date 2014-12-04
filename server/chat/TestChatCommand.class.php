<?php
class TestChatCommand implements ChatCommand{

	public function command(WebSocketChat $wsc, $message, WebSocketUser $user){
		return;
	}
	
	public function getDescription(){
		return "/test	:	debug code";
	}
}