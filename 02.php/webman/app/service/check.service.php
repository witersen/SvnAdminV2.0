<?php
/*
 * @Author: witersen
 * @Date: 2022-05-03 21:06:50
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 19:41:44
 * @Description: QQ:1801168257
 */

class CheckService
{
    public $config_svnadmin_reg;

    function __construct()
    {
        $this->config_svnadmin_reg = config('svnadmin_reg');
    }

    /**
     * 检查SVN仓库名称
     */
    public function CheckRepName($repName, $message = 'SVN仓库名称只能包含字母、数字、破折号、下划线、点，不能以点开头或结尾')
    {
        if (preg_match($this->config_svnadmin_reg['REG_SVN_REP_NAME'], $repName) != 1) {
            return ['code' => 200, 'status' => 0, 'message' => $message, 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 检查SVN用户名称
     */
    public function CheckRepUser($repUserName)
    {
        if (preg_match($this->config_svnadmin_reg['REG_SVN_USER_NAME'], $repUserName) != 1) {
            return ['code' => 200, 'status' => 0, 'message' => 'SVN用户名只能包含字母、数字、破折号、下划线、点', 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 检查SVN用户组名称
     */
    public function CheckRepGroup($repGroupName)
    {
        if (preg_match($this->config_svnadmin_reg['REG_SVN_GROUP_NAME'], $repGroupName) != 1) {
            return ['code' => 200, 'status' => 0, 'message' => 'SVN分组名只能包含字母、数字、破折号、下划线、点', 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 邮箱检查
     */
    public function CheckMail($mail)
    {
        if (preg_match_all($this->config_svnadmin_reg['REG_MAIL'], $mail) == 1) {
            return ['code' => 200, 'status' => 0, 'message' => '邮箱错误', 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }
}
