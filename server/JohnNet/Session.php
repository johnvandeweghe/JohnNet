<?php
namespace JohnNet;


class Session {
    public $sessionKey;
    public $lastSeen;
    public $payloads;
    public $subscriptions;
    public $expiration;

    public function __construct($sessionKey, $lastSeen, $subscriptions, $expiration = 300){
        $this->sessionKey = $sessionKey;
        $this->lastSeen = $lastSeen;
        $this->subscriptions = $subscriptions;
        $this->payloads = new \Stackable;
        $this->expiration = $expiration;
    }

    public function expired(){
        return time() - $this->lastSeen > $this->expiration;
    }
}