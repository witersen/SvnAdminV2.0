<?php
/*
 * @Author: witersen
 * @Date: 2022-04-27 15:45:45
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-12 13:30:14
 * @Description: QQ:1801168257
 * @copyright: https://github.com/witersen/
 */

namespace SVNAdmin\SVN;

class Rep extends Core
{
    function __construct($authzFileContent, $passwdFileContent, $config_svn, $config_bin)
    {
        parent::__construct($authzFileContent, $passwdFileContent, $config_svn, $config_bin);
    }

    /**
     * 获取某个仓库路径下有权限的用户列表
     * 
     * 0    不存在该仓库路径的记录
     * 
     * 空列表
     * []
     * 
     * 正常数据
     * Array
     * (
     *     [0] => u1
     *     [1] => u2
     * )
     */
    function GetRepUserListWithoutPri($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                return $resultPreg[1];
            }
        } else {
            return '0';
        }
    }

    /**
     * 获取某个仓库路径下有权限的用户列表以及对应的权限
     * 
     * 不存在该仓库路径的记录
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
     *             [userName] => rep1
     *             [userPri] => r
     *         )
     *     [1] => Array
     *         (
     *             [userName] => rep2
     *             [userPri] => 
     *         )
     * )
     */
    function GetRepUserListWithPri($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');
                $result = [];
                //注意$value的值 不限于合法的 r 、rw、空 以及不合法的其它字符
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $key => $value) {
                    $item = [];
                    $item['userName'] = $key;
                    $item['userPri'] = $value;
                    array_push($result, $item);
                }
                return $result;
            }
        } else {
            return '0';
        }
    }

    /**
     * 获取某个仓库路径下有权限的分组列表
     * 
     * 不存在该仓库路径的记录
     * 0                
     * 
     * 空列表
     * []
     * 
     * 正常数据
     * Array
     * (
     *     [0] => g1
     *     [1] => g2
     * )
     */
    function GetRepGroupListWithoutPri($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                return $resultPreg[1];
            }
        } else {
            return '0';
        }
    }

    /**
     * 获取某个仓库路径下有权限的分组列表以及对应的权限
     * 
     * 不存在该仓库路径的记录
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
     *             [groupName] => rep1
     *             [groupPri] => rw
     *         )
     *     [1] => Array
     *         (
     *             [groupName] => rep2
     *             [groupPri] => rw
     *         )
     * )
     */
    function GetRepGroupListWithPri($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');
                $result = [];
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $key => $value) {
                    $item = [];
                    $item['groupName'] = $key;
                    $item['groupPri'] = $value;
                    array_push($result, $item);
                }
                return $result;
            }
        } else {
            return '0';
        }
    }

    /**
     * 为某个仓库路径设置用户权限
     * 包括为已有权限的用户修改权限
     * 包括为没有权限的用户增加权限
     * 如果该目录的该用户的父目录有权限 那么所有子目录继承权限
     * 
     * 0        不存在该仓库路径的记录
     * string   正常 
     */
    function SetRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                //添加用户
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n$user=$privilege\n", $authzContent);
            } else {
                //进一步判断有没有用户数据
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                //处理分组
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                if (in_array($user, $resultPreg[1])) {
                    //编辑
                    $userContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        $userContent .= "@$groupStr=$groupPri\n";
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        if ($userStr == $user) {
                            $userContent .= "$userStr=$privilege\n";
                        } else {
                            $userContent .= "$userStr=$userPri\n";
                        }
                    }

                    return str_replace($authzContentPreg[0][0], $userContent, $authzContent);
                } else {
                    //新增
                    $userContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        $userContent .= "@$groupStr=$groupPri\n";
                    }

                    //处理用户
                    $userContent .= "$user=$privilege\n";
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        $userContent .= "$userStr=$userPri\n";
                    }
                    return str_replace($authzContentPreg[0][0], $userContent, $authzContent);
                }
            }
        } else {
            return '0';
        }
    }

    /**
     * 删除某个仓库路径的用户权限
     * 父目录有权限 无法取消子目录的权限
     * 
     * 0        不存在该仓库路径的记录
     * 1        已删除
     * string   正常
     */
    function DelRepUserPri($authzContent, $user, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return '1';
            } else {
                //进一步判断有没有用户数据
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                //处理分组
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                if (in_array($user, $resultPreg[1])) {
                    //删除
                    $userContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        $userContent .= "@$groupStr=$groupPri\n";
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        if ($userStr == $user) {
                            continue;
                        }
                        $userContent .= "$userStr=$userPri\n";
                    }

                    return str_replace($authzContentPreg[0][0], $userContent, $authzContent);
                } else {
                    return '1';
                }
            }
        } else {
            return '0';
        }
    }

    /**
     * 修改某个仓库路径的用户权限
     * 
     * 0        不存在该仓库路径的记录
     * string   正常
     */
    function UpdRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n$user=$privilege\n", $authzContent);
            } else {
                //进一步判断有没有用户数据
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                //处理分组
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                //编辑
                $userContent = "[$repName:$repPath]\n";

                //处理分组
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                    if ($userStr == $user) {
                        $userContent .= "$userStr=$privilege\n";
                    } else {
                        $userContent .= "$userStr=$userPri\n";
                    }
                }
                return str_replace($authzContentPreg[0][0], $userContent, $authzContent);
            }
        } else {
            return '0';
        }
    }

    /**
     * 为某个仓库路径设置分组权限
     * 包括为已有权限的分组修改权限
     * 包括为没有权限的分组增加权限
     * 其中如果分组和用户都设置了权限 但是权限一个为可读 一个为可写 应该遵循什么规则呢 开发者无需考虑
     * 
     * 0        不存在该仓库路径的记录
     * string   正常
     */
    function SetRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                //进一步判断有没有分组数据
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                //处理用户
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                if (in_array($group, $resultPregGroup[1])) {
                    //编辑
                    $groupContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        if ($groupStr == $group) {
                            $groupContent .= "@$group=$privilege\n";
                        } else {
                            $groupContent .= "@$groupStr=$groupPri\n";
                        }
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        $groupContent .= "$userStr=$userPri\n";
                    }

                    return str_replace($authzContentPreg[0][0], $groupContent, $authzContent);
                } else {
                    //新增
                    $groupContent = "[$repName:$repPath]\n";

                    //处理分组
                    $groupContent .= "@$group=$privilege\n";
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        $groupContent .= "@$groupStr=$groupPri\n";
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        $groupContent .= "$userStr=$userPri\n";
                    }

                    return str_replace($authzContentPreg[0][0], $groupContent, $authzContent);
                }
            }
        } else {
            return '0';
        }
    }

    /**
     * 删除某个仓库的分组权限
     * 
     * 0        不存在该仓库路径的记录
     * 1        已删除
     * string   正常
     */
    function DelRepGroupPri($authzContent, $group, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return '1';
            } else {
                //进一步判断有没有分组数据
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                //处理用户
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                if (in_array($group, $resultPregGroup[1])) {
                    //编辑
                    $groupContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        if ($groupStr == $group) {
                            continue;
                        } else {
                            $groupContent .= "@$groupStr=$groupPri\n";
                        }
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        $groupContent .= "$userStr=$userPri\n";
                    }

                    return str_replace($authzContentPreg[0][0], $groupContent, $authzContent);
                } else {
                    return '1';
                }
            }
        } else {
            return '0';
        }
    }

    /**
     * 修改某个仓库路径的分组权限
     * 
     * 0        不存在该仓库路径的记录
     * 1        该仓库下不存在该分组
     * string   正常
     */
    function UpdRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                //进一步判断有没有分组数据
                preg_match_all($this->REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
                array_walk($resultPregGroup[1], 'FunArrayValueTrim');
                array_walk($resultPregGroup[2], 'FunArrayValueTrim');

                //处理用户
                preg_match_all($this->REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
                array_walk($resultPreg[1], 'FunArrayValueTrim');
                array_walk($resultPreg[2], 'FunArrayValueTrim');

                if (in_array($group, $resultPregGroup[1])) {
                    //编辑
                    $groupContent = "[$repName:$repPath]\n";

                    //处理分组
                    foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                        if ($groupStr == $group) {
                            $groupContent .= "@$group=$privilege\n";
                        } else {
                            $groupContent .= "@$groupStr=$groupPri\n";
                        }
                    }

                    //处理用户
                    foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
                        $groupContent .= "$userStr=$userPri\n";
                    }

                    return str_replace($authzContentPreg[0][0], $groupContent, $authzContent);
                } else {
                    return '1';
                }
            }
        } else {
            return '0';
        }
    }

    /**
     * 向配置文件写入仓库路径
     *
     * 1        已存在
     * string   正常
     */
    function SetRepAuthz($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITHOUT_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            return '1';
        } else {
            return $authzContent . "\n[$repName:$repPath]\n";
        }
    }

    /**
     * 从配置文件删除指定仓库的指定路径
     * 
     * 1        已删除
     * string   正常
     */
    function DelRepPath($authzContent, $repName, $repPath)
    {
        //处理路径结尾
        if ($repPath != '/') {
            if (substr($repPath, strlen($repPath) - 1, 1) == '/') {
                $repPath = substr($repPath, 0, strlen($repPath) - 1);
            }
        }

        preg_match_all(sprintf($this->REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            return str_replace($authzContentPreg[0][0], "", $authzContent);
        } else {
            return '1';
        }
    }

    /**
     * 从配置文件删除指定仓库的所有路径
     *
     * 1        已删除
     * string   正常
     */
    function DelRepAuthz($authzContent, $repName)
    {
        preg_match_all(sprintf($this->REG_AUTHZ_REP_ALL_PATH_WITH_CON, $repName), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[0])) {
            foreach ($authzContentPreg[0] as $key => $value) {
                $authzContent = str_replace($value, "", $authzContent);
            }
            return $authzContent;
        } else {
            return '1';
        }
    }

    /**
     * 从配置文件获取所有的仓库名称
     * 
     * 不匹配这些仓库的内容和具体路径
     * 
     * 空列表
     * []
     * 
     * 正常数据
     * 
     */
    function GetNoPathAndConRepAuthz($authzContent)
    {
        preg_match_all($this->REG_AUTHZ_ALL_REP_WITHOUT_PATH_AND_CON, $authzContent, $authzContentPreg);
        array_walk($authzContentPreg[1], 'FunArrayValueTrim');
        return array_values(array_unique($authzContentPreg[1]));
    }

    /**
     * 从配置文件修改仓库名称
     * 修改该仓库所有路径的仓库名称
     * 
     * 没有校验要修改的仓库是否已经存在 需要上层函数进行校验
     *
     * 1        仓库不存在
     * string   正常
     */
    function UpdRepAuthz($authzContent, $oldRepName, $newRepName)
    {
        preg_match_all(sprintf($this->REG_AUTHZ_REP_ALL_PATH_WITHOUT_CON, $oldRepName), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[1])) {
            foreach ($authzContentPreg[0] as $key => $value) {
                $authzContent = str_replace($value, '[' . $newRepName . ':' . $authzContentPreg[1][$key] . ']', $authzContent);
            }
            return $authzContent;
        } else {
            return '1';
        }
    }

    /**
     * 获取所有用户都有权限的仓库列表带有权限(包括只读、读写)
     * 用户匹配 *=r *=rw
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
     *             [repPri] => r
     *         )
     * )
     */
    function GetAllHavePriRepListWithPri($authzContent)
    {
        $userName = '*';
        preg_match_all(sprintf($this->REG_AUTHZ_ALL_HAVE_PRI_REPS, $userName), $authzContent, $authzContentPreg);
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
     * 获取所有用户都有权限的仓库列表(包括只读、读写)
     * 用户匹配 *=r *=rw
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
    function GetAllHavePriRepListWithoutPri($authzContent)
    {
        $userName = '*';
        preg_match_all(sprintf($this->REG_AUTHZ_ALL_HAVE_PRI_REPS, $userName), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[1])) {
            array_walk($authzContentPreg[1], 'FunArrayValueTrim');
            array_walk($authzContentPreg[3], 'FunArrayValueTrim');
            $result = [];
            foreach (array_combine($authzContentPreg[1], $authzContentPreg[3]) as $key => $value) {
                array_push($result, $key);
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 获取所有用户都无权限的仓库列表
     * 用户匹配 *=
     */
    function GetAllNoPriRepListWithoutPri($authzContent)
    {
        $userName = '*';
        preg_match_all(sprintf($this->REG_AUTHZ_ALL_NO_PRI_REPS, $userName), $authzContent, $authzContentPreg);
        if (array_key_exists(0, $authzContentPreg[1])) {
            array_walk($authzContentPreg[1], 'FunArrayValueTrim');
            array_walk($authzContentPreg[3], 'FunArrayValueTrim');
            $result = [];
            foreach (array_combine($authzContentPreg[1], $authzContentPreg[3]) as $key => $value) {
                if ($value == '') {
                    array_push($result, $key);
                }
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 获取SVN仓库列表
     */
    function GetRepList()
    {
        $repArray = [];
        $file_arr = scandir($this->config_svn['rep_base_path']);
        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (is_dir($this->config_svn['rep_base_path'] .  $file_item)) {
                    $file_arr2 = scandir($this->config_svn['rep_base_path'] .  $file_item);
                    foreach ($file_arr2 as $file_item2) {
                        if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                            array_push($repArray, array(
                                'repName' => $file_item,
                                'repUrl' => $this->config_svn['rep_base_path'] .  $file_item,
                                'repSize' => round(FunGetDirSize($this->config_svn['rep_base_path'] .  $file_item) / (1024 * 1024), 2),
                                'repCheckoutUrl' => 'svn://' . 'SERVER_DOMAIN' . '/' . $file_item,
                            ));
                            break;
                        }
                    }
                }
            }
        }
        return $repArray;
    }

    /**
     * 获取简单SVN仓库列表
     */
    function GetSimpleRepList()
    {
        $repArray = [];
        $file_arr = scandir($this->config_svn['rep_base_path']);
        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (is_dir($this->config_svn['rep_base_path'] .  $file_item)) {
                    $file_arr2 = scandir($this->config_svn['rep_base_path'] .  $file_item);
                    foreach ($file_arr2 as $file_item2) {
                        if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                            array_push($repArray, $file_item);
                            break;
                        }
                    }
                }
            }
        }
        return $repArray;
    }

    /**
     * 初始化仓库结构为 trunk branches tags
     */
    function InitRepStruct($templetePath, $repPath, $initUser = 'SVNAdmin', $initPass = 'SVNAdmin', $message = 'Initial structure')
    {
        $cmd = sprintf("'%s' import '%s' 'file:///%s' --quiet --username '%s' --password '%s' --message '%s'", $this->config_bin['svn'], $templetePath, $repPath, $initUser, $initPass, $message);
        FunShellExec($cmd);
    }

    /**
     * 使用 svnadmin info $repPath 获取部分仓库信息
     * UUID
     * Revisions
     * Repository Format
     * Compatible With Version
     * Repository Capability
     * Filesystem Type
     * Filesystem Format
     * FSFS Sharded
     * FSFS Shard Size
     * FSFS Shards Packed
     * FSFS Logical Addressing
     * 
     * 此函数暂未使用因为包含兼容性问题 需要 Subversion > 1.9
     */
    function GetRepInfo($repName)
    {
        $repPath = $this->config_svn['rep_base_path'] .  $repName;

        $svnadminInfoCmd = sprintf("'%s' info '%s'", $this->config_bin['svnadmin'], $repPath);

        $cmdResult = FunShellExec($svnadminInfoCmd);
        $cmdResult = $cmdResult['result'];

        preg_match_all($this->REG_REP_INFO, $cmdResult, $svnadminInfoPreg);

        $svnadminInfoResult =  array_combine($svnadminInfoPreg[1], $svnadminInfoPreg[2]);

        unset($svnadminInfoResult['Path'], $svnadminInfoResult['Configuration File']);

        return $svnadminInfoResult;
    }

    /**
     * 使用 svnlook tree $repPath 获取仓库的最新版本的目录树
     */
    function GetRepTree($repName)
    {
        $repPath = $this->config_svn['rep_base_path'] .  $repName;
        $svnadminInfoCmd = sprintf("'%s' tree '%s'", $this->config_bin['svnlook'], $repPath);
        $cmdResult = FunShellExec($svnadminInfoCmd);
        $cmdResult = $cmdResult['result'];
        // $cmdResult = FunShellExec($svnadminInfoCmd);
        $treeArray = explode("\n", $cmdResult);
        //去除数组中的空字符串键值 通常为最后一项
        $treeArray = array_filter($treeArray, 'FunArrayValueFilter');
        //获取对应空格数开头的数组
        $spaceCountArray = array_map('FunArrayGetStrSpaceCount', $treeArray);
        //获取是否为目录或者文件的数组
        $isFolderArray = array_map('FunArrayIsStrFolder', $treeArray);
        //合并数组
        $complateArray = [];
        foreach ($treeArray as $key => $value) {
            array_push($complateArray, array(
                'content' => trim($value),
                'spaceCount' => $spaceCountArray[$key],
                'isFolder' => $isFolderArray[$key]
            ));
        }
        //拼接根目录
        return [
            [
                'title' => '/',
                'expand' => true,
                'children' => self::GetFolderStruct($complateArray, 0)
            ]
        ];
    }

    /**
     * 按照指定的数据结构以递归方式拼接目录结构
     * 
     * 示例输入以下数组和0
     * Array
     * (
     *     [0] => Array
     *         (
     *             [content] => /
     *             [spaceCount] => 0
     *             [isFolder] => 1
     *         )
     *     [1] => Array
     *         (
     *             [content] => 00.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [2] => Array
     *         (
     *             [content] => 001.jpg
     *             [spaceCount] => 2
     *             [isFolder] => 0
     *         )
     *     [3] => Array
     *         (
     *             [content] => 01.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [4] => Array
     *         (
     *             [content] => 011.folder/
     *             [spaceCount] => 2
     *             [isFolder] => 1
     *         )
     *     [5] => Array
     *         (
     *             [content] => 02.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [6] => Array
     *         (
     *             [content] => 021.folder/
     *             [spaceCount] => 2
     *             [isFolder] => 1
     *         )
     *     [7] => Array
     *         (
     *             [content] => 0211.file.docx
     *             [spaceCount] => 3
     *             [isFolder] => 0
     *         )
     *     [8] => Array
     *         (
     *             [content] => 03.jpg
     *             [spaceCount] => 1
     *             [isFolder] => 0
     *         )
     * )
     * 
     * 示例输出
     * Array
     * (
     *     [0] => Array
     *         (
     *             [title] => 00.folder/
     *             [children] => Array
     *                 (
     *                     [0] => Array
     *                         (
     *                             [title] => 001.jpg
     *                         )
     *                 )
     *         )
     *     [1] => Array
     *         (
     *             [title] => 01.folder/
     *             [children] => Array
     *                 (
     *                     [0] => Array
     *                         (
     *                             [title] => 011.folder/
     *                         )
     *                 )
     *         )
     *     [2] => Array
     *         (
     *             [title] => 02.folder/
     *             [children] => Array
     *                 (
     *                     [0] => Array
     *                         (
     *                             [title] => 021.folder/
     *                             [children] => Array
     *                                 (
     *                                     [0] => Array
     *                                         (
     *                                             [title] => 0211.file.docx
     *                                         )
     *                                 )
     *                         )
     *                 )
     *         )
     *     [3] => Array
     *         (
     *             [title] => 03.jpg
     *         )
     * )
     */
    function GetFolderStruct($complateArray, $fileIndex)
    {
        //获取当前文件夹的目录内容 以子文件夹和子文件下标的形式返回
        $dirContent = self::GetFolderDirAndFiles($complateArray, $fileIndex);

        //按照特定结构拼接
        $result = [];
        foreach ($dirContent as $key => $value) {
            $children = self::GetFolderStruct($complateArray, $value);
            if ($children == []) {
                array_push($result, array(
                    'title' => $complateArray[$value]['content'],
                    'expand' => true,
                ));
            } else {
                array_push($result, array(
                    'title' => $complateArray[$value]['content'],
                    'expand' => true,
                    'children' => $children
                ));
            }
        }
        return $result;
    }

    /**
     * 模拟正常的文件系统 获取指定目录或者文件的子文件夹和子文件(文件会返回空)
     * 
     * 示例输入(只用到spaceCount键值)以下数组和0
     * Array
     * (
     *     [0] => Array
     *         (
     *             [content] => /
     *             [spaceCount] => 0
     *             [isFolder] => 1
     *         )
     *     [1] => Array
     *         (
     *             [content] => 00.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [2] => Array
     *         (
     *             [content] => 001.jpg
     *             [spaceCount] => 2
     *             [isFolder] => 0
     *         )
     *     [3] => Array
     *         (
     *             [content] => 01.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [4] => Array
     *         (
     *             [content] => 011.folder/
     *             [spaceCount] => 2
     *             [isFolder] => 1
     *         )
     *     [5] => Array
     *         (
     *             [content] => 02.folder/
     *             [spaceCount] => 1
     *             [isFolder] => 1
     *         )
     *     [6] => Array
     *         (
     *             [content] => 021.folder/
     *             [spaceCount] => 2
     *             [isFolder] => 1
     *         )
     *     [7] => Array
     *         (
     *             [content] => 0211.file.docx
     *             [spaceCount] => 3
     *             [isFolder] => 0
     *         )
     *     [8] => Array
     *         (
     *             [content] => 03.jpg
     *             [spaceCount] => 1
     *             [isFolder] => 0
     *         )
     * )
     * 
     * 示例输出
     * Array
     * (
     *     [0] => 1
     *     [1] => 3
     *     [2] => 5
     *     [3] => 8
     * )
     */
    function GetFolderDirAndFiles($complateArray, $fileIndex)
    {
        $result = [];
        //末尾
        if (!array_key_exists($fileIndex + 1, $complateArray)) {
            return [];
        }
        //无子节点
        if ($complateArray[$fileIndex]['spaceCount'] == $complateArray[$fileIndex + 1]['spaceCount']) {
            return [];
        }
        //有子节点
        foreach ($complateArray as $key => $value) {
            if ($key > $fileIndex) {
                //遇到同级目录退出
                //遇到上级目录退出
                if ($complateArray[$key]['spaceCount'] <= $complateArray[$fileIndex]['spaceCount']) {
                    break;
                }
                //遇到下级目录push
                if ($complateArray[$key]['spaceCount'] == $complateArray[$fileIndex]['spaceCount'] + 1) {
                    array_push($result, $key);
                }
            }
        }
        return $result;
    }


    /**
     * 检查仓库是否存在或者是否存在同名的文件夹
     */
    function CheckRepExist($repName, $message = '仓库已经存在')
    {
        clearstatcache();
        if (is_dir($this->config_svn['rep_base_path'] .  $repName)) {
            return ['code' => 200, 'status' => 0, 'message' => $message, 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 检查仓库是否创建成功
     */
    function CheckRepCreate($repName, $message = '仓库创建失败')
    {
        clearstatcache();
        if (!is_dir($this->config_svn['rep_base_path'] .  $repName)) {
            return ['code' => 200, 'status' => 0, 'message' => $message, 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 检查仓库是否删除成功
     */
    function CheckRepDelete($repName, $message = '仓库删除失败')
    {
        clearstatcache();
        if (is_dir($this->config_svn['rep_base_path'] .  $repName)) {
            return ['code' => 200, 'status' => 0, 'message' => $message, 'data' => []];
        }
        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
    }

    /**
     * 获取仓库的修订版本数量
     * svnadmin info
     * 
     * Subversion 1.9 及以前没有 svnadmin info 子指令 
     * 因此使用 svnlook youngest 来代替
     */
    function GetRepRev($repName)
    {
        // $cmd = sprintf("'%s' info '%s' | grep 'Revisions' | awk '{print $2}'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $repName);

        $cmd = sprintf("'%s' youngest '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $repName);

        $result = FunShellExec($cmd);

        return (int)trim($result['result']);
    }

    /**
     * 获取仓库的UUID
     */
    function GetRepUUID($repName)
    {
        $cmd = sprintf("'%s' uuid '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $repName);

        $result = FunShellExec($cmd);

        return trim($result['result']);
    }

    /**
     * 获取仓库的属性内容（key-value的形式）
     * svnadmin info
     * 
     * Subversion 1.9 及以前没有 svnadmin info 子指令 
     */
    function GetRepDetail110($repName)
    {
        $cmd = sprintf("'%s' info '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $repName);
        $result = FunShellExec($cmd);
        return $result;
    }

    /**
     * 获取仓库下某个文件的体积
     * 
     * 目前为默认最新版本
     * 
     * 根据体积大小自动调整单位
     * 
     * svnlook file
     */
    function GetRepRevFileSize($repName, $filePath)
    {
        $cmd = sprintf("'%s' filesize '%s' '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] . $repName, $filePath);
        $result = FunShellExec($cmd);
        $size = (int)$result['result'];
        return FunFormatSize($size);
    }

    /**
     * 获取仓库下指定文件或者文件夹的最高修订版本
     * 
     * svnlook history
     */
    function GetRepFileRev($repName, $filePath)
    {
        $cmd = sprintf("'%s' history --limit 1 '%s' '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $repName, $filePath);
        $result = FunShellExec($cmd);
        $result = $result['result'];
        $resultArray = explode("\n", $result);
        $content = preg_replace("/\s{2,}/", ' ', $resultArray[2]);
        $contentArray = explode(' ', $content);
        return trim($contentArray[1]);
    }

    /**
     * 获取仓库下指定文件或者文件夹的作者
     * 
     * svnlook author
     */
    function GetRepFileAuthor($repName, $rev)
    {
        $cmd = sprintf("'%s' author -r %s '%s'", $this->config_bin['svnlook'], $rev, $this->config_svn['rep_base_path'] .  $repName);
        $result = FunShellExec($cmd);
        return $result['result'];
    }

    /**
     * 获取仓库下指定文件或者文件夹的提交日期
     * 
     * svnlook date
     */
    function GetRepFileDate($repName, $rev)
    {
        $cmd = sprintf("'%s' date -r %s '%s'", $this->config_bin['svnlook'], $rev, $this->config_svn['rep_base_path'] .  $repName);
        $result = FunShellExec($cmd);
        return $result['result'];
    }

    /**
     * 获取仓库下指定文件或者文件夹的提交日志
     * 
     * svnlook log
     */
    function GetRepFileLog($repName, $rev)
    {
        $cmd = sprintf("'%s' log -r %s '%s'", $this->config_bin['svnlook'], $rev, $this->config_svn['rep_base_path'] .  $repName);
        $result = FunShellExec($cmd);
        return $result['result'];
    }

    /**
     * 使用 svnadmin dump 备份仓库
     * 
     * 默认为全版本
     */
    function RepDump($repName, $backupName)
    {
        $cmd = sprintf("'%s' dump '%s' --quiet  > '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $repName, $this->config_svn['backup_base_path'] .  $backupName);
        FunShellExec($cmd);
    }

    /**
     * 删除备份文件
     */
    function DelRepBackup($fileName)
    {
        $cmd = sprintf("cd '%s' && rm -f './%s'", $this->config_svn['backup_base_path'], $fileName);
        FunShellExec($cmd);
    }

    /**
     * 使用 svnadmin load 导入仓库
     */
    function RepLoad($repName, $fileName)
    {
        $cmd = sprintf("'%s' load --quiet '%s' < '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $repName, $this->config_svn['backup_base_path'] .  $fileName);
        $result = FunShellExec($cmd);
        return $result;
    }

    /**
     * 使用svn list进行内容获取
     */
    function CheckSvnUserPathAutzh($checkoutHost, $repName, $repPath, $svnUserName, $svnUserPass)
    {
        $cmd = sprintf("'%s' list '%s' --username '%s' --password '%s' --no-auth-cache --non-interactive --trust-server-cert", $this->config_bin['svn'], $checkoutHost . '/' . $repName . $repPath, $svnUserName, $svnUserPass);
        $result = FunShellExec($cmd);

        if ($result['resultCode'] != 0) {
            //: Authentication error from server: Password incorrect
            if (strstr($result['error'], 'svn: E170001') && strstr($result['error'], 'Password incorrect')) {
                return ['code' => 200, 'status' => 0, 'message' => '密码错误', 'data' => []];
            }
            //: Authorization failed
            if (strstr($result['error'], 'svn: E170001') && strstr($result['error'], 'Authorization failed')) {
                return ['code' => 200, 'status' => 0, 'message' => '无访问权限', 'data' => []];
            }
            //svn: E170001类型的其它错误
            if (strstr($result['error'], 'svn: E170001')) {
                return ['code' => 200, 'status' => 0, 'message' => '无访问权限-svn: E170001', 'data' => []];
            }
            //: Invalid authz configuration
            if (strstr($result['error'], 'svn: E220003')) {
                return ['code' => 200, 'status' => 0, 'message' => 'authz文件配置错误 请使用svnauthz-validate工具检查', 'data' => []];
            }
            //: Unable to connect to a repository at URL
            if (strstr($result['error'], 'svn: E170013')) {
                return ['code' => 200, 'status' => 0, 'message' => '无法连接到仓库', 'data' => []];
            }
            //: Could not list all targets because some targets don't exist
            if (strstr($result['error'], 'svn: warning: W160013') || strstr($result['error'], "svn: E200009")) {
                return ['code' => 200, 'status' => 0, 'message' => '该授权路径在仓库不存在 请刷新以同步', 'data' => []];
            }
            return ['code' => 200, 'status' => 0, 'message' => '认证出错' . $result['error'], 'data' => []];
        }

        return ['code' => 200, 'status' => 1, 'message' => '成功', 'data' => $result['result']];
    }
}
