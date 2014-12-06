var PubSubHost = function(applicationID, apiKey){
	this.applicationID = applicationID;
	this.apiKey = apiKey;

	var websocket = new Websocket('ws://localhost');

	websocket.onopen = function(){

	};

	websocket.onmessage = function(payload){

	};

	websocket.onclose = function(){

	};


	this.subscribe = function(channel){

	};

	this.publish = function(channel, payload){

	};
};
