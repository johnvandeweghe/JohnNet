<?php
namespace JohnNet;

class Connections extends \Stackable {

    public function add(&$connection){
        var_dump($connection);
        $this[] = $connection;
        var_dump($this);
    }

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

        echo "1\n";

        //var_dump($this);

        foreach($this as $connection){
            $return[] = $connection->socket;
        }

        echo "2\n";

        return $return;
    }
}
