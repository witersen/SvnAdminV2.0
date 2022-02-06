<?php

declare(strict_types=1);

/**
 * 获取随机长度、随机内容的字符串
 */
function FunGetRandStr()
{
    $randStr = 'ABCDEwitersenFGHIJKLMNOPQRSTwitersenUVWXYZwitersenabcdefghijklmnopqwitersenrstuvwxyz1234552167890';
    return substr(str_shuffle($randStr), 0, rand(2, 5));
}