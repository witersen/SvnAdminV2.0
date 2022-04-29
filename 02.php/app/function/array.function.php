<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 23:57:53
 * @Description: QQ:1801168257
 */

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
    $REG_SVN_USER_DISABLED = '#disabled#';
    
    if (substr($value, 0, strlen($REG_SVN_USER_DISABLED)) == $REG_SVN_USER_DISABLED) {
        $value = substr($value, strlen($REG_SVN_USER_DISABLED));
    }
}

/**
 * 检查表单的参数
 */
function FunCheckForm($checkedArray, $columns)
{
    //检查数组本身是否合法
    if (empty($checkedArray) || !isset($checkedArray)) {
        FunMessageExit(200, 0, '参数不完整');
    }
    foreach ($columns as $key => $value) {
        //检查数组中是否包含指定的变量
        if (!array_key_exists($key, $checkedArray)) {
            FunMessageExit(200, 0, '参数不完整' . $key);
        }
        //检查变量类型是否正确
        if ($value['type'] != gettype($checkedArray[$key])) {
            FunMessageExit(200, 0, '参数不完整' . $key);
        }
        //检查是否可以为空
        if ($value['notNull']) {
            if (empty($checkedArray[$key])) {
                FunMessageExit(200, 0, '参数不完整' . $key);
            }
        }
    }
}


/**
 * 由于array_column到php5.5+才支持
 * 为了兼容php5.4
 * 这里选择手动实现 可能性能不高
 */
function FunArrayColumn($array, $columnKey)
{
    $resultArray = [];
    foreach ($array as $key => $value) {
        if (!array_key_exists($columnKey, $value)) {
            return false;
        }
        array_push($resultArray, $value[$columnKey]);
    }
    return $resultArray;
}
