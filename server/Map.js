function Map(mapData){
	if(typeof mapData !== 'undefined'){
		this.data = mapData;
		this.width = mapData.length;
		this.height = mapData[0].length;
	} else {
		this.data = [];
		this.width = 32;
		this.height = 32;
	}
	
	this.tileSize = 24;
	
	this.mode = "";
	
	this.tilesWide = Math.floor(canvas.width/this.tileSize);
	this.tilesTall = Math.floor(canvas.height/this.tileSize)
	
	this.selectedX = Math.floor(this.width/2);
	this.selectedY = Math.floor(this.height/2);
	
	this.cameraX = Math.floor(this.width/2) - this.tilesWide/2;
	this.cameraY = Math.floor(this.height/2) - this.tilesTall/2;
	
	$(canvas).css("backgroundColor", "black");
	
	$(document).off();
	
	$(document).keydown(function(e){
		switch(e.keyCode){
			case 38://up
				animates["map"].moveSelectedUp();
				e.preventDefault();
				break;
			case 40://down
				animates["map"].moveSelectedDown();
				e.preventDefault();
				break;
			case 37://left
				animates["map"].moveSelectedLeft();
				e.preventDefault();
				break;
			case 39://right
				animates["map"].moveSelectedRight();
				e.preventDefault();
				break;
			case 13://enter
			case 32://space
				animates["map"].select();
				e.preventDefault();
				break;
		}
	});
	
	this.animate = function(progress){
		var percent = (progress % 3000) / 3000;
		
		//Calculate start and stop points to only loop through visible tiles
		var xStart = this.cameraX < 0 ? 0 : this.cameraX;
		var yStart = this.cameraY < 0 ? 0 : this.cameraY;
		
		var xStop = this.tilesWide + this.cameraX > this.width ? this.width : this.tilesWide + this.cameraX;
		var yStop = this.tilesTall + this.cameraY > this.height ? this.height : this.tilesTall + this.cameraY;
		
		for(var x = xStart; x < xStop; x++){
			for(var y = yStart; y < yStop; y++){
				if(this.data[x][y][0] != -1)
					ctx.drawImage(images["textures"], (this.data[x][y][0] % 10) * 16 , Math.floor(this.data[x][y][0]/10) * 16, 16, 16, (x-this.cameraX)*this.tileSize, (y-this.cameraY)*this.tileSize, this.tileSize, this.tileSize);
				
				if(this.data[x][y][1] != -1)
					ctx.drawImage(images["textures"], (this.data[x][y][1] % 10) * 16 , Math.floor(this.data[x][y][1]/10) * 16, 16, 16, (x-this.cameraX)*this.tileSize, (y-this.cameraY)*this.tileSize, this.tileSize, this.tileSize);
			}
		}
		ctx.beginPath();
		ctx.strokeStyle = "rgba(0,0,0,.4)";
		for(var x = 0; x < this.tilesWide; x++){
			ctx.moveTo(0, x*this.tileSize);
			ctx.lineTo(canvas.width, x*this.tileSize);
		}
		for(var y = 0; y < this.tilesTall; y++){
			ctx.moveTo(y*this.tileSize, 0);
			ctx.lineTo(y*this.tileSize, canvas.height);
		}
		ctx.stroke();
		ctx.beginPath();
		ctx.strokeStyle = "rgba(255,255,255,1)";
		ctx.rect((this.selectedX-this.cameraX)*this.tileSize-1, (this.selectedY-this.cameraY)*this.tileSize-1, this.tileSize+2, this.tileSize+2);
		ctx.stroke();
	};
	
	this.moveSelectedLeft = function(){
		if(this.selectedX > 0){
			this.selectedX--;
			playSound("boop");
			if(this.selectedX-this.cameraX < this.tilesWide*.25 && this.cameraX > -2)
				this.cameraX--;
		}
	};
	
	this.moveSelectedUp = function(){
		if(this.selectedY > 0){
			this.selectedY--;
			playSound("boop");
			if(this.selectedY-this.cameraY < this.tilesTall*.25 && this.cameraY > -2)
				this.cameraY--;
		}
	};
	
	this.moveSelectedDown = function(){
		if(this.selectedY < this.height-1){
			this.selectedY++;
			playSound("boop");
			if(this.selectedY-this.cameraY > this.tilesTall*.75 && this.cameraY < this.height-this.tilesTall+2)
				this.cameraY++;
		}
	};
	
	this.moveSelectedRight = function(){
		if(this.selectedX < this.width-1){
			this.selectedX++;
			playSound("boop");
			if(this.selectedX-this.cameraX > this.tilesWide*.75 && this.cameraX < this.width-this.tilesWide+2)
				this.cameraX++;
		}
	};
	
	this.select = function(){
		switch(this.mode){
			case "map_maker":
				this.data[this.selectedX][this.selectedY][mapMakerLayer] = mapMakerTile;
				break;
		}
	};
	
	this.generateMap = function(){
		this.data = [];
		for(var x = 0; x < this.width; x++){
			this.data[x] = [];
			for(var y = 0; y < this.height; y++)
				this.data[x][y] = [50, -1];
		}
	};
}