<?php

class blacktoken extends controller
{
    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    /**
     * 将token加入黑名单
     */
    function BlackToken()
    {
        $arr = explode('.', $this->token);
        $this->database->insert('black_token', [
            'token' => $this->token,
            'start_time' => $arr[2],
            'end_time' => $arr[3],
            'insert_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 扫描黑名单中的token 发现过期的则删除
     * 
     * 目的：不给搜索增加压力
     */
    function CleanBlack()
    {
        $this->database->delete('black_token', [
            'end_time[<]' => time()
        ]);
    }

    /**
     * 检查token是否存在于黑名单
     */
    function CheckBlack()
    {
        $result = $this->database->get('black_token', [
            'token_id'
        ], [
            'token' => $this->token
        ]);
        if ($result != null) {
            FunMessageExit(401, 0, 'token已注销');
        }
    }
}
