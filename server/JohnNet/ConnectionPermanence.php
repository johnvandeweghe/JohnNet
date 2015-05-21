<?php
namespace JohnNet;

/**
 * Class ConnectionPermanence
 * @package JohnNet
 */
class ConnectionPermanence extends \Stackable {

    /**
     *
     */
    public function __construct(){

    }

    /**
     *
     */
    public function run(){}

    /**
     * @param $channel
     * @param $payload
     */
    public function addPayloadBySubscription($channel, $payload){
        foreach($this as $i => $session){
            if(!$session->expired()){
                foreach($session->subscriptions as $subscription){
                    if($subscription == $channel){
                        echo "Added payload to session\n";
                        $session->payloads[] = $payload;
                        break;
                    }
                }
            } else {
                unset($this[$i]);
            }
        }
    }

    /**
     * @param $sessionKey
     * @return Session|bool
     */
    public function findBySessionKey($sessionKey){
        foreach($this as $session){
            if($session->sessionKey == $sessionKey){
                return $session;
            }
        }
        return false;
    }
}
