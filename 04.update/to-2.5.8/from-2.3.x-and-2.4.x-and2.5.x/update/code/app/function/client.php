<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 获取用户IP地址
 */
function funGetCip()
{
    $cip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $cip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $cip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $cip = $_SERVER['REMOTE_ADDR'];
    }

    // 如果存在逗号分隔的多个IP地址，取第一个非空IP地址作为最终结果
    $ipList = explode(',', $cip);
    foreach ($ipList as $ip) {
        $ip = trim($ip);
        if (!empty($ip)) {
            $cip = $ip;
            break;
        }
    }

    // 使用过滤函数过滤IP地址，确保格式正确
    $cip = filter_var($cip, FILTER_VALIDATE_IP);

    // 如果过滤后的IP地址为空，则设置为unknown
    if (empty($cip)) {
        $cip = 'unknown';
    }

    return $cip;
}
