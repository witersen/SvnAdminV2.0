<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use Verifycode;
use app\service\Ldap as ServiceLdap;
use app\service\Apache as ServiceApache;

class Common extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Logs;
    private $Mail;
    private $Setting;
    private $ServiceLdap;
    private $ServiceApache;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->Logs = new Logs($parm);
        $this->Mail = new Mail($parm);
        $this->Setting = new Setting($parm);
        $this->ServiceLdap = new ServiceLdap($parm);
        $this->ServiceApache = new ServiceApache($parm);
    }

    /**
     * 登录
     */
    public function Login()
    {
        $checkResult = funCheckForm($this->payload, [
            'user_name' => ['type' => 'string', 'notNull' => true],
            'user_pass' => ['type' => 'string', 'notNull' => true],
            'user_role' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $userName = $this->payload['user_name'];
        $userPass = $this->payload['user_pass'];
        $userRole = $this->payload['user_role'];
        $userRoleName = $userRole == 1 ? '管理员' : ($userRole == 2 ? 'SVN用户' : ($userRole == 3 ? '子管理员' : '未知'));

        //清理过期token
        $this->CleanBlack();

        $verifyOptionResult = $this->Setting->GetVerifyOption();

        if ($verifyOptionResult['status'] != 1) {
            return message(200, 0, $verifyOptionResult['message']);
        }

        $verifyOption = $verifyOptionResult['data'];

        if ($verifyOption['enable'] == true) {
            $endTime = $this->database->get('verification_code', 'end_time', [
                'uuid' => $this->payload['uuid'],
                'code' => $this->payload['code'],
            ]);
            if (empty($endTime)) {
                //每个 uuid 仅可使用一次 防止爆破
                $this->database->update('verification_code', [
                    'end_time' => 0
                ], [
                    'uuid' => $this->payload['uuid']
                ]);
                return message(200, 0, '登录失败[验证码错误]', $endTime);
            }
            if ($endTime == 0) {
                return message(200, 0, '登陆失败[验证码失效]');
            }
            if ($endTime < time()) {
                return message(200, 0, '登陆失败[验证码过期]');
            }
        }

        $token = '';
        if ($userRole == 1) {
            $result = $this->database->get('admin_users', [
                'admin_user_id',
                'admin_user_name',
                'admin_user_phone',
                'admin_user_email'
            ], [
                'admin_user_name' => $userName,
                'admin_user_password' => $userPass
            ]);
            if (empty($result)) {
                return message(200, 0, '登录失败[账号或密码错误]');
            }

            //更新token
            $this->database->update('admin_users', [
                'admin_user_token' => $token = $this->CreateToken($userRole, $userName)
            ], [
                'admin_user_name' => $userName
            ]);
        } elseif ($userRole == 2) {
            if ($this->enableCheckout == 'svn') {
                $dataSource = $this->svnDataSource;
            } else {
                $dataSource = $this->httpDataSource;
            }

            if ($dataSource['user_source'] == 'ldap') {
                $result = $this->database->get('svn_users', 'svn_user_id', [
                    'svn_user_name' => $userName,
                ]);
                if (empty($result)) {
                    return message(200, 0, '登录失败[ldap账户未同步]');
                }

                if (!$this->ServiceLdap->LdapUserLogin($userName, $userPass)) {
                    return message(200, 0, '登录失败[ldap账户认证失败]');
                }

                $this->database->update('svn_users', [
                    'svn_user_pass' => $userPass
                ], [
                    'svn_user_name' => $userName
                ]);

                if (strstr($userName, '|')) {
                    return message(200, 0, '登录失败[ldap账户名不合法]');
                }
            } else {
                if ($this->enableCheckout == 'svn') {
                    $result = $this->database->get('svn_users', [
                        'svn_user_id',
                        'svn_user_status'
                    ], [
                        'svn_user_name' => $userName,
                        'svn_user_pass' => $userPass
                    ]);
                    if (empty($result)) {
                        return message(200, 0, '登录失败[账号或密码错误]');
                    }
                    if ($result['svn_user_status'] == 0) {
                        return message(200, 0, '登录失败[用户已过期]');
                    }
                } else {
                    $result = $this->ServiceApache->Auth($userName, $userPass);
                    if ($result['status'] != 1) {
                        return message2($result);
                    }

                    $result = $this->database->get('svn_users', [
                        'svn_user_id',
                        'svn_user_status'
                    ], [
                        'svn_user_name' => $userName
                    ]);
                    if (empty($result)) {
                        return message(200, 0, '登录失败[用户未同步]');
                    }
                    if ($result['svn_user_status'] == 0) {
                        return message(200, 0, '登录失败[用户已过期]');
                    }

                    $this->database->update('svn_users', [
                        'svn_user_pass' => $userPass
                    ], [
                        'svn_user_name' => $userName
                    ]);
                }
            }

            //更新登录时间
            $this->database->update('svn_users', [
                'svn_user_last_login' => date('Y-m-d H:i:s')
            ], [
                'svn_user_name' => $userName
            ]);

            //更新token
            $this->database->update('svn_users', [
                'svn_user_token' => $token = $this->CreateToken($userRole, $userName)
            ], [
                'svn_user_name' => $userName
            ]);
        } elseif ($userRole == 3) {
            $result = $this->database->get('subadmin', [
                'subadmin_id',
                'subadmin_name',
                'subadmin_password',
                'subadmin_status'
            ], [
                'subadmin_name' => $userName,
                'subadmin_password' => md5($userPass)
            ]);
            if (empty($result)) {
                return message(200, 0, '登录失败[账号或密码错误]');
            }
            if ($result['subadmin_status'] == 0) {
                return message(200, 0, '登录失败[用户已过期]');
            }

            //更新登录时间
            $this->database->update('subadmin', [
                'subadmin_last_login' => date('Y-m-d H:i:s')
            ], [
                'subadmin_name' => $userName
            ]);

            //更新token
            $this->database->update('subadmin', [
                'subadmin_token' => $token = $this->CreateToken($userRole, $userName)
            ], [
                'subadmin_name' => $userName
            ]);
        }

        //日志
        $this->Logs->InsertLog(
            '用户登录',
            sprintf("账号:%s IP地址:%s", $userName, funGetCip()),
            $userName
        );

        //邮件
        $this->Mail->SendMail('Common/Login', '用户登录成功通知', '账号：' . $userName . ' ' . 'IP地址：' . funGetCip() . ' ' . '时间：' . date('Y-m-d H:i:s'));

        $info = $this->GetDynamicRouting($userName, $userRole);
        return message(200, 1, '登陆成功', [
            'token' => $token,
            'user_name' => $userName,
            'user_role_name' => $userRoleName,
            'user_role_id' => $userRole,
            'route' => $info['route'],
            'functions' => $info['functions'],
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

        $part1 = $userRoleId . $this->configSign['signSeparator'] . $userName . $this->configSign['signSeparator'] . $startTime . $this->configSign['signSeparator'] . $endTime;

        $part2 = hash_hmac('md5', $part1, $this->configSign['signature']);

        return $part1 . $this->configSign['signSeparator'] . $part2;
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
        if ($this->userRoleId == 1) {
            $this->database->update('admin_users', [
                'admin_user_token' => '-'
            ], [
                'admin_user_name' => $this->userName
            ]);
        } elseif ($this->userRoleId == 2) {
            $this->database->update('svn_users', [
                'svn_user_token' => '-'
            ], [
                'svn_user_name' => $this->userName
            ]);
        } elseif ($this->userRoleId == 3) {
            $this->database->update('subadmin', [
                'subadmin_token' => '-'
            ], [
                'subadmin_name' => $this->userName
            ]);
        }

        //加入本token
        $this->AddBlack();

        //日志
        $this->Logs->InsertLog(
            '用户注销',
            sprintf("账号:%s IP地址:%s", $this->userName, funGetCip()),
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
        $codeId = $this->database->get('verification_code', 'code_id', [
            'uuid' => $uuid
        ]);

        if (empty($codeId)) {
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
        $arr = explode($this->configSign['signSeparator'], $this->token);
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
