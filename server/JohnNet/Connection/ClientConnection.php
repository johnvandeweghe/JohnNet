<?php
namespace JohnNet\Connection;

use JohnNet\ConnectionHandler;
use JohnNet\ConnectionPermanence;
use \JohnNet\Server;
use JohnNet\Session;

class ClientConnection extends Connection {

    public $buffer = '';

    public $isRegistered = false;

    public $isHandshake = false;

    public $subscriptions;
    public $permanence;
    public $applicationID;

    public $sessionKey = '';

    public static $server;

    public $id;

    public $lastPing = 0;
    public $ping = 0;

    public $pingsSincePong = 0;

    public function __construct($socket, $handlerID, \Stackable &$subscriptions, ConnectionPermanence &$permanence){
        parent::__construct($socket, $handlerID);
        $this->subscriptions = $subscriptions;
        $this->permanence = $permanence;

        $this->id = (string)$socket;
    }

    public function handleRead(ConnectionHandler &$handler, $buffer){
        if(!$this->isHandshake){
            $handshake = $this->handshake($buffer);
            if($handshake !== true){
                $err = '';
                if($handshake == 400){
                    $err = "HTTP/1.0 400 Bad Request";
                } elseif($handshake == 200){
                    $err = "";
                }
                $this->writeRaw($err);
                $this->close();
            }
        } else {
            $opcode = ord($buffer[0]) & 15;
            $data = Server::unframe($buffer);
            if($opcode >= 0x8 && $opcode <= 0xF){
                switch($opcode){
                    case 0x8: //Close
                        echo "Received close\n";
                        $this->close($data, true);
                        break;
                    case 0x9: //Ping
                        $this->writeWS($data, 0xA);
                        break;
                    case 0xA: //Pong
                        $this->pingsSincePong = 0;
                        $this->ping = round(microtime(true) - $this->lastPing, 1);
                        echo "pong: " . $this->ping . "\n";
                        break;
                    default:
                        echo "unknown control frame received\n";
                        $this->close("Unknown control frame received");
                        break;
                }
            } else {
                if($opcode === 0x0){
                    $this->buffer .= $data;
                } else {
                    $payload = json_decode($this->buffer . $data, true);
                    $this->buffer = '';

                    if($payload && isset($payload['type']) && isset($payload['payload']) && $opcode === 0x1){
                        switch($payload['type']) {
                            case 'register':
                                //Register user to application
                                if (!isset($payload['payload']['app_id']) || !isset($payload['payload']['app_secret'])) {
                                    $this->writePayload('register', [
                                        'status' => 'failed',
                                        'message' => 'Missing app id or app secret'
                                    ]);
                                    break;
                                }

                                if($this->register($payload['payload']['app_id'], $payload['payload']['app_secret'], $handler)) {
                                    $this->writePayload('register', [
                                        'status' => 'success',
                                        'message' => 'Registered'
                                    ]);
                                    break;
                                }
                                $this->writePayload('register', [
                                    'status' => 'failed',
                                    'message' => 'Incorrect credentials (credential failure logged and reported)'
                                ]);
                                break;
                            case 'subscribe':
                                if($this->isReady()){
                                    if($this->subscribe($payload['payload']['channel'])) {
                                        $this->writePayload('subscribe', [
                                            'status' => 'success',
                                            'message' => 'Subscribed to channel'
                                        ]);
                                    } else {
                                        $this->writePayload('subscribe', [
                                            'status' => 'failed',
                                            'message' => 'Access to channel denied'
                                        ]);
                                    }
                                } else {
                                    $this->writePayload('subscribe', [
                                        'status' => 'failed',
                                        'message' => 'Not yet registered'
                                    ]);
                                }
                                break;
                            case 'unsubscribe':
                                if($this->isReady()){
                                    if($this->isSubscribed($payload['payload']['channel'])){
                                        $this->unsubscribe($payload['payload']['channel']);
                                        $this->writePayload('subscribe', [
                                            'status' => 'success',
                                            'message' => 'Unsubscribed from channel'
                                        ]);
                                    } else {
                                        $this->writePayload('subscribe', [
                                            'status' => 'failed',
                                            'message' => 'Not subscribed to that channel'
                                        ]);
                                    }
                                } else {
                                    $this->writePayload('subscribe', [
                                        'status' => 'failed',
                                        'message' => 'Not yet registered'
                                    ]);
                                }
                                break;
                            case 'publish':
                                if($this->isReady()){
                                    if($sub = $this->isSubscribed($payload['payload']['channel'])){
                                        if(isset($payload['payload']['payload']) && isset($payload['payload']['channel'])){
                                            $handler->publish($this->applicationID, $payload['payload']['channel'], $payload['payload']['payload'], $this);
                                            $this->writePayload('publish', [
                                                'status' => 'success',
                                                'message' => 'Payload published to channel'
                                            ]);
                                        } else {
                                            $this->writePayload('publish', [
                                                'status' => 'failed',
                                                'message' => 'Not subscribed to channel'
                                            ]);
                                        }
                                    } else {
                                        $this->writePayload('publish', [
                                            'status' => 'failed',
                                            'message' => 'Not subscribed to channel'
                                        ]);
                                    }
                                } else {
                                    $this->writePayload('publish', [
                                        'status' => 'failed',
                                        'message' => 'Not yet registered'
                                    ]);
                                }
                                break;
                        }
                    } else {
                        $this->writePayload('system', [
                            'status' => 'failed',
                            'message' => 'Invalid data received. JSON only.'
                        ]);
                    }
                }
            }
        }
    }

    public function handshake($buffer){
        $temp = explode("\r\n", str_replace("\r\n\r\n", "", $buffer));

        $get = str_replace(array("GET "," HTTP/1.1"), "", array_shift($temp));

        $headers = array();
        foreach($temp as $header){
            list($key, $value) = explode(": ", $header);
            $headers[$key] = $value;
        }

        echo implode(',', array_keys($headers)) . "\n";

        $loadSession = false;
        if(isset($headers["Cookie"])){
            $cookies = explode("; ", $headers["Cookie"]);

            if($cookies){
                foreach($cookies as $cookie){
                    list($key, $value) = explode("=", $cookie);
                    echo $cookie . "\n";
                    if($key == 'session_id'){
                        $this->sessionKey = $value;
                        $loadSession = true;
                    }
                }
            }
        } else {
            $this->sessionKey = md5(microtime(true).rand());
        }
        $expires = new \DateTime('1 week');

        if(isset($headers['Host']) && ($get === '/text.html' || $get === '/JohnNet.js')){
            echo $this->name . " has a page request for $get\n";
            //Test page
            $contents = file_get_contents('../client' . $get);
            $response = "HTTP/1.0 200 OK\r\n" .
                "Content-Type: " . ($get === '/text.html' ? 'html' : 'text/javascript') . "\r\n" .
                "Content-Length: " . strlen($contents) . "\r\n".
                "Set-Cookie: session_id={$this->sessionKey}; Path=/; Domain=" . Server::$URL . "; Expires=" . $expires->format(\DateTime::COOKIE) . "\r\n" .
                "Server: JohnNet 0.0\r\n\r\n" .
                $contents;
                $this->writeRaw($response);
            return 200;
        } elseif(!isset($headers['Host']) ||
            !(isset($headers['Upgrade']) && strtolower($headers['Upgrade']) == 'websocket') ||
            !(isset($headers['Connection']) && stristr($headers['Connection'], 'upgrade') !== false) ||
            !isset($headers['Sec-WebSocket-Key']) ||
            !(isset($headers['Sec-WebSocket-Version']) && $headers['Sec-WebSocket-Version'] == 13)
        ){
            echo $this->name . " has a page request for $get\n";
            return 400;
        }
        echo $this->name . " has a ws request for $get\n";

        if(isset($headers['Sec-WebSocket-Extensions']) && $headers['Sec-WebSocket-Extensions']){
            //$this->user->extensions = explode('; ', $headers['Sec-WebSocket-Extensions']);
        }

        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: " . base64_encode(sha1($headers["Sec-WebSocket-Key"] . Server::GUID, true)) . "\r\n" .
            "Set-Cookie: session_id={$this->sessionKey}; Path=/; Domain=" . Server::$URL . "; Expires=" . $expires->format(\DateTime::COOKIE) . "\r\n" .
            "\r\n";

        $this->writeRaw($upgrade);
        $this->isHandshake = true;
        $this->ready();

        if($loadSession){
            if($session = $this->permanence->findBySessionKey($this->sessionKey)){
                foreach($session->payloads as $payload){
                    echo "Sending session payload!\n";
                    //$this->writePayload('payload', json_decode($payload, true));
                }
                //$this->subscriptions = $session->subscriptions;
            }
        }

        return true;
    }

    //Mark a user closed and send them $msg as the reason
    public function close($msg = '', $force = false){
        echo $this->name . " is closed\n";
//        var_dump('CLOSED', $this->socket, $msg, $force);
        //If the conditions are right to send a message (handshake completed, not closed) send a close message
        if($this->isHandshake && !$this->closed && !$force) {
            $this->writeWS($msg, 0x8);
        }

        echo "1\n";
        if($this->isHandshake) {
            echo "Creating session!\n";
            //$this->permanence[] = new Session($this->sessionKey, time(), $this->subscriptions);
        }
        echo "2\n";
        parent::close();
    }

    public function isReady(){
        return parent::isReady() && $this->isHandshake;
    }

    public function register($app_id, $app_secret, $handler){
        if(isset($handler->application_secrets[$app_id]) && $handler->application_secrets[$app_id]['secret'] === $app_secret) {
            $this->applicationID = $app_id;
            $this->isRegistered = true;
            return true;
        } else {
            return false;
        }
    }

    public function subscribe($channel){
        //TODO: Subscriptions approval logic
        if(true){
            $this->subscriptions[] = $channel;
            return true;
        }
        return false;
    }

    public function unsubscribe($channel){
        if($this->isSubscribed($channel)){
            foreach($this->subscriptions as $i=>$subs){
                if($subs === $channel){
                   unset($this->subscriptions[$i]);
                }
            }
        }
    }

    public function isSubscribed($channel){
        foreach($this->subscriptions as $sub){
            if($channel === $sub){
                return true;
            }
        }
        return false;
    }

    public function ping(){
        $this->pingsSincePong++;
        if($this->isReady()) {
            $this->lastPing = microtime(true);
            return $this->writeWS(md5(time()), 0x9);
        }
    }

    public function writePayload($type, $payload){
        return $this->writeWS(json_encode([
            'type' => $type,
            'payload' => $payload
        ]));
    }

    //Send a message to a specific WebSocket connection, $opcode corresponds to the RFC opcodes (1=text, 2=binary)
    public function writeWS($payload, $opcode=0x1){
        if(!$this->isReady()) {
            return;
        }

        $payload = Server::frame($payload, $opcode);
        return $this->writeRaw($payload);
    }
}
