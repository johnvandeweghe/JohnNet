<?php
if(PHP_SAPI == "cli"){
	require_once("Server.class.php");
	require_once("User.class.php");
	require_once("ConnectionHandler.php");
	require_once("Connection.php");
	require_once("Connections.php");
	require_once("ClientConnection.php");
	require_once("ServerConnection.php");
	require_once("config.php");

	error_reporting(E_ALL);
	set_time_limit(0);

	$master = new JohnNet\Server("localhost", 8080, $application_secrets);
	$master->live(1, isset($argv[1]) ? $argv[1] : '');
}

/*
 * TODO
 * Server listen port
 * ServerConnection Read handler (read publishes. read channel joins, maintain P2P channel list, for webhook existence/vacate events (only "presence-" ?)
 * ClientConnection Read handler (registration (needs DB...), subscribes, publishes)
 * Handle publishes to server's users
 * Front end (channel bind handler, channel trigger handler, subscribe handler, reconnection handler, route to new server handler, logging, authentication, etc)
 * Server side Longpolling support... ?
 * HTTP RESTFUL API (server clients)
 * Add a server purely for webshook handling
 *
 *
 * Low priority (could do after Kipsu integration):
 * Front end UI to create applications/register webhooks?
 * compression WS extension support
 */
