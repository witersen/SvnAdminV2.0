<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 16:59:08
 * @Description: QQ:1801168257
 */

/**
 * 获取随机长度、随机内容的字符串
 */
function funGetRandStr()
{
    $randStr = '12fsd3wsfdefds4567890dfqwerdwtsyusfdiodsfpasddfgw3erhjklzr3dsxcvsfdsdrfvbnm';
    return substr(str_shuffle($randStr), 0, rand(6, 8));
}

function funGetRandStrL($length)
{
    $randStr = '12fsd3wsfdefds4567890dfqwerdwtsyusfdiodsfpasddfgw3erhjklzr3dsxcvsfdsdrfvbnm';
    return substr(str_shuffle($randStr), 0, $length);
}
