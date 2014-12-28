<?php
namespace JohnNet;

class Connections extends \Stackable {

    public function __construct(){

    }


    public function findBySocket(&$socket){
        foreach($this as $i => &$connection){
            if($connection->socket == $socket){
                return $connection;
            }
        }

        return false;
    }


    public function remove($conn){
        foreach($this as &$connection) {
            if ($connection == $conn) {
                unset($connection);
            }
        }
    }

    public function getAllSocketsByThread($thread){
        $return = [];

        foreach($this as $connection) {
            if($connection->handlerID == $thread) {
                $return[] = $connection->socket;
            }
        }
        return $return;
    }
    public function run(){}
}
