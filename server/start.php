<?php
if(PHP_SAPI == "cli"){
	require_once("JohnNet/Server.php");
	require_once("JohnNet/ConnectionHandler.php");
	require_once("JohnNet/Connection/Connection.php");
	require_once("JohnNet/Connection/ClientConnection.php");
	require_once("JohnNet/Connection/ServerConnection.php");
	require_once("JohnNet/Connections.php");
	require_once("JohnNet/ConnectionPermanence.php");
	require_once("config.php");

	error_reporting(E_ALL);
	set_time_limit(0);

	$master = new \JohnNet\Server("localhost", 8080, $application_secrets);
	$master->live(3, isset($argv[1]) ? $argv[1] : '');
}

/*
 * TODO
 * Server listen port
 * ServerConnection Read handler (read publishes. read channel joins, maintain P2P channel list, for webhook existence/vacate events (only "presence-" ?)
 * Handle publishes to server's users
 * Front end (route to new server handler, authentication, etc)
 * Server side Longpolling support... ?
 * HTTP RESTFUL API (server clients)
 * Add a server purely for webshook handling
 *
 *
 * Low priority (could do after potential Kipsu integration):
 * Front end UI to create applications/register webhooks?
 * compression WS extension support
 */
