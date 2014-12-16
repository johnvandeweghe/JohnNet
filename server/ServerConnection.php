<?php
namespace JohnNet;

class ServerConnection extends Connection {

    public function __construct(&$socket){
        parent::__construct($socket);
    }

    public function handleRead($buffer){

    }
}
