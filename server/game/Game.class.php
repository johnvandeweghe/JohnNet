<?php
class Game {
	public $units;
	
	public $map;
	
	public function __construct(Map $map){
		$this->map = $map;
	}
}