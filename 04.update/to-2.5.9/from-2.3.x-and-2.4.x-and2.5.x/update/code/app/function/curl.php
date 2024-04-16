<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 通用请求
 */
function funCurlRequest($url)
{
    //初始化
    $curl = curl_init();

    //设置请求url
    curl_setopt($curl, CURLOPT_URL, $url);

    //设置true会将头文件的信息作为数据流输出 否则作为字符串输出
    curl_setopt($curl, CURLOPT_HEADER, false);

    //设置true会不输出body部分 此时请求类型被转变为head请求
    curl_setopt($curl, CURLOPT_NOBODY, false);

    //设置true会将curl_exec()获取的信息以字符串返回 否则会直接输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    //设置true会在页面发生301或者302时自动进行跳转抓取
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    //将请求类型改为get 如果为探测状态 可避免因为请求类型为head造成的探测失误
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");

    //设置请求超时时间
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    //设置false将不检查证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    //设置false将不检查证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    //执行
    $result = curl_exec($curl);

    //关闭
    curl_close($curl);

    return $result;
}
