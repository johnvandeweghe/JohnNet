<?php
namespace JohnNet;

/**
 * Class Connections
 * @package JohnNet
 */
class Connections extends \Stackable {

    /**
     *
     */
    public function __construct(){

    }


    /**
     * @param $thread
     * @param $socket
     * @return bool|Connection\ClientConnection
     */
    public function findByThreadIDAndSocket($thread, &$socket){
        foreach($this as $i => &$connection){
            if($connection->handlerID == $thread && $connection->socket == $socket){
                return $connection;
            }
        }

        return false;
    }


    /**
     * @param $conn
     */
    public function remove($conn){
        foreach($this as $i => $connection) {
            if ($connection === $conn) {
                echo "UNSET $i\n";
                unset($this[$i]);
            }
        }
    }

    /**
     * @param $thread
     * @return Connection\ClientConnection[]
     */
    public function getAllSocketsByThread($thread){
        $return = [];

        foreach($this as $connection) {
            if($connection->handlerID == $thread && !$connection->closed) {
                $return[] = $connection->socket;
            }
        }

        return $return;
    }

    /**
     * @param $applicationID
     * @param $channel
     * @return Connection\ClientConnection[]
     */
    public function getAllByAppIDAndChannel($applicationID, $channel){
        $return = [];

        foreach($this as $connection) {
            if(!$connection->closed && $connection->applicationID === $applicationID && $connection->isSubscribed($channel)) {
                $return[] = $connection;
            }
        }
        return $return;
    }

    /**
     *
     */
    public function run(){}
}
