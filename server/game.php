<?php
define('INCLUDED', true);

require_once("../../include.php");


$template_vars["title"] = "LunixLabs' Websocket Game";
$template_vars["description"] = "LunixLabs' game based on HTML5 Websockets";
$template_vars["keywords"] = "LunixLabs,Projects,websockets,game,html5";

include(TEMPLATE . "projects/websockets/game.tpl.php");