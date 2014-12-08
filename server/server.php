<?php
if(PHP_SAPI == "cli"){
	require_once('vendor/autoload.php');
	require_once("WebSocket.class.php");
	require_once("User.class.php");
	require_once("Reader.class.php");
	require_once("Writer.class.php");

	error_reporting(E_ALL);
	set_time_limit(0);

	ActiveRecord\Config::initialize(function($cfg)
	{
		$cfg->set_model_directory('models');
		$cfg->set_connections(array(
			'development' => 'mysql://root:rootpassword@localhost/pubsubhost'
		));
	});

	$master = new WebSocket\WebSocket("localhost", 8080);
	$master->listen();
}
