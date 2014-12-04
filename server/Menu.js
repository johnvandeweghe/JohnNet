function Menu(){

	this.topData = ["Campaign (Single Player)", "PVP", "Options", "Map Maker", "Credits"];
	this.campaignData = ["Back", "Level 1", "More coming soon"];
	this.optionsData = ["Back", "Toggle \"boop\": ", "Music: "];
	this.creditsData = ["Back"];
	
	this.credits = ["Tiles by Eiyeron & Jerom",
					"\"The Snow Queen\" Kevin MacLeod (incompetech.com)",
					"All game code done by Alex Lunix"];
	
	this.selectedY = 0;
	
	this.level = "top";
	
	$(canvas).css("backgroundColor", "white");
	
	if (typeof sounds["The_Snow_Queen"].loop == 'boolean'){
		sounds["The_Snow_Queen"].loop = true;
	} else {
		sounds["The_Snow_Queen"].addEventListener('ended', function() {
			this.currentTime = 0;
			this.play();
		}, false);
	}
	sounds["The_Snow_Queen"].volume = options["music"];
	sounds["The_Snow_Queen"].play();
	
	$(document).off();
	
	$(document).keydown(function(e){
		switch(e.keyCode){
			case 38://up
				animates["menu"].moveSelectedUp();
				e.preventDefault();
				break;
			case 40://down
				animates["menu"].moveSelectedDown();
				e.preventDefault();
				break;
			case 32://space
			case 13://enter
				animates["menu"].select();
				e.preventDefault();
				break;
		}
	});
	
	
	this.animate = function(progress){
		ctx.font = "30px sans-serif";
		ctx.textAlign = 'center';
		
		switch(this.level){
			case "top":
				$.each(this.topData, function(i, v){
					if(animates["menu"].selectedY == i){
						ctx.fillStyle = "rgb(200,200,200)";
					} else {
						ctx.fillStyle = "rgb(100,100,100)";
					}
					ctx.fillRect(8, 20 + 60 * i, canvas.width - 16, 40);
					
					ctx.fillStyle = "rgb(0,0,0)";
					ctx.fillText(v, canvas.width/2, 50 + 60 * i);
				});
				break;
			case "campaign":
				$.each(this.campaignData, function(i, v){
					if(i <= campaignLevel){
						if(animates["menu"].selectedY == i){
							ctx.fillStyle = "rgb(200,200,200)";
						} else {
							ctx.fillStyle = "rgb(100,100,100)";
						}
						ctx.fillRect(8, 20 + 60 * i, canvas.width - 16, 40);
						
						ctx.fillStyle = "rgb(0,0,0)";
						ctx.fillText(v, canvas.width/2, 50 + 60 * i);
					}
				});
				break;
			case "options":
				$.each(this.optionsData, function(i, v){
					switch(i){
						case 1:
							v += options["boop"] ? "On" : "Off";
							break;
						case 2:
							v += (options["music"] * 100) + "%";
							break;
					}
					if(animates["menu"].selectedY == i){
						ctx.fillStyle = "rgb(200,200,200)";
					} else {
						ctx.fillStyle = "rgb(100,100,100)";
					}
					ctx.fillRect(8, 20 + 60 * i, canvas.width - 16, 40);
					
					ctx.fillStyle = "rgb(0,0,0)";
					ctx.fillText(v, canvas.width/2, 50 + 60 * i);
				});
				break;
			case "credits":
				$.each(this.creditsData, function(i, v){
					if(animates["menu"].selectedY == i){
						ctx.fillStyle = "rgb(200,200,200)";
					} else {
						ctx.fillStyle = "rgb(100,100,100)";
					}
					ctx.fillRect(8, 20 + 60 * i, canvas.width - 16, 40);
					
					ctx.fillStyle = "rgb(0,0,0)";
					ctx.fillText(v, canvas.width/2, 50 + 60 * i);
				});
				ctx.font = "20px sans-serif";
				$.each(this.credits, function(i, v){
					ctx.fillStyle = "rgb(0,0,0)";
					ctx.fillText(v, canvas.width/2, 50 + 50 * (i+1));
				});
				break;
		}
		
		ctx.fillStyle = "rgb(0,0,0)";
		ctx.textAlign = 'right';
		ctx.fillText(username, canvas.width-10, canvas.height - 30);
	}
	
	this.moveSelectedUp = function(){
		if(this.selectedY > 0){
			this.selectedY --;
			playSound("boop");
		}
	};
	
	this.moveSelectedDown = function(){
		switch(this.level){
			case "top":
				if(this.selectedY < this.topData.length-1){
					this.selectedY ++;
					playSound("boop");
				}
				break;
			case "options":
				if(this.selectedY < this.optionsData.length-1){
					this.selectedY ++;
					playSound("boop");
				}
				break;
			case "campaign":
				if(this.selectedY < campaignLevel){
					this.selectedY ++;
					playSound("boop");
				}
				break;
			case "credits":
				if(this.selectedY < this.creditsData.length-1){
					this.selectedY ++;
					playSound("boop");
				}
				break;
		}
	};
	
	this.select = function(){
		switch(this.level){
			case "top":
				switch(this.selectedY){
					case 0:
						this.level = "campaign";
						playSound("boop");
						break;
					case 2:
						this.level = "options";
						playSound("boop");
						break;
					case 3:
						playSound("boop");
						animates = [];
						animates["map"] = new Map();
						animates["map"].generateMap();
						animates["map"].mode = "map_maker";
						$(canvas).parent().append("<span id='mapMaker'><img id='tiles' src='color_tileset_16x16_Jerom&Eiyeron_CC-BY-SA-3.0_8.png' /><input type='button' id='toggleLayer' value='Toggle Layer' /><input type='button' id='blankTile' value='Set Tile to blank' /><br />Width<input type='text' id='width' value='32' /> Height<input type='text' id='height' value='32' /><input type='button' id='setWH' value='Set'/><br /><input type='button' id='export' value='Export'/><input type='button' id='import' value='Import' /><input type='text' id='importData' /></span>");
						mapMaker();
						sounds["The_Snow_Queen"].pause();
						sounds["The_Snow_Queen"].currentTime = 0;
						break;
					case 4:
						this.level = "credits";
						this.selectedY = 0;
						playSound("boop");
						break;
				}
				break;
			case "options":
				switch(this.selectedY){
					case 0:
						this.level = "top";
						playSound("boop");
						break;
					case 1:
						options["boop"] = !options["boop"];
						playSound("boop");
						break;
					case 2:
						options["music"] = Math.round((options["music"] + .1)*10)/10 % 1.1;
						sounds["The_Snow_Queen"].volume = options["music"];
						playSound("boop");
						break;
				}
				break;
			case "campaign":
				switch(this.selectedY){
					case 0:
						this.level = "top";
						playSound("boop");
						break;
					case 1:
						playSound("boop");
						animates = [];
						sounds["The_Snow_Queen"].pause();
						sounds["The_Snow_Queen"].currentTime = 0;
						animates["loading"] = new Loading();
						animates["loading"].status = "Requesting Campaign data from server";
						socket.send(JSON.stringify({type: "start_campaign", level: 1}));
						break;
				}
				break;
			case "credits":
				switch(this.selectedY){
					case 0:
						this.level = "top";
						playSound("boop");
						break;
				}
				break;
		}
	};
}