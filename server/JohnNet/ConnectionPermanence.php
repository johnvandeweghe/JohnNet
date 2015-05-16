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
                $has_channel = false;
                foreach($session->subscriptions as $subscription){
                    if($subscription == $channel){
                        $has_channel = true;
                        break;
                    }
                }
                if($has_channel){
                    $session->payloads[] = $payload;
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
