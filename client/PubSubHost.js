var PubSubHost = function(app_id, app_secret, debug){
	this.app_id = app_id;
	this.app_secret = app_secret;
	this.debug = (typeof debug !== 'undefined') ? debug : true;
	this.retryLimit = 6;

	var websocket = new WebSocket('ws://localhost:8080');
	var retries = 0;

	websocket.onopen = function(m){
		this.register();
		retries = 0;
	};

	websocket.onmessage = function(payload){
		console.log(payload);
	};

	websocket.onclose = function(m){
		console.log(m);
		if(retries <= this.retryLimit) {
			setTimeout(this.connect, 500);
			retries++;
		}
	};

	websocket.onerror = function(m){
		console.log(m);
		if(retries <= this.retryLimit) {
			setTimeout(this.connect, 500);
			retries++;
		}
	};

	this.connect = function(){
		websocket = new WebSocket('ws://localhost:8080');
	}

	this.register = function(){
		var obj = {'type': 'register', 'payload': {'app_id': this.app_id, 'app_secret': this.app_secret}};
		websocket.send(JSON.stringify(obj));
	};

	this.subscribe = function(channel){
		var obj = {'type': 'subscribe', 'payload': {'channel': channel}};
		websocket.send(JSON.stringify(obj));
	};

	this.publish = function(channel, payload){

	};
};
