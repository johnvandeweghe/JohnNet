<?php
namespace Websocket;

class User {
	public $id;
	public $socket;
	public $handshake = false;
	public $closed = false;
	public $channels = [];
	public $cookie;
}