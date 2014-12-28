<?php
namespace JohnNet;

class Connections extends \Stackable {

    public function __construct(){

    }


    public function findBySocket(&$socket){
        foreach($this as $pool){
            foreach($pool as $connection){
                if($connection->socket == $socket){
                    return $connection;
                }
            }
        }

        return false;
    }


    public function remove($conn){
        foreach($this as &$pool){
            foreach($pool as &$connection) {
                if ($connection == $conn) {
                    unset($connection);
                }
            }
        }
    }

    public function getAllSocketsByThread($thread){
        $return = [];
        //$connections = $this->connections->chunk(count($this->connections), true);
        var_dump($this);
        exit();
        foreach($this[$thread] as $connection) {
            $return[] = $connection->socket;
        }
        var_dump($return);
        return $return;
    }
    public function run(){}
}
