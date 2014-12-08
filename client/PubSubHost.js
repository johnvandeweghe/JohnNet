var PubSubHost = function(app_id, app_secret, debug){
	this.app_id = app_id;
	this.api_secret = app_secret;
	this.debug = (typeof debug !== 'undefined') ? debug : true;

	var websocket = new WebSocket('wss://localhost:8080');

	websocket.onopen = function(m){
		console.log(m);
	};

	websocket.onmessage = function(payload){
		console.log(payload);
	};

	websocket.onclose = function(m){
		console.log(m);
	};

	websocket.onerror = function(m){
		console.log(m);
	};


	this.subscribe = function(channel){
		var obj = {'channel': channel, 'payload': 'subscribe'};
		websocket.send(JSON.stringify(obj));
	};

	this.publish = function(channel, payload){

	};
};
