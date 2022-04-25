<?php

//declare(strict_types=1);

/**
 * 获取随机长度、随机内容的字符串
 */
function FunGetRandStr()
{
    $randStr = '12fsd3wsfdefds4567890dfqwerdwtsyusfdiodsfpasddfgw3erhjklzr3dsxcvsfdsdrfvbnm';
    return substr(str_shuffle($randStr), 0, rand(6, 8));
}

function FunGetRandStrL($length)
{
    $randStr = '12fsd3wsfdefds4567890dfqwerdwtsyusfdiodsfpasddfgw3erhjklzr3dsxcvsfdsdrfvbnm';
    return substr(str_shuffle($randStr), 0, $length);
}
