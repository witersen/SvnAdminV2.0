<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 16:58:46
 * @Description: QQ:1801168257
 */

function FunCheckJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}
