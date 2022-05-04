<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 00:18:26
 * @Description: QQ:1801168257
 */

function message($code = 200, $status = 1, $message = '成功', $data = [])
{
    return json([
        'code' => $code,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
}
