<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-12 00:01:12
 * @Description: QQ:1801168257
 */

namespace app\service;

use Config;
use Verifycode;

class Common extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Svnuser;
    private $Logs;
    private $Mail;
    private $Safe;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->Svnuser = new Svnuser();
        $this->Logs = new Logs();
        $this->Mail = new Mail();
        $this->Safe = new Safe();
    }

    /**
     * 登录
     */
    public function Login()
    {
        //清理过期token
        $this->CleanBlack();

        $verifyOptionResult = $this->Safe->GetVerifyOption();

        if ($verifyOptionResult['status'] != 1) {
            return message(200, 0, $verifyOptionResult['message']);
        }

        $verifyOption = $verifyOptionResult['data'];

        if ($verifyOption['enable'] == true) {
            $codeResult = $this->database->get('verification_code', [
                'end_time'
            ], [
                'uuid' => $this->payload['uuid'],
                'code' => $this->payload['code'],
            ]);
            if ($codeResult == null) {
                //每个 uuid 仅可使用一次 防止爆破
                $this->database->update('verification_code', [
                    'end_time' => 0
                ], [
                    'uuid' => $this->payload['uuid']
                ]);
                return message(200, 0, '验证码错误', $codeResult);
            }
            if ($codeResult['end_time'] == 0) {
                return message(200, 0, '验证码仅可用一次');
            }
            if ($codeResult['end_time'] < time()) {
                return message(200, 0, '验证码过期');
            }
        }

        $checkResult = funCheckForm($this->payload, [
            'user_name' => ['type' => 'string', 'notNull' => true],
            'user_pass' => ['type' => 'string', 'notNull' => true],
            'user_role' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
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
            $syncResult = $this->Svnuser->SyncUserToDb();
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

        //日志
        $this->Logs->InsertLog(
            '用户登录',
            sprintf("账号:%s IP地址:%s", $this->payload['user_name'], $_SERVER["REMOTE_ADDR"]),
            $this->payload['user_name']
        );

        //邮件
        $this->Mail->SendMail('Common/Login', '用户登录成功通知', '账号：' . $this->payload['user_name'] . ' ' . 'IP地址：' . $_SERVER["REMOTE_ADDR"] . ' ' . '时间：' . date('Y-m-d H:i:s'));

        return message(200, 1, '登陆成功', [
            'token' => $this->CreateToken($this->payload['user_role'], $this->payload['user_name']),
            'user_name' => $this->payload['user_name'],
            'user_role_name' => $this->payload['user_role'] == 1 ? '管理人员' : 'SVN用户',
            'user_role_id' => $this->payload['user_role']
        ]);
    }

    /**
     * 生成token
     *
     * @param int $userRoleId
     * @param string $userName
     * @return string
     */
    private function CreateToken($userRoleId, $userName)
    {
        $nowTime = time();

        $startTime = $nowTime;

        //配置登录凭证过期时间为6个小时
        $endTime = $nowTime + 60 * 60 * 6;

        $part1 = $userRoleId . $this->config_sign['signSeparator'] . $userName . $this->config_sign['signSeparator'] . $startTime . $this->config_sign['signSeparator'] . $endTime;

        $part2 = hash_hmac('md5', $part1, $this->config_sign['signature']);

        return $part1 . $this->config_sign['signSeparator'] . $part2;
    }

    /**
     * 注销
     * 
     * 注销操作为将用户尚未过期的token加入所谓黑名单
     * 每次注销触发主动扫描黑名单 将名单中过期的token删除
     * 目的：实现用户注销后尚未过期的token无法继续使用
     */
    public function Logout()
    {
        //加入本token
        $this->AddBlack();

        //日志
        $this->Logs->InsertLog(
            '用户注销',
            sprintf("账号:%s IP地址:%s", $this->userName, $_SERVER["REMOTE_ADDR"]),
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
    public function GetVerifyCode()
    {
        //清除过期验证码
        $this->Clean();

        //生成验证码
        $code = funGetRandStrL(4);

        //生成唯一标识
        $uuid = time() . funGetRandStr() . funGetRandStr();

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

        //从数据库查询验证数据被正常写入
        $result = $this->database->get('verification_code', [
            'code_id'
        ], [
            'uuid' => $uuid
        ]);

        if ($result == null) {
            return message(200, 0, '无法写入数据库，如果为 SQLite，请为数据库文件及上级目录授权');
        }

        $varification = new Verifycode(134, 32, $code);

        $imageString = $varification->CreateVerifacationImage();

        //返回图片的base64编码
        return message(200, 1, 'success', [
            'uuid' => $uuid,
            'base64' => $imageString,
        ]);
    }

    /**
     * 将token加入黑名单
     *
     * @return void
     */
    private function AddBlack()
    {
        $arr = explode($this->config_sign['signSeparator'], $this->token);
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
