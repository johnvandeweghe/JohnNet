<?php
if(PHP_SAPI == "cli"){
	require_once("WebSocket.class.php");
	require_once("User.class.php");
	require_once("Reader.class.php");
	require_once("Writer.class.php");

	error_reporting(E_ALL);
	set_time_limit(0);

	$master = new WebSocket\WebSocket("localhost", 443);
	$master->listen();
}
