<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 16:58:52
 * @Description: QQ:1801168257
 */

function FunMessageExit($code = 200, $status = 1, $message = '成功', $data = [])
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
