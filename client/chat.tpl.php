                                                                cmmmmm    xxm     xxxxxxxx                                                     <?php
if (!defined('INCLUDED')) {
    header('HTTP/1.1 404 Not Found', 404);
	exit;
}

require_once(TEMPLATE . "header.tpl.php");
?>
<style type="text/css">
	#log {
		height: 400px;
		width: 500px;
		overflow-y: scroll;
		border-style: solid;
		border-width: 2px;
		border-color: #444444;
		border-radius: 5px;
	}
	#log p.msg {
		border-bottom-style: solid;
		border-bottom-width: 1px;
		border-color: #AAAAAA;
		padding-left: 5px;
	}
	#msg {
		width: 440px;
	}
	#left {
		display: inline-block;
		width: 525px;
	}
	#right {
		display: inline-block;
		width: 250px;
		vertical-align: top;
		overflow-y: scroll;
		border-style: solid;
		border-width: 2px;
		border-color: #444444;
		border-radius: 5px;
		padding: 5px;
	}
		
</style>
<h2>WebSockets</h2>
<h3>Chatbox</h3>
<div id="left">
 <div id="log"></div>
 <input id="msg" type="textbox" onkeypress="return keypress(event);"/>
 <button onclick="send()">Send</button>
</div>
<div id="right">
<p>Users:</p>
</div>
<script type="text/javascript">
var socket;

var notify = new Audio("notify.mp3");

var userID;

function init(){
	var host = "ws://lunixlabs.com:8080/chat";
	log("<span style='color: blue'>Connecting...</span>");
	try {
		socket = new WebSocket(host);
		socket.onopen    = function(msg){
			log("<span style='color: blue'>Connected</span>");
			$("#msg").prop('disabled', false);
			$("button[onclick='send()']").prop('disabled', false);};
		socket.onmessage = function(msg){
			var message = JSON.parse(msg.data);
			switch(message.type){
				case "message":
					log("<span style='font-weight: bold'>" + message.user + ":</span> " + message.message);
					break;
				case "me":
					log("<span style='font-weight: bold'>" + message.user + "</span> " + message.message);
					break;
				case "server_message":
					log("<span style='color: blue;'>" + message.message + "</span>");
					break;
				case "join":
					$("<p id='user_" + message.user.replace(" ", "_") + "'>" + message.user + "</p>").hide().appendTo("#right").fadeIn(1000);
					log("<span style='color: blue'>" + message.user + " has joined the room</span>");
					break;
				case "leave":
					$("#user_" + message.user.replace(" ", "_").replace("#", "\\#")).fadeOut(1000, function(){$(this).remove()});
					log("<span style='color: blue'>" + message.user + " has left the room</span>");
					break;
				case "users":
					for(user in message.users){
						$("#right").append("<p id='user_" + message.users[user].replace(" ", "_") + "'>" + (message.users[user] == message.you ? "(You) " : "") + message.users[user] + "</p>");
					}
					break;
			}
		};
		socket.onclose   = function(msg){
			log("<span style='color: red'>Disconnected</span>");
			$("#msg").prop('disabled', true);
			$("button[onclick='send()']").prop('disabled', true);
		};
	}
	catch(ex){
		log(ex);
	}
	$("msg").focus();
}

function keypress(e){
	if(e.keyCode==13){
		send();
		return false;
	}
}
function send(){
	var txt,msg;
	if(socket.readyState != 1)
		return;
	txt = $("#msg");
	msg = txt.val();
	if(!msg){ return; }
	txt.val("");
	txt.focus();
	try {
		socket.send(JSON.stringify({type: "message", message: msg}));
		if(msg[0] != "/")
			log("<span style='font-weight: bold'>You:</span> " + msg);
	} catch(ex){
		log(ex);
	}
}
function log(msg){
	$("#log").append("<p class='msg'>" + msg + "</p>");
	document.getElementById("log").scrollTop = 100000;
	var not = new Audio();
	not.src = notify.src;
	not.play();
}
init();
</script>
<?php
require_once(TEMPLATE . "footer.tpl.php");