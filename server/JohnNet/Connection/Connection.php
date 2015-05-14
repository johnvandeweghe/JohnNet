<?php
namespace JohnNet\Connection;


abstract class Connection extends \Stackable {
    public $socket;

    //Raw, original socket. "$socket" is temporarily not this when read from another thread
    public $rawSocket;
    public $name;
    public $handlerID;

    public $ready = false;
    public $closed = false;

    public function __construct(&$socket, $handlerID){
        $this->socket = $socket;
        $this->rawSocket = $socket;
        $this->name = stream_socket_get_name($socket, true);
        echo "New connection: {$this->name}\n";
        $this->handlerID = $handlerID;
    }

    public function writeRaw($payload){
        if($this->closed)
            return false;
        return fwrite(is_resource($this->socket) ? $this->socket : $this->rawSocket, $payload, strlen($payload));
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

        if(is_resource($this->socket) || is_resource($this->rawSocket)) {
            stream_socket_shutdown(is_resource($this->socket) ? $this->socket : $this->rawSocket, STREAM_SHUT_RDWR);
            //This causes weird crashes. Let's just not close them.
            //fclose(is_resource($this->socket) ? $this->socket : $this->rawSocket);
        }

        $this->socket = false;
        $this->rawSocket = false;
    }

}