<?php
interface Action {

	public function __construct(WebSocket $ws, $route);
	public function run();
	
}