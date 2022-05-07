<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 19:38:16
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 13:37:21
 * @Description: QQ:1801168257
 */

function message($code = 200, $status = 1, $message = '成功', $data = [])
{
    return [
        'code' => $code,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
}

function json1($code = 200, $status = 1, $message = '成功', $data = [])
{
    header('Content-Type:application/json; charset=utf-8');
    // http_response_code($code);
    exit(json_encode([
        'code' => $code,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]));
}

function json2($message = ['code' => 200, 'status' => 1, 'message' => '成功', 'data' => []])
{
    header('Content-Type:application/json; charset=utf-8');
    // http_response_code($code);
    exit(json_encode([
        'code' => $message['code'],
        'status' => $message['status'],
        'message' => $message['message'],
        'data' => $message['data']
    ]));
}
