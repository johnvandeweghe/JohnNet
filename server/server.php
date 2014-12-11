<?php
if(PHP_SAPI == "cli"){
	require_once('vendor/autoload.php');
	require_once("WebSocket.class.php");
	require_once("User.class.php");
	require_once("Reader.class.php");
	require_once("Writer.class.php");
	require_once("config.php");
	require_once("johnmodels/Model.php");
	require_once("johnmodels/Server.php");
	require_once("johnmodels/User.php");
	require_once("johnmodels/Application.php");
	require_once("johnmodels/Channel.php");
	require_once("johnmodels/Subscription.php");

	error_reporting(E_ALL);
	set_time_limit(0);

	$master = new WebSocket\WebSocket("localhost", 8080);
	$master->live($argv[1]);
}

/*
 * TODO
 * JohnNet P2P setup
 * 	connect to node
 *  get list of servers from node
 *  connect to each server
 *  listen for incoming connections (MAIN THREAD)
 *  Split server listen/write threads from client listen/write threads? NO, need to only listen once, and won't know which type the connection is until after it's in a thread
	 *  read existing connections (CHUNKED READ THREADS, adding new messages to write thread)
	 *   channel broadcasts from servers (add to write threads for each user in channel)
	 *   channel broadcasts from clients (add to write threads for each server)
	 *   channel subscribes (mark user as in channel, create channel if necisary
 	 *   server registers (first hop (intial) and second hop)
	 * 	 client registers
	 *  write queue of messages to connections (single message queue thread)
 * Connection class (with server/client dervitives)
 * Chunking support
 *
 *
 * JohnLongpolling support (old clients)
 * HTTP RESTFUL JOHNAPI (server clients)
 * Johnwebhooks
 *
 *
 * Low priority (could do after Kipsu integration):
 * Front end UI to create applications/register Johnwebhooks?
 * compression WS extension support
 */
