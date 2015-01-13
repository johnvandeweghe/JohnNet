<?php
namespace JohnNet;

class Session extends \Stackable {

    public $sessionKey;

    public function __construct($sessionKey = false){
        if(!$sessionKey){
            $sessionKey = md5(microtime(true));
        }
        $this->sessionKey = $sessionKey;
    }

    public function run(){}
}
