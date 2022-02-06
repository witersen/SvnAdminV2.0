<?php

declare(strict_types=1);

/**
 * 更新配置文件的value项
 */
function FunUpdateConfigValue($strContent, $key, $value)
{
    $status = preg_match(sprintf(REG_CONFIG, $key), $strContent, $result);
    if ($status == 0) {
        return false;
    } else {
        return str_replace($result[0], "define('$key', '$value')", $strContent);
    }
}

/**
 * 获取配置文件的value项
 */

function FunGetConfigValue($strContent, $key)
{
    $status = preg_match(sprintf(REG_CONFIG, $key), $strContent, $result);
    if ($status == 0) {
        return false;
    } else {
        return $result[1];
    }
}
