<?php
/*
 * @Author: witersen
 * @Date: 2022-05-03 02:20:27
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 17:02:03
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Index extends Core
{
    public function index(Request $request)
    {
        return message(200, 0, '无效的请求方法');
    }

    public function token1(Request $request)
    {
        sleep(10);
        return message(200, 1, 'token1', [
            'token' => $this->token,
            'userName'=>$this->userName,
            'userRoleId'=>$this->userRoleId,
        ]);
    }

    public function token2(Request $request)
    {
        return message(200, 1, 'token2', [
            'token' => $this->token,
            'userName'=>$this->userName,
            'userRoleId'=>$this->userRoleId,
            'payload'=>$this->payload
        ]);
    }
}
