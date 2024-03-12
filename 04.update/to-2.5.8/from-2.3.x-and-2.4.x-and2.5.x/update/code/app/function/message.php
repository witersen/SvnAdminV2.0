<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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

function message2($message = ['code' => 200, 'status' => 1, 'message' => '成功', 'data' => []])
{
    return [
        'code' => $message['code'],
        'status' => $message['status'],
        'message' => $message['message'],
        'data' => $message['data']
    ];
}

function json1($code = 200, $status = 1, $message = '成功', $data = [])
{
    header('Content-Type:application/json; charset=utf-8');
    // ob_end_clean();
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
    // ob_end_clean();
    // http_response_code($code);
    exit(json_encode([
        'code' => $message['code'],
        'status' => $message['status'],
        'message' => $message['message'],
        'data' => $message['data']
    ]));
}
