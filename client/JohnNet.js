var JohnNet = function(app_id, app_secret, debug){
	this.app_id = app_id;
	this.app_secret = app_secret;
	this.debug = (typeof debug !== 'undefined') ? debug : true;
	this.retryLimit = 6;

	var _this = this;

	var websocket = null;
	var retries = 0;
	var eventHandlers = [];

	var sendPayload = function(type, payload){
		return websocket.send(JSON.stringify({'type': type, 'payload': payload}));
	};

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
		payload = JSON.parse(payload.data);
		if(typeof payload !== 'undefined' && typeof payload.type !== 'undefined'){
			var type = payload.type;
			payload = payload.payload;

			switch(type){
				case 'register':
					if(payload.status == 'success') {
						_this.fireEvent('registration_success', payload);
					} else {
						_this.fireEvent('registration_failed', payload);
					}
					break;
				case 'subscribe':
					if(payload.status == 'success') {
						_this.fireEvent('subscription_success', payload);
					} else {
						_this.fireEvent('subscription_failed', payload);
					}
					break;
				case 'unsubscribe':
					if(payload.status == 'success') {
						_this.fireEvent('unsubscription_success', payload);
					} else {
						_this.fireEvent('unsubscription_failed', payload);
					}
					break;
				case 'publish':
					if(payload.status == 'success') {
						_this.fireEvent('publish_success', payload);
					} else {
						_this.fireEvent('publish_failed', payload);
					}
					break;
				case 'payload':
					_this.fireEvent('payload_received', payload);
					break;
			}
		}
	};

	var onClose = function(m){
		log('Closed');
		if(retries <= _this.retryLimit) {
			setTimeout(_this.connect, 500);
			retries++;
		} else {
			log('Giving up on connecting');
		}
	};

	var onError = function(m){
		log('Error:');
		log(m);
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
		websocket.close('Closed by client', 1000);
	};

	this.register = function(){
		var payload = {'app_id': this.app_id, 'app_secret': this.app_secret};
		sendPayload('register', payload);
		this.fireEvent('registration_sent', payload);
	};

	this.subscribe = function(channel){
		var payload = {'channel': channel};
		sendPayload('subscribe', payload);
		this.fireEvent('subscription_sent', payload);
	};

	this.unsubscribe = function(channel){
		var payload = {'channel': channel};
		sendPayload('unsubscribe', payload);
		this.fireEvent('unsubscription_sent', payload);
	};

	this.publish = function(channel, payload){
		var wspayload = {'channel': channel, 'payload': payload};
		sendPayload('publish', wspayload);
		this.fireEvent('publish_sent', wspayload);
	};

	this.bind = function(event, handler){
		if(typeof eventHandlers[event] === 'undefined'){
			eventHandlers[event] = [];
		}
		eventHandlers[event].push(handler);
	};

	this.bindChannel = function(channel, handler){
		this.bind('publish', function(data){
			if(data.channel === channel) {
				handler.apply(_this, data);
			}
		});
	};

	this.fireEvent = function(event, data) {
		if (typeof eventHandlers[event] !== 'undefined') {
			for(var i in eventHandlers[event]){
				if(eventHandlers[event][i] instanceof Function){
					eventHandlers[event][i].apply(_this, [data]);
				}
			}
		}
	};

	this.connect();
};
