<?php
if(PHP_SAPI == "cli"){
	require_once('vendor/autoload.php');
	require_once("WebSocket.class.php");
	require_once("User.class.php");
	require_once("Reader.class.php");
	require_once("Writer.class.php");
	require_once("config.php");
	require_once("models/Model.php");
	require_once("models/Server.php");
	require_once("models/User.php");

	error_reporting(E_ALL);
	set_time_limit(0);


	$master = new WebSocket\WebSocket("localhost", 8080);
	$master->listen();
}

/*
 * TODO
 * Finish replacing ActiveRecord with JohnModels
 * JohnLongpolling support (old clients)
 * HTTP RESTFUL JOHNAPI (server clients)
 * Johnwebhooks
 *
 *
 * Low priority (could do after Kipsu integration):
 * Front end UI to create applications/register Johnwebhooks?
 * compression WS extension support
 */
