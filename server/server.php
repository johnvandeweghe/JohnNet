<?php
if(PHP_SAPI == "cli"){
	require_once("WebSocket.class.php");
	require_once("User.class.php");
	require_once("Read.class.php");
	require_once("Write.class.php");

	error_reporting(E_ALL);
	set_time_limit(0);
	
	$master = new Websocket\WebSocket("localhost", 8080);
	$master->listen();
}