<?php

function buildMobile()
{
    $my_array = array("6", "7", "8", "9");
    $length = count($my_array) - 1;
    $hd = rand(0, $length);
    $begin = $my_array[$hd];
    $a = rand(10, 99);
    $b = rand(100, 999);
    return $begin . $a . '****' . $b;
}
