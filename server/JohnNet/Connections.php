<?php
namespace JohnNet;

class Connections extends \Stackable {

    public function __construct(){

    }


    public function findByThreadIDAndSocket($thread, &$socket){
        foreach($this as $i => &$connection){
            if($connection->handlerID == $thread && $connection->socket == $socket){
                return $connection;
            }
        }

        return false;
    }


    public function remove($conn){
        foreach($this as $i => $connection) {
            if ($connection === $conn) {
                echo "UNSET $i\n";
                unset($this[$i]);
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

    public function getAllByAppIDAndChannel($applicationID, $channel){
        $return = [];

        foreach($this as $connection) {
            if($connection->applicationID === $applicationID && $connection->isSubscribed($channel)) {
                $return[] = $connection;
            }
        }
        return $return;
    }

    public function run(){}
}
