(function() {
  var requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame ||
                              window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;
  window.requestAnimationFrame = requestAnimationFrame;
})();

//todo: name set of variables
var socket;
var canvas = document.getElementById("Game");
var ctx = canvas.getContext("2d");
var start = null;
var images = {};
var sounds = {};
var animates = {};

//User Data
var username = "";
var campaignLevel = 1;
var allUnits = [];
var selectedUnits = [];

//Map Maker variables
var mapMakerTile = 50;
var mapMakerLayer = 0;

//Game options
var options = {boop: true, music: .7};

//Map/unit/etc data
var campaign_maps = [[[[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[50,-1],[50,-1]],[[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[53,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[53,-1],[53,-1],[51,-1],[50,-1],[50,-1]],[[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[53,-1],[50,57],[50,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1],[51,-1],[50,-1],[50,-1]],[[70,-1],[70,-1],[50,-1],[53,-1],[53,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[51,-1],[50,-1],[50,-1]],[[70,-1],[70,-1],[70,-1],[50,-1],[53,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[50,-1],[50,-1],[50,-1]],[[50,-1],[71,-1],[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[51,-1],[51,-1],[51,-1],[50,-1],[50,57],[50,-1]],[[50,-1],[71,-1],[70,-1],[50,-1],[50,-1],[50,57],[50,57],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1]],[[50,-1],[71,-1],[70,-1],[70,-1],[50,-1],[50,57],[50,57],[50,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1]],[[50,57],[50,-1],[71,-1],[70,-1],[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1]],[[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1]],[[50,-1],[53,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[50,-1],[51,-1],[51,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1]],[[50,-1],[53,-1],[50,57],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[59,-1],[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[53,-1],[50,-1],[50,-1]],[[53,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[59,-1],[70,-1],[70,-1],[70,-1],[70,-1],[70,-1],[50,-1],[50,-1],[50,-1],[53,-1],[53,-1],[50,-1]],[[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[51,-1],[51,-1],[51,-1],[51,-1],[71,-1],[70,-1],[70,-1],[70,-1],[70,-1],[70,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1]],[[50,57],[50,-1],[51,-1],[51,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[70,-1],[70,-1],[70,-1],[50,-1],[50,-1],[50,-1]],[[50,-1],[50,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[70,-1],[50,-1],[50,-1]],[[50,-1],[50,-1],[51,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1],[70,-1]],[[50,-1],[51,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[53,-1],[53,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1],[70,-1]],[[50,-1],[51,-1],[50,-1],[50,57],[50,-1],[50,-1],[53,-1],[53,-1],[50,-1],[50,-1],[50,-1],[50,-1],[53,-1],[53,-1],[53,-1],[50,-1],[50,-1],[50,-1],[71,-1],[70,-1]],[[50,-1],[51,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,-1],[50,57],[50,-1],[50,-1],[53,-1],[53,-1],[53,-1],[50,57],[50,-1],[50,-1],[50,-1],[50,-1]]],
						];

						
//GO!
init();


function init(){
	animates["loading"] = new Loading();
	
	requestAnimationFrame(animate);
	
	var host = "ws://dev.lunixlabs.com:8080/game";
	
	try {
		socket = new WebSocket(host);
		socket.onopen = function(msg){
			animates["loading"].status = "Verifying and downloading account data";
		};
		socket.onmessage = function(msg){
			var message = JSON.parse(msg.data);
			switch(message.type){
				case "login":
					username = message.you;
					campaignLevel = message.campaignLevel;
					for(u in message.units){
						allUnits[message.units[u].id] = new Unit(message.units[u].id, message.units[u].user, message.units[u].name, message.units[u].type);
					}
					
					animates["loading"].status = "Loading Audio";
					sounds["boop"] = new Audio();
					sounds["The_Snow_Queen"] = new Audio();
					var counter = 0;
					$.each(sounds, function(i, v){
						$(sounds[i]).on('canplay canplaythrough', function(){
							counter++;
							if(counter == 2){
								counter = 0;
								animates["loading"].status = "Loading Images";
								images["textures"] = new Image();
								$.each(images, function(i, v){
									$(images[i]).load(function(){
										counter++;
										if(counter == 1){
											delete animates["loading"];
											animates["menu"] = new Menu();
										}
									});
								});
								images["textures"].src = "color_tileset_16x16_Jerom&Eiyeron_CC-BY-SA-3.0_8.png";
							}
						});
					});
					sounds["boop"].src = "boop.ogg";
					sounds["The_Snow_Queen"].src = "The Snow Queen.mp3";
					break;
				case "start_campaign":
					animates = [];
					//
					animates["unitSelect"] = new UnitSelect(message.max_player_units, message.level);
					break;
			}
		};
		socket.onclose = function(msg){
			animates = [];
			animates["loading"] = new Loading();
			animates["loading"].status = "Connection to server lost: " + msg.reason;
		};
	}
	catch(ex){
		animates["loading"].status = "Error, unable to connect to server, retrying";
		init();
	}
}
var timer = new Timer();
var then = 0;
var fps = 0;
function animate(timestamp) {
	requestAnimationFrame(animate);
	if (start === null) start = timestamp;
	var progress = timestamp - start;
	
	ctx.clearRect(0,0,canvas.width,canvas.height);
	if(animates != [])
		for(var a in animates){
			animates[a].animate(progress);
		}
		
	 timer.tick(timestamp);
 
	if (timestamp - then > 1000) {
		then = timestamp;
		fps = timer.fps() + "";
	}
	ctx.fillStyle = "rgb(0,0,0)";
	ctx.fillText(fps, canvas.width - ctx.measureText(fps).width - 5, 24);
}

function Loading(){
	this.status = "Connecting";
	
	$(canvas).css("backgroundColor", "black");
	
	this.animate = function(progress){
		var percent = (progress % 3000) / 3000;
		ctx.fillStyle = "rgb(255,255,255)";
		ctx.font = "18px sans-serif";
		ctx.textAlign = 'left';
		var dots = "";
		if(percent >= .25 && percent < .5)
			dots = ".";
		else if(percent >= .5 && percent < .75)
			dots = "..";
		else if(percent >= .75)
			dots = "...";
		ctx.fillText(this.status + dots, canvas.width/2 - ctx.measureText(this.status).width/2, canvas.height/2);
	}
}

function UnitSelect(limit, level){

	this.limit = limit;
	this.level = level;
	this.selectedY = 0;
	
	$(canvas).css("backgroundColor", "white");
	
	$(document).off();
	
	$(document).keydown(function(e){
		switch(e.keyCode){
			case 38://up
				animates["unitSelect"].moveSelectedUp();
				e.preventDefault();
				break;
			case 40://down
				animates["unitSelect"].moveSelectedDown();
				e.preventDefault();
				break;
			case 32://space
			case 13://enter
				animates["unitSelect"].select();
				e.preventDefault();
				break;
		}
	});
	
	
	this.animate = function(progress){
		ctx.font = "30px sans-serif";
		ctx.textAlign = 'center';
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("You can choose " + this.limit + " units for this level", canvas.width/2, 40);
		
		ctx.beginPath();
		ctx.strokeStyle = "rgb(0,0,0)";
		for(x = 0; x < this.limit; x++){
			if(this.selectedY == x)
				ctx.strokeStyle = "rgb(100,100,100)";
			else
				ctx.strokeStyle = "rgb(0,0,0)";
			ctx.rect(49 + x * 53, 100 + Math.floor(x / 6) * 53, 50, 50);
			if(typeof selectedUnits[x] != 'undefined')
				ctx.fillText(selectedUnits[x]["name"], 49 + x * 53, 160 + Math.floor(x / 6) * 53);
			else
				ctx.fillText(selectedUnits[x]["Select"], 49 + x * 53, 160 + Math.floor(x / 6) * 53);
		}
		ctx.stroke();
		
		if(this.selectedY == this.limit){
			ctx.fillStyle = "rgb(200,200,200)";
		} else {
			ctx.fillStyle = "rgb(100,100,100)";
		}
		ctx.fillRect(8, canvas.height - 120, canvas.width - 16, 40);
		
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("Back", canvas.width/2, canvas.height - 90);
		
		if(this.selectedY == this.limit + 1){
			ctx.fillStyle = "rgb(200,200,200)";
		} else {
			ctx.fillStyle = "rgb(100,100,100)";
		}
		ctx.fillRect(8, canvas.height - 60, canvas.width - 16, 40);
		
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("Go!", canvas.width/2, canvas.height - 30);
	}
	
	this.moveSelectedUp = function(){
		if(this.selectedY > 0){
			this.selectedY --;
			playSound("boop");
		}
	};
	
	this.moveSelectedDown = function(){
		if(this.selectedY < this.limit+1){
			this.selectedY ++;
			playSound("boop");
		}
	};
	
	this.select = function(){
		switch(this.selectedY){
			case this.limit://Back
				animates = [];
				animates["menu"] = new Menu();
				animates["menu"].level = "campaign";
				playSound("boop");
				break;
			case this.limit+1://GO
				if(selectedUnits.length == this.limit){
					animates = [];
					animates["map"] = new Map(campaign_maps[this.level-1]);
					playSound("boop");
				}
				break;
			default:
				playSound("boop");
				break;
		}
	};
}


function ShowUnits(select){

	this.select = select;
	this.selectedY = 0;
	
	$(canvas).css("backgroundColor", "white");
	
	$(document).off();
	
	$(document).keydown(function(e){
		switch(e.keyCode){
			case 38://up
				animates["showUnits"].moveSelectedUp();
				e.preventDefault();
				break;
			case 40://down
				animates["showUnits"].moveSelectedDown();
				e.preventDefault();
				break;
			case 32://space
			case 13://enter
				animates["showUnits"].select();
				e.preventDefault();
				break;
		}
	});
	
	
	this.animate = function(progress){
		ctx.font = "30px sans-serif";
		ctx.textAlign = 'center';
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("You can choose " + this.limit + " units for this level", canvas.width/2, 40);
		
		ctx.beginPath();
		ctx.strokeStyle = "rgb(0,0,0)";
		for(x = 0; x < this.limit; x++){
			if(this.selectedY == x)
				ctx.strokeStyle = "rgb(100,100,100)";
			else
				ctx.strokeStyle = "rgb(0,0,0)";
			ctx.rect(50 + x * 36, 100 + Math.floor(x / 10) * 36, 32, 32);
		}
		ctx.stroke();
		
		if(this.selectedY == this.limit){
			ctx.fillStyle = "rgb(200,200,200)";
		} else {
			ctx.fillStyle = "rgb(100,100,100)";
		}
		ctx.fillRect(8, canvas.height - 120, canvas.width - 16, 40);
		
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("Back", canvas.width/2, canvas.height - 90);
		
		if(this.selectedY == this.limit + 1){
			ctx.fillStyle = "rgb(200,200,200)";
		} else {
			ctx.fillStyle = "rgb(100,100,100)";
		}
		ctx.fillRect(8, canvas.height - 60, canvas.width - 16, 40);
		
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.fillText("Go!", canvas.width/2, canvas.height - 30);
	}
	
	this.moveSelectedUp = function(){
		if(this.selectedY > 0){
			this.selectedY --;
			playSound("boop");
		}
	};
	
	this.moveSelectedDown = function(){
		if(this.selectedY < this.limit+1){
			this.selectedY ++;
			playSound("boop");
		}
	};
	
	this.select = function(){
		switch(this.selectedY){
			case this.limit://Back
				animates = [];
				animates["menu"] = new Menu(campaign_maps[message.level-1]);
				animates["menu"].level = "campaign";
				playSound("boop");
				break;
			case this.limit+1://GO
				if(selectedUnits.length == this.limit){
					animates = [];
					animates["map"] = new Map(campaign_maps[message.level-1]);
					playSound("boop");
				}
				break;
			default:
				playSound("boop");
				break;
		}
	};
	
}

function Unit(id, user, name, type){
	this.id = id;
	this.user = user;
	this.name = name;
	this.type = type;
	this.x = x;
	this.y = y;
	
	this.animate = function(progress){
	
	
	};
}

function Timer () {
	this.elapsed = 0;
	this.last = null;
	this.tick = function (now) {
		this.elapsed = (now - (this.last || now)) / 1000;
		this.last = now;
	}
	this.fps = function () {
		return Math.round(1 / this.elapsed);
	}
}

function mapMaker() {
	$('#tiles').click(function(e) {
		var offset = $(this).offset();
		mapMakerTile = Math.floor((e.pageY - offset.top) / 16) * 10 + Math.floor((e.pageX - offset.left) / 16);
	});
	$('#setWH').click(function(e) {
		animates["map"].width = $("#width").val();
		animates["map"].height = $("#height").val();
		animates["map"].generateMap();
	});
	$('#export').click(function(e) {
		$("#importData").val(JSON.stringify(animates["map"].data));
	});
	$('#toggleLayer').click(function(e) {
		mapMakerLayer = (mapMakerLayer + 1) % 2;
	});
	$('#blankTile').click(function(e) {
		mapMakerTile = -1;
	});
	$('#import').click(function(e) {
		animates['map'] = new Map(JSON.parse($("#importData").val()));
		animates['map'].mode = "map_maker";
	});
}

function playSound(sound, volume){
	if(typeof volume == 'undefined')
		switch(sound){
			case "boop":
				volume = options["boop"] ? .5 : 0;
				break;
			default:
				volume = 1;
				break;
		}
	var sound = sounds[sound].cloneNode()
	sound.volume = volume;
	sound.play();
}