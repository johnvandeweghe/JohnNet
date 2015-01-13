<?php
namespace JohnNet\Connection;

class ServerConnection extends Connection {

    public function __construct(&$socket, $handlerID){
        parent::__construct($socket, $handlerID);
    }

    public function handleRead($buffer){

    }
}
