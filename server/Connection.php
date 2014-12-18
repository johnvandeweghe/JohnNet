<?php
namespace JohnNet;


abstract class Connection extends \Stackable {
    public $socket;

    public $ready = false;
    public $closed = false;

    public function __construct(&$socket){
        $this->socket = $socket;
    }

    public function writeRaw($payload){
        return fwrite($this->socket, $payload, strlen($payload));
    }

    public function readOnce(){
        $buffer = '';

        if($result = fread($this->socket, 1)){
            $buffer .= $result;
        }

        if($result = fread($this->socket, 8192)){
            $buffer .= $result;
        }

        return $buffer;
    }

    public function isReady(){
        return $this->ready && !$this->closed;
    }

    public function ready(){
        $this->ready = true;
    }

    public function close(){
        $this->closed = true;
        if(is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

}