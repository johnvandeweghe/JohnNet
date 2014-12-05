<?php
namespace Websocket;

interface EventHandler {

	public function __construct(WebSocket $ws);
	public function onPublish(WebSocketUser $user, $channel, $payload);
	public function onSubscribe(WebSocketUser $user, $channel);
	public function onConnect(WebSocketUser $user);
	public function onClose(WebSocketUser $user);

}