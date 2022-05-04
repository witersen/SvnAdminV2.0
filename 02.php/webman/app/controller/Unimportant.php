<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 19:25:42
 * @Description: QQ:1801168257
 */

namespace app\controller;

use ServiceVerifycode;
use support\Request;

/**
 * 登录注销等公共类
 */
class Unimportant extends Core
{
    /**
     * 登录
     */
    public function Login(Request $request)
    {
        $codeResult = $this->database->get('verification_code', [
            'end_time'
        ], [
            'uuid' => $this->payload['uuid'],
            'code' => $this->payload['code'],
        ]);
        if ($codeResult == null) {
            return message(200, 0, '验证码错误', $codeResult);
        }
        if ($codeResult['end_time'] < time()) {
            return message(200, 0, '验证码过期');
        }

        $checkResult = FunCheckForm($this->payload, [
            'user_name' => ['type' => 'string', 'notNull' => true],
            'user_pass' => ['type' => 'string', 'notNull' => true],
            'user_role' => ['type' => 'string', 'notNull' => true],
        ]);
        if (!$checkResult) {
            return message(200, 0, '参数不完整');
        }

        if ($this->payload['user_role'] == 1) {
            $result = $this->database->get('admin_users', [
                'admin_user_id',
                'admin_user_name',
                'admin_user_phone',
                'admin_user_email'
            ], [
                'admin_user_name' => $this->payload['user_name'],
                'admin_user_password' => $this->payload['user_pass']
            ]);
            if ($result == null) {
                return message(200, 0, '账号密码错误');
            }
        } else if ($this->payload['user_role'] == 2) {
            //进行用户数据同步
            $syncResult = parent::SyncUserToDb();
            if ($syncResult['status'] != 1) {
                return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
            }

            $result = $this->database->get('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status'
            ], [
                'svn_user_name' => $this->payload['user_name'],
                'svn_user_pass' => $this->payload['user_pass']
            ]);
            if ($result == null) {
                return message(200, 0, '登陆失败');
            }
            if ($result['svn_user_status'] == 0) {
                return message(200, 0, '用户已过期');
            }
        }

        parent::InsertLog(
            '用户登录',
            '登陆成功 '
                . '账号：' . $this->payload['user_name'] . ' '
                . 'IP地址：' . $request->getRealIp(true),
            $this->payload['user_name']
        );

        return message(200, 1, '登陆成功', [
            'token' => parent::CreateToken($this->payload['user_role'], $this->payload['user_name']),
            'user_name' => $this->payload['user_name'],
            'user_role_name' => $this->payload['user_role'] == 1 ? '管理人员' : 'SVN用户',
            'user_role_id' => $this->payload['user_role']
        ]);
    }

    /**
     * 注销
     * 
     * 注销操作为将用户尚未过期的token加入所谓黑名单
     * 每次注销触发主动扫描黑名单 将名单中过期的token删除
     * 目的：实现用户注销后尚未过期的token无法继续使用
     */
    public function Logout(Request $request)
    {
        //清理过期token
        $this->CleanBlack();

        //加入本token
        $this->AddBlack();

        parent::InsertLog(
            '用户注销',
            '账号：' . $this->userName . 'IP地址：' . $request->getRealIp(true),
            $this->userName
        );

        //退出
        return message(200, 1, '退出登录成功');
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
    public function GetVeryfyCode(Request $request)
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

        $varification = new ServiceVerifycode(134, 32, $code);

        $imageString = $varification->CreateVerifacationImage();

        //返回图片的base64编码
        return message(200, 1, 'success', [
            'uuid' => $uuid,
            'base64' => $imageString
        ]);
    }

    /**
     * 将token加入黑名单
     *
     * @return void
     */
    private function AddBlack()
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
    private function CleanBlack()
    {
        $this->database->delete('black_token', [
            'end_time[<]' => time()
        ]);
    }
}
