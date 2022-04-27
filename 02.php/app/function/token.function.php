<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 15:07:53
 * @Description: QQ:1801168257
 */

//生成token
function FunCreateToken($userRoleId, $userName)
{
    $nowTime = time();
    $startTime = $nowTime;
    //配置登录凭证过期时间为6个小时
    $endTime = $nowTime + 60 * 60 * 6;
    $part1 = $userRoleId . '.' . $userName . '.' . $startTime . '.' . $endTime;
    $part2 = hash_hmac('md5', $part1, SIGNATURE);
    return $part1 . '.' . $part2;
}

//校验token
function FunCheckToken($token)
{
    //判断是否为空
    if (!isset($token) || empty($token)) {
        FunMessageExit(401, 0, '非法请求');
    }

    //校验token格式
    if (substr_count($token, '.') != 4) {
        FunMessageExit(401, 0, '非法请求');
    }

    $arr = explode('.', $token);

    //校验token格式
    foreach ($arr as $value) {
        if (trim($value) == '') {
            FunMessageExit(401, 0, '非法请求');
        }
    }

    //检验token内容
    $part1 =  hash_hmac('md5', $arr[0] . '.' . $arr[1] . '.' . $arr[2] . '.' . $arr[3], SIGNATURE);
    $part2 = $arr[4];
    if ($part1 != $part2) {
        FunMessageExit(401, 0, '非法请求');
    }

    //校验是否过期
    if (time() > $arr[3]) {
        FunMessageExit(401, 0, '登录过期');
    }
}
