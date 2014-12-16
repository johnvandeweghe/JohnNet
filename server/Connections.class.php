<?php
namespace JohnNet;

class Connections extends \Stackable {


    public function findBySocket(&$socket){
        foreach($this as &$connection){
            if($connection->socket == $socket){
                return $connection;
            }
        }

        return false;
    }

    public function getAllSockets(){
        $return = [];

        foreach($this as &$connection){
            $return[] = &$connection->socket;
        }

        return $return;
    }
}
