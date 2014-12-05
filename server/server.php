<?php
if(PHP_SAPI == "cli"){
	require_once("websockets/WebSocket.class.php");
	require_once("websockets/WebSocketEventHandler.interface.php");
	require_once("websockets/Action.interface.php");
	
	error_reporting(E_ALL);
	set_time_limit(0);
	
	$master = new WebSocket("dev.lunixlabs.com", 8080);
	$master->listen();
}