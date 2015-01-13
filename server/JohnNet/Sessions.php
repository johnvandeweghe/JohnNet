<?php
namespace JohnNet;

class Sessions extends \Stackable {

    public function __construct(){

    }

    public function findByKey($sessionKey){
        foreach($this as $session){
            if($session->sessionKey == $sessionKey){
                return $session;
            }
        }

        return false;
    }

    public function newSession(){
        $session = new Session();
        while($this->)
    }

    public function run(){}
}
