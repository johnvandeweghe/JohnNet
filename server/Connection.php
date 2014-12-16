<?php
namespace JohnNet;


abstract class Connection extends \Stackable {
    public $socket;

    private $ready = false;
    protected $closed = false;

    public function __construct(&$socket){
        $this->socket = $socket;
    }

    //Send a message to a specific WebSocket connection, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
    public function writeWS($payload, $opcode=0x1){
        if(!$this->isReady()) {
            return;
        }

        $payload = WebSocket::frame($payload, $opcode);
        return $this->writeRaw($payload);
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