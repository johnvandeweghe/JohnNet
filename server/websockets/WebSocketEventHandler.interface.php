<?php
interface WebSocketEventHandler {

	public function __construct(WebSocket $ws);
	public function onMessage(WebSocketUser $user, $msg, $opcode);
	public function onConnect(WebSocketUser $user);
	public function onClose(WebSocketUser $user);

}