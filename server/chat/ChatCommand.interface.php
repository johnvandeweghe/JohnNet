<?php
interface ChatCommand {

	public function command(WebSocketChat $wsc, $message, WebSocketUser $user);

	public function getDescription();
}