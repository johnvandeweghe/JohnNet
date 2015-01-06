var PubSubHost = function(app_id, app_secret, debug){
	this.app_id = app_id;
	this.app_secret = app_secret;
	this.debug = (typeof debug !== 'undefined') ? debug : true;
	this.retryLimit = 6;

	var _this = this;

	var websocket = null;
	var retries = 0;

	var log = function(){
		if(_this.debug) {
			console.log.apply(console, arguments);
		}
	};

	var onOpen = function(m){
		log('Connected');
		_this.register();
		retries = 0;
	};

	var onMessage = function(payload){
		log(payload);
	};

	var onClose = function(m){
		log('Closed');
		if(retries <= _this.retryLimit) {
			setTimeout(_this.connect, 500);
			retries++;
		}
	};

	var onError = function(m){
		log('Error:');
		log(m);
		if(retries <= _this.retryLimit) {
			setTimeout(_this.connect, 500);
			retries++;
		}
	};

	this.connect = function(){
		log('Reconnecting...');
		websocket = new WebSocket('ws://localhost:8080');
		websocket.onerror = onError;
		websocket.onmessage = onMessage;
		websocket.onclose = onClose;
		websocket.onopen = onOpen;
	};

	this.close = function(){
		websocket.close('Closed by client');
	};

	this.register = function(){
		var obj = {'type': 'register', 'payload': {'app_id': this.app_id, 'app_secret': this.app_secret}};
		log('Registering...');
		websocket.send(JSON.stringify(obj));
	};

	this.subscribe = function(channel){
		log('Subscribing to ' + channel + '...');
		var obj = {'type': 'subscribe', 'payload': {'channel': channel}};
		websocket.send(JSON.stringify(obj));
	};

	this.unsubscribe = function(channel){
		var obj = {'type': 'unsubscribe', 'payload': {'channel': channel}};
		websocket.send(JSON.stringify(obj));
	};

	this.publish = function(channel, payload){

	};

	this.connect();
};
