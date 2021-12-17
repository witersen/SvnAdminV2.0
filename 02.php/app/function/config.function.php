<?php

function UpdateConfigValue($strContent, $key, $value)
{
    $status = preg_match("/define\(\"*'*$key'*\"*\s*,\s*'*(.*?)'*\)/", $strContent, $result);
    if ($status == 0) {
        return false;
    } else {
        return str_replace($result[0], "define('$key', '$value')", $strContent);
    }
}

function GetConfigValue($strContent, $key)
{
    $status = preg_match("/define\(\"*'*$key'*\"*\s*,\s*'*(.*?)'*\)/", $strContent, $result);
    if ($status == 0) {
        return false;
    } else {
        return $result[1];
    }
}
