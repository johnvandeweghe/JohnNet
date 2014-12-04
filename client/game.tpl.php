<?php
if (!defined('INCLUDED')) {
    header('HTTP/1.1 404 Not Found', 404);
	exit;
}

require_once(TEMPLATE . "header.tpl.php");
?>
<style type="text/css">
#Game {
	image-rendering: -webkit-optimize-contrast; /* webkit */
	image-rendering: -moz-crisp-edges; /* Firefox */
	background-color: black;
}
#tiles {
	image-rendering: -webkit-optimize-contrast; /* webkit */
	image-rendering: -moz-crisp-edges; /* Firefox */
}
#mapMaker {
	display: inline-block;
	width: 160px;
}
</style>
<h2>WebSockets</h2>
<h3>Game</h3>

<div style="text-align: center">
<canvas id="Game" style="border-style: solid" width="576px" height="576px">
</canvas>
</div>

<script type="text/javascript" src="Menu.js"></script>
<script type="text/javascript" src="Map.js"></script>
<script type="text/javascript" src="game.js"></script>
<?php
require_once(TEMPLATE . "footer.tpl.php");