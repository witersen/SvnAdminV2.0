<?php

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
