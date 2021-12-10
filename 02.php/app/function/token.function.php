<?php

//生成token
function CreateToken($userid)
{
    $time = time();
    $end_time = time() + 86400;
    $info = $userid . '.' . $time . '.' . $end_time; //设置token过期时间为一天
    //根据以上信息信息生成签名（密钥为 siasqr)
    $signature = hash_hmac('md5', $info, SIGNATURE);
    //最后将这两部分拼接起来，得到最终的Token字符串
    return $token = $info . '.' . $signature;
}

//校验token
function CheckToken($token)
{
    if (!isset($token) || empty($token)) {
        $data['code'] = '400';
        $data['message'] = '非法请求';
        return $data;
    }
    //对比token
    $explode = explode('.', $token); //以.分割token为数组
    if (!empty($explode[0]) && !empty($explode[1]) && !empty($explode[2]) && !empty($explode[3])) {
        $info = $explode[0] . '.' . $explode[1] . '.' . $explode[2]; //信息部分
        $true_signature = hash_hmac('md5', $info, SIGNATURE); //正确的签名
        if (time() > $explode[2]) {
            $data['code'] = '401';
            $data['message'] = 'token已过期,请重新登录';
            return $data;
        }
        if ($true_signature == $explode[3]) {
            $data['code'] = '200';
            $data['message'] = 'token合法';
            return $data;
        } else {
            $data['code'] = '400';
            $data['message'] = 'token不合法';
            return $data;
        }
    } else {
        $data['code'] = '400';
        $data['message'] = 'token不合法';
        return $data;
    }
}
