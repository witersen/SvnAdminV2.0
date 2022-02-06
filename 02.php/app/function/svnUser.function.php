<?php

declare(strict_types=1);

/**
 * 不提供修改SVN用户名称的方法
 * 一个不变的用户对应SVN仓库所有的历史记录是非常有必要的
 */

/**
 * 添加SVN用户
 * 
 * 0        文件格式错误(不存在[users]标识)
 * 1        用户已存在
 * string   正常
 */
function FunAddSvnUser($passwdContent, $userName, $userPass)
{
    $userName = trim($userName);
    $userPass = trim($userPass);
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            $userStr = "\n$userName=$userPass\n";
            return $passwdContent . $userStr;
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');

            $enabledAllUser = $resultPreg[1];
            array_walk($enabledAllUser, 'FunArrayValueEnabled');

            if (in_array($userName, $resultPreg[1]) || in_array($userName, $enabledAllUser)) {
                return '1';
            }
            $resultStr = "[users]\n";
            foreach (array_combine($resultPreg[1], $resultPreg[3]) as $key => $value) {
                $resultStr .= "$key=$value\n";
            }
            $resultStr .= "$userName=$userPass\n";
            return $resultStr;
        }
    } else {
        return '0';
    }
}

/**
 * 修改SVN用户(passwd文件中)
 */
function FunUpdSvnUserPasswd($passwdContent, $oldUserName, $newUserName)
{
}

/**
 * 删除SVN用户
 * 
 * 0        文件格式错误(不存在[users]标识)
 * 1        用户不存在
 * string   正常
 */
function FunDelSvnUserPasswd($passwdContent, $userName, $isDisabledUser = false)
{
    $userName = trim($userName);
    $userName = $isDisabledUser ? (REG_SVN_USER_DISABLED . $userName) : $userName;
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            if (in_array($userName, $resultPreg[1])) {
                $resultStr = "[users]\n";
                foreach (array_combine($resultPreg[1], $resultPreg[3]) as $key => $value) {
                    if ($key == $userName) {
                        continue;
                    }
                    $resultStr .= "$key=$value\n";
                }
                return $resultStr;
            }
            return '1';
        }
    } else {
        return '0';
    }
}

/**
 * 获取SVN用户列表
 * 
 * 文件格式错误(不存在[users]标识)
 * 0
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => Array
 *         (
 *             [userName] => u1
 *             [disabled] => 1
 *         )
 * 
 *     [1] => Array
 *         (
 *             [userName] => u2
 *             [disabled] => 0
 *         )
 * 
 * )
 */
function FunGetSvnUserList($passwdContent)
{
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            $result = [];
            foreach ($resultPreg[1] as $value) {
                $item = [];
                if (substr($value, 0, strlen(REG_SVN_USER_DISABLED)) == REG_SVN_USER_DISABLED) {
                    $item['userName'] = substr($value, strlen(REG_SVN_USER_DISABLED));
                    $item['disabled'] = '1';
                } else {
                    $item['userName'] = $value;
                    $item['disabled'] = '0';
                }
                array_push($result, $item);
            }
            return $result;
        }
    } else {
        return '0';
    }
}

/**
 * 获取SVN用户以及密码列表
 * 
 * 文件格式错误(不存在[users]标识)
 * 0
 * 
 * 空数据
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => Array
 *         (
 *             [userName] => u1
 *             [userPass] => p1
 *             [disabled] => 1
 *         )
 * 
 *     [1] => Array
 *         (
 *             [userName] => u2
 *             [userPass] => p2
 *             [disabled] => 0
 *         )
 * 
 * )
 */
function FunGetSvnUserPassList($passwdContent)
{
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            $result = [];
            foreach (array_combine($resultPreg[1], $resultPreg[3]) as $userName => $userPass) {
                $item = [];
                if (substr($userName, 0, strlen(REG_SVN_USER_DISABLED)) == REG_SVN_USER_DISABLED) {
                    $item['userName'] = substr($userName, strlen(REG_SVN_USER_DISABLED));
                    $item['userPass'] = $userPass;
                    $item['disabled'] = '1';
                } else {
                    $item['userName'] = $userName;
                    $item['userPass'] = $userPass;
                    $item['disabled'] = '0';
                }
                array_push($result, $item);
            }
            return $result;
        }
    } else {
        return '0';
    }
}

/**
 * 获取SVN指定用户的密码
 * 
 * 0        文件格式错误(不存在[users]标识)
 * 1        用户不存在
 * string   正常
 */
function FunGetPassByUser($passwdContent, $userName, $isDisabledUser = false)
{
    $userName = trim($userName);
    $userName = $isDisabledUser ? (REG_SVN_USER_DISABLED . $userName) : $userName;
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            if (array_search($userName, $resultPreg[1])) {
                return $resultPreg[3][array_search($userName, $resultPreg[1])];
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 修改SVN指定用户的密码
 * 
 * 0        文件格式错误(不存在[users]标识)
 * 1        用户不存在
 * string   正常
 */
function FunUpdSvnUserPass($passwdContent, $userName, $userPass, $isDisabledUser = false)
{
    $userName = trim($userName);
    $userPass = trim($userPass);
    $userName = $isDisabledUser ? (REG_SVN_USER_DISABLED . $userName) : $userName;
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            if (in_array($userName, $resultPreg[1])) {
                $resultStr = "[users]\n";
                foreach (array_combine($resultPreg[1], $resultPreg[3]) as $key => $value) {
                    if ($key == $userName) {
                        $value = $userPass;
                    }
                    $resultStr .= "$key=$value\n";
                }
                return $resultStr;
            }
            return '1';
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个用户所在的分组列表
 * $recursively = false 只获取用户与分组处于直接包含关系的分组列表
 * $recursively = true  获取用户与分组处于直接包含关系的分组列表、获取处于分组嵌套分组导致用户与分组处于间接包含关系的分组列表
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => goup1
 *     [1] => group
 * )
 */
function FunGetSvnUserGroupList($authzContent, $userName)
{
    $userName = trim($userName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            $userArray = [];
            foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, 'FunArrayValueTrim');
                if (in_array($userName, $userGroupArray)) {
                    array_push($userArray, $groupStr);
                }
            }
            return $userArray;
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个用户有权限的所有仓库列表
 *
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => rep1
 *     [1] => rep2
 * )
 */
function FunGetUserPriRepListWithoutPri($authzContent, $userName)
{
    $userName = trim($userName);
    preg_match_all(sprintf(REG_AUTHZ_USER_PRI_REPS, $userName), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'FunArrayValueTrim');
        return $authzContentPreg[1];
    } else {
        return [];
    }
}

/**
 * 获取某个用户有权限的所有仓库列表以及对应的权限
 *
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => Array
 *         (
 *             [repName] => rep1
 *             [repPri] => rw
 *         )
 *     [1] => Array
 *         (
 *             [repName] => rep2
 *             [repPri] => rw
 *         )
 * )
 */
function FunGetUserPriRepListWithPri($authzContent, $userName)
{
    $userName = trim($userName);
    preg_match_all(sprintf(REG_AUTHZ_USER_PRI_REPS, $userName), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'FunArrayValueTrim');
        array_walk($authzContentPreg[3], 'FunArrayValueTrim');
        $result = [];
        foreach (array_combine($authzContentPreg[1], $authzContentPreg[3]) as $key => $value) {
            $item = [];
            $item['repName'] = $key;
            $item['repPri'] = $value;
            array_push($result, $item);
        }
        return $result;
    } else {
        return [];
    }
}

/**
 * 从所有仓库路径和分组下修改用户名
 *
 * string       正常
 */
function FunUpdUserAuthz($authzContent, $oldUserName, $newUserName)
{
}

/**
 * 从所有仓库路径和分组下删除SVN用户
 * 从所有分组中删除用户
 * 从所有仓库下删除用户
 * 
 * 0        文件格式错误(不存在[users]标识)
 * string   正常
 */
function FunDelUserAuthz($authzContent, $userName)
{
    $userName = trim($userName);
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg1);
    if (array_key_exists(0, $authzContentPreg1[0])) {
        if (!empty(trim($authzContentPreg1[1][0]))) {
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg1[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            $groupContent = "";
            foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                $groupContent .= "$groupStr=";
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, 'FunArrayValueTrim');
                if (in_array($userName, $userGroupArray)) {
                    unset($userGroupArray[array_search($userName, $userGroupArray)]);
                }
                $groupContent .=  implode(',', $userGroupArray) . "\n";
            }
            $content1 .=  $groupContent;
        }
    } else {
        return '0';
    }
    $content2 = preg_replace("/([^@]^$userName" . "[\s]*=.*?)\n/m", "\n", str_replace($authzContentPreg1[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 禁用某个SVN用户
 * 0        文件格式错误(不存在[users]标识)
 * 1        要禁用的用户不存在
 * string   正常
 */
function FunDisabledUser($passwdContent, $userName)
{
    $userName = trim($userName);
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            if (in_array($userName, $resultPreg[1])) {
                $resultStr = "[users]\n";
                foreach (array_combine($resultPreg[1], $resultPreg[3]) as $key => $value) {
                    if ($key == $userName) {
                        $key = REG_SVN_USER_DISABLED . $key;
                    }
                    $resultStr .= "$key=$value\n";
                }
                return $resultStr;
            }
            return '1';
        }
    } else {
        return '0';
    }
}

/**
 * 启用某个SVN用户
 * 0        文件格式错误(不存在[users]标识)
 * 1        要启用的用户不存在
 * string   正常
 */
function FunEnabledUser($passwdContent, $userName)
{
    $userName = trim($userName);
    preg_match_all(REG_PASSWD_USER_WITH_CON, $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all(sprintf(REG_PASSWD_USER_PASSWD, REG_SVN_USER_DISABLED), $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[3], 'FunArrayValueTrim');
            if (in_array(REG_SVN_USER_DISABLED . $userName, $resultPreg[1])) {
                $resultStr = "[users]\n";
                foreach (array_combine($resultPreg[1], $resultPreg[3]) as $key => $value) {
                    if ($key ==  REG_SVN_USER_DISABLED . $userName) {
                        $key = $userName;
                    }
                    $resultStr .= "$key=$value\n";
                }
                return $resultStr;
            }
            return '1';
        }
    } else {
        return '0';
    }
}


