<?php

declare(strict_types=1);

/**
 * 对数据每项键值进行trim操作 
 */
function FunArrayValueTrim(&$value, $key)
{
    $value = trim($value);
}

/**
 * 去除数组中的空字符串
 */
function FunArrayValueFilter($value)
{
    return trim($value) != '';
}

/**
 * 获取数组中每个键值开始的空格数
 */
function FunArrayGetStrSpaceCount($string)
{
    for ($i = 0; $i < strlen($string); $i++) {
        if ($string[$i] != ' ') {
            break;
        }
    }
    return $i;
}

/**
 * 根据数组中每个键值结尾是否包含 / 判断键值是否为目录或文件
 */
function FunArrayIsStrFolder($string)
{
    if (substr($string, strlen($string) - 1) == '/') {
        return '1';
    } else {
        return '0';
    }
}

/**
 * 对数据每项键值进行去除 #disabled# 操作
 */
function FunArrayValueEnabled(&$value, $key)
{
    if (substr($value, 0, strlen(REG_SVN_USER_DISABLED)) == REG_SVN_USER_DISABLED) {
        $value = substr($value, strlen(REG_SVN_USER_DISABLED));
    }
}
