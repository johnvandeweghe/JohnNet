<?php
define('INCLUDED', true);

require_once("../../include.php");


$template_vars["title"] = "LunixLabs' Websocket Chat";
$template_vars["description"] = "LunixLabs' chatbox based on HTML5 Websockets";
$template_vars["keywords"] = "LunixLabs,Projects,websockets,chat,html5";

include(TEMPLATE . "projects/websockets/chat.tpl.php");