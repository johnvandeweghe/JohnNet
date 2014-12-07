var PubSubHost = function(applicationID, apiKey){
	this.applicationID = applicationID;
	this.apiKey = apiKey;

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
