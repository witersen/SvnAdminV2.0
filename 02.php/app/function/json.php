<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

function funCheckJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}
