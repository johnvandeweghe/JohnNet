<?php
class Unit {
	public $move, $rangeMin, $rangeMax, $id;
	
	public function __construct($id, $move, $rangeMin, $rangeMax){
		$this->id = $id;
		$this->move = $move;
		$this->rangeMin = $rangeMin;
		$this->rangeMax = $rangeMax;
	}
}