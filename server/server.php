<?php
if(PHP_SAPI == "cli"){
	require_once("websockets/WebSocket.class.php");
	require_once("websockets/WebSocketEventHandler.interface.php");
	require_once("websockets/Action.interface.php");
	
	require_once("chat/ChatCommand.interface.php");
	require_once("chat/WebSocketChat.class.php");
	require_once("chat/Pinger.class.php");
	require_once("chat/HelpChatCommand.class.php");
	require_once("chat/TestChatCommand.class.php");
	require_once("chat/MeChatCommand.class.php");
	
	require_once("game/WebSocketGame.class.php");
	require_once("game/Map.class.php");
	require_once("game/Game.class.php");
	require_once("game/Unit.class.php");
	require_once("game/MySQLPing.class.php");
	
	require_once("../../classes/User.class.php");
	
	error_reporting(E_ALL);
	set_time_limit(0);
	
	$master = new WebSocket("dev.lunixlabs.com", 8080);
	$ChatHandler = new WebSocketChat($master);
	$ChatHandler->addCommand("/help", new HelpChatCommand());
	$ChatHandler->addCommand("/test", new TestChatCommand());
	$ChatHandler->addCommand("/me", new MeChatCommand());
	$master->addEventHandler("/chat", $ChatHandler);
	$master->addEventHandler("/game", new WebSocketGame($master));
	$master->addAction(new MySQLPing($master, "/game"), 3600);
	$master->listen();
}
?>