<?php

/**
 * 登录注销等公共类
 */
class common extends controller
{
    private $Svnuser;
    private $Blacktoken;
    private $Logs;


    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Svnuser = new svnuser();
        $this->Blacktoken = new blacktoken();
        $this->Logs = new logs();
    }

    /**
     * 登录
     */
    function Login()
    {
        $codeResult = $this->database->get('verification_code', [
            'end_time'
        ], [
            'uuid' => $this->requestPayload['uuid'],
            'code' => $this->requestPayload['code'],
        ]);
        if ($codeResult == null) {
            FunMessageExit(200, 0, '验证码错误', $codeResult);
        }
        if ($codeResult['end_time'] < time()) {
            FunMessageExit(200, 0, '验证码过期');
        }

        FunCheckForm($this->requestPayload, [
            'user_name' => ['type' => 'string', 'notNull' => true],
            'user_pass' => ['type' => 'string', 'notNull' => true],
            'user_role' => ['type' => 'string', 'notNull' => true],
        ]);

        if ($this->requestPayload['user_role'] == 1) {
            $result = $this->database->get('admin_users', [
                'admin_user_id',
                'admin_user_name',
                'admin_user_phone',
                'admin_user_email'
            ], [
                'admin_user_name' => $this->requestPayload['user_name'],
                'admin_user_password' => $this->requestPayload['user_pass']
            ]);
            if ($result == null) {
                FunMessageExit(200, 0, '账号密码错误');
            }
        } else if ($this->requestPayload['user_role'] == 2) {
            //进行用户数据同步
            $this->Svnuser->SyncUserToDb();

            $result = $this->database->get('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status'
            ], [
                'svn_user_name' => $this->requestPayload['user_name'],
                'svn_user_pass' => $this->requestPayload['user_pass']
            ]);
            if ($result == null) {
                FunMessageExit(200, 0, '登陆失败');
            }
            if ($result['svn_user_status'] == 0) {
                FunMessageExit(200, 0, '用户已过期');
            }
        }

        $this->Logs->InsertLog(
            '用户登录',
            '登陆成功 '
                . '账号：' . $this->requestPayload['user_name'] . ' '
                . 'IP地址：' . $_SERVER["REMOTE_ADDR"],
            $this->requestPayload['user_name']
        );

        FunMessageExit(200, 1, '登陆成功', [
            'token' => FunCreateToken($this->requestPayload['user_role'], $this->requestPayload['user_name']),
            'user_name' => $this->requestPayload['user_name'],
            'user_role_name' => $this->requestPayload['user_role'] == 1 ? '管理人员' : 'SVN用户',
            'user_role_id' => $this->requestPayload['user_role']
        ]);
    }

    /**
     * 注销
     * 
     * 注销操作为将用户尚未过期的token加入所谓黑名单
     * 每次注销触发主动扫描黑名单 将名单中过期的token删除
     * 目的：实现用户注销后尚未过期的token无法继续使用
     */
    function Logout()
    {
        //清理过期token
        $this->Blacktoken->CleanBlack();

        //加入本token
        $this->Blacktoken->BlackToken();

        $this->Logs->InsertLog(
            '用户注销',
            '账号：' . $this->globalUserName . 'IP地址：' . $_SERVER["REMOTE_ADDR"],
            $this->globalUserName
        );

        //退出
        FunMessageExit();
    }

    /**
     * 清除过期验证码
     */
    private function Clean()
    {
        $this->database->delete('verification_code', [
            'end_time[<]' => time()
        ]);
    }

    /**
     * 获取验证码
     */
    function GetVeryfyCode()
    {
        //清除过期验证码
        $this->Clean();

        //生成验证码
        $code = FunGetRandStrL(4);

        //生成唯一标识
        $uuid = time() . FunGetRandStr() . FunGetRandStr();

        //
        $prefix = time();

        //生效时间
        $startTime = $prefix;

        //有效时间为60s
        $endTime = $prefix + 60;

        //写入数据库
        $this->database->insert('verification_code', [
            'uuid' => $uuid,
            'code' => $code,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'insert_time' => date('Y-m-d H:i:s')
        ]);

        require_once BASE_PATH . '/extension/VerifyCode/VerifyCode.class.php';

        $varification = new verification(134, 32, $code, BASE_PATH . '/data/test.png');

        $imageString = $varification->CreateVerifacationImage();

        //返回图片的base64编码
        FunMessageExit(200, 1, 'success', [
            'uuid' => $uuid,
            'base64' => $imageString
        ]);
    }
}
