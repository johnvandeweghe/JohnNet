<?php
namespace JohnNet;

class ConnectionPermanence extends \Stackable {

    public function __construct(){

    }

    public function newSession(){
        do {
            $session = new Session();
        } while($this->findByKey($session->sessionKey));
        $this[] = $session;
    }

    public function run(){}
}
