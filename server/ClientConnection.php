<?php
namespace JohnNet;

class ClientConnection extends Connection {

    private $buffer = '';

    private $isRegistered = false;

    private $isHandshake = false;

    public $subscriptions;
    public $applicationID;

    //WARNING!!!!!
    //Using this currently WILL DEFINITELY SEGFAULT
    //Needs to be wrapped into a stackable, once the time comes to use it
    private $cookie = [];

    public function __construct(&$socket){
        $this->subscriptions = new \Stackable();
        parent::__construct($socket);
    }

    public function handleRead(&$handler, $buffer){
        if(!$this->isHandshake){
            $handshake = $this->handshake($buffer);
            if($handshake !== true){
                $err = '';
                if($handshake == 400){
                    $err = "HTTP/1.1 400 Bad Request";
                } elseif($handshake == 404){
                    $err = "HTTP/1.1 404 Not Found";
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
                        $this->close($data);
                        break;
                    case 0x9: //Ping
                        $this->writeWS($data, 0xA);
                        break;
                    case 0xA: //Pong
                        break;
                    default:
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
                            case 'publish':
                                if($this->isReady()){
                                    if($sub = $this->isSubscribed($payload['payload']['channel'])){
                                        $this->publish($payload['payload']['channel'], $payload['payload']);
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
                                        'message' => 'Not yet registered'
                                    ]);
                                }
                                break;
                        }
                    } else {
                        //invalid payload
                    }
                }
            }
        }
    }

    private function handshake($buffer){
        $temp = explode("\r\n", str_replace("\r\n\r\n", "", $buffer));

        //$get = str_replace(array("GET "," HTTP/1.1"), "", array_shift($temp));

        $headers = array();
        foreach($temp as $header){
            list($key, $value) = explode(": ", $header);
            $headers[$key] = $value;
        }

        if(!isset($headers['Host']) ||
            !(isset($headers['Upgrade']) && strtolower($headers['Upgrade']) == 'websocket') ||
            !(isset($headers['Connection']) && stristr($headers['Connection'], 'upgrade') !== false) ||
            !isset($headers['Sec-WebSocket-Key']) ||
            !(isset($headers['Sec-WebSocket-Version']) && $headers['Sec-WebSocket-Version'] == 13)
        ){
            return 400;
        }

        if(isset($headers['Sec-WebSocket-Extensions']) && $headers['Sec-WebSocket-Extensions']){
            //$this->user->extensions = explode('; ', $headers['Sec-WebSocket-Extensions']);
        }

        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: " . base64_encode(sha1($headers["Sec-WebSocket-Key"] . Server::GUID, true)) . "\r\n" .
            "\r\n";

        $this->writeRaw($upgrade);
        $this->isHandshake = true;

        if(isset($headers["Cookie"])){
            $cookies = explode("; ", $headers["Cookie"]);
            if($cookies){
                foreach($cookies as $cookie){
                    list($key, $value) = explode("=", $cookie);
                    $this->cookie[$key] = $value;
                }
            }
        }

        return true;
    }

    //Mark a user closed and send them $msg as the reason
    public function close($msg = ''){
        //If the conditions are right to send a message (handshake completed, not closed) send a close message
        if($this->isHandshake && !$this->closed) {
            $this->user->write($msg, 0x8);
        }
        parent::close();
    }

    public function isReady(){
        return parent::isReady() && $this->isHandshake && $this->isRegistered;
    }

    public function register($app_id, $app_secret, &$handler){

        if(isset($handler->application_secrets[$app_id]) && $handler->application_secrets[$app_id]->secret === $app_secret) {
            $this->subscriptions = new \Stackable();
            $this->applicationID = $app_id;
            $this->isRegistered = true;
            return true;
        } else {
            return false;
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