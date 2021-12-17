<?php

/**
 * 不提供修改svn用户名称的方法 因为从svn的机制来看 这是不合理的 一个准确唯一不变的用户对应svn仓库所有的历史记录是非常有必要的
 */

/**
 * 所有的行中以#开头或包含#的为无效行
 * 以[users]开头且仅包含[users]的所在行为开始行
 * 在开始行之后且以[*]开头的行为结束行
 * 开始行与结束行之间的去除无效行剩下的行为有效行
 * 有效行中内容不符合 user=pass 的标识为无效行
 */

/**
 * 获取svn所有用户列表
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * null 空的用户列表
 * array(
 *  'user1',
 *  'user2'
 * ) 
 */
function FunGetSvnUserList($passwdContent)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            return $resultPreg[1];
        }
    } else {
        return '0';
    }
}

/**
 * 获取svn所有用户以及每个用户对应的密码
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * null 空的用户列表
 * array(
 *  //正常数据
 *  'user1'=>'pass1',
 *  'user2'=>'pass2'
 * )
 */
function FunGetSvnUserPassList($passwdContent)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            return array_combine($resultPreg[1], $resultPreg[2]);
        }
    } else {
        return '0';
    }
}

/**
 * 获取svn某个用户的密码
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * null 空的用户列表
 * array(
 *  //正常数据
 *  'user1'=>'pass1',
 *  'user2'=>'pass2'
 * )
 */
function FunGetPassByUser($passwdContent, $user)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            return $resultPreg[2][array_search($user, $resultPreg[1])];
        }
    } else {
        return '0';
    }
}

/**
 * 添加svn用户以及对应的密码
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * 1 用户已存在
 * string 正常
 */
function FunAddSvnUser($passwdContent, $user, $passwd)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            $userStr = "\n$user=$passwd\n";
            return $passwdContent . $userStr;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($user, $resultPreg[1])) {
                return '1';
            }
            $combinArray = array_combine($resultPreg[1], $resultPreg[2]);
            $resultStr = "[users]\n";
            foreach ($combinArray as $key => $value) {
                $resultStr .= "$key=$value\n";
            }
            $resultStr .= "$user=$passwd\n";
            return $resultStr;
        }
    } else {
        return '0';
    }
}

/**
 * 删除svn用户
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * 1 用户不存在
 * string 正常
 */
function FunDelSvnUserPasswd($passwdContent, $user)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($user, $resultPreg[1])) {
                $combinArray = array_combine($resultPreg[1], $resultPreg[2]);
                $resultStr = "[users]\n";
                foreach ($combinArray as $key => $value) {
                    if ($key == $user) {
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
 * 修改密码文件中的用户名
 */
function FunUpdSvnUserPasswd($passwdContent, $oldUserName, $newUserName)
{
}

/**
 * 修改svn用户的密码
 * 
 * 0 文件格式错误 文件中不存在[users]标识
 * 1 用户不存在
 * string 正常
 */
function FunUpdSvnUserPass($passwdContent, $user, $passwd)
{
    preg_match_all("/^\[users\][\n+]([\s\S]*)/m", $passwdContent, $passwdContentPreg);
    if (array_key_exists(0, $passwdContentPreg[1])) {
        if (empty(trim($passwdContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=\s*([A-Za-z0-9_.@]+)/m", $passwdContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($user, $resultPreg[1])) {
                $combinArray = array_combine($resultPreg[1], $resultPreg[2]);
                $resultStr = "[users]\n";
                foreach ($combinArray as $key => $value) {
                    if ($key == $user) {
                        $value = $passwd;
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
