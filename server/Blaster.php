<?php
//FINALLY, a consistent crashing method!
for($i = 0; $i < 600; $i ++) {
    $connection[$i] = fsockopen('localhost', 80);
    fwrite($connection[$i], rand(1, 10000));
}