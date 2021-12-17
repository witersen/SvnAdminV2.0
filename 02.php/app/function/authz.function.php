<?php

/**
 * 获取分组列表
 * 
 * @param string $authzContent
 * @return 
 * 0                    文件格式错误
 * null                 无分组
 * array('g1','g2')     有分组
 */
function FunGetSvnGroupList($authzContent)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            return $resultPreg[1];
        }
    } else {
        return '0';
    }
}

/**
 * 获取分组列表以及每个分组包含的用户列表
 * 
 * @param string $authzContent
 * @return 
 * 0                        文件格式错误(不存在[groups]标识)
 * null                     无分组
 * array(                   有分组
 *  'g1'=>array('u1',u2),
 *  'g2'=>array('u2'),
 * )
 */
function FunGetSvnGroupUserList($authzContent)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
            foreach ($groupArray as $group => $userStr) {
                $userArray = explode(',', $userStr);
                array_walk($userArray, 'ArrayValueTrim');
                $groupArray[$group] = $userArray;
            }
            return $groupArray;
        }
    } else {
        return '0';
    }
}

/**
 * 添加分组
 * 
 * @param string $authzContent
 * @param string $group
 * @return 
 * 0                文件格式错误
 * 1                分组已存在
 * string           正常
 */
function FunAddSvnGroup($authzContent, $group)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            $groupContent = "\n[groups]\n$group=\n";
            return str_replace($authzContentPreg[0][0], str_replace("[groups]", $groupContent, $authzContentPreg[0][0]), $authzContent);
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($group, $resultPreg[1])) {
                return '1';
            } else {
                $groupContent = "\n";
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($groupArray as $groupStr => $userList) {
                    $groupContent .= $groupStr . '=';
                    $userArray = explode(',', $userList);
                    array_walk($userArray, 'ArrayValueTrim');
                    $groupContent .=  implode(',', $userArray) . "\n";
                }
                $groupContent .= "$group=\n";
                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            }
        }
    } else {
        return '0';
    }
}

/**
 * 从所有仓库路径和分组下修改分组名称
 *
 * @param string $authzContent
 * @param string $oldGroup
 * @param string $newGroup
 * @param string $rootPath
 * @return 
 * string       正常
 */
function FunUpdSvnGroup($authzContent, $oldGroup, $newGroup)
{
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (!empty(trim($authzContentPreg[1][0]))) {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($oldGroup, $resultPreg[1])) {
                $groupContent = "\n";
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($groupArray as $groupStr => $userList) {
                    if ($groupStr == $oldGroup) {
                        $groupStr = $newGroup;
                    }
                    $groupContent .= $groupStr . '=';
                    $userArray = explode(',', $userList);
                    array_walk($userArray, 'ArrayValueTrim');
                    $groupContent .=  implode(',', $userArray) . "\n";
                }
                $content1 .= $groupContent;
            }
        }
    }
    $content2 = preg_replace("/(^@" . $oldGroup . "[\s]*)=/m", "@$newGroup=", str_replace($authzContentPreg[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 从所有仓库路径和分组下删除分组名称
 *
 * @param string $authzContent
 * @param string $groupName
 * @param string $rootPath
 * @return 
 * string       正常
 */
function FunDelSvnGroup($authzContent, $groupName)
{
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (!empty(trim($authzContentPreg[1][0]))) {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "\n";
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($groupArray as $groupStr => $userList) {
                    if ($groupStr == $groupName) {
                        continue;
                    }
                    $groupContent .= $groupStr . '=';
                    $userArray = explode(',', $userList);
                    array_walk($userArray, 'ArrayValueTrim');
                    $groupContent .=  implode(',', $userArray) . "\n";
                }
                $content1 .= $groupContent;
            } else {
                $content1 =  $authzContentPreg[0][0];
            }
        }
    }
    $content2 = preg_replace("/(^@" . $groupName . "[\s]*=.*?)\n/m", "\n", str_replace($authzContentPreg[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 为分组添加用户
 * 
 * @param string $authzContent
 * @param string $group
 * @param string $user
 * @return 
 * 0        文件格式错误(不存在[groups]标识)
 * 1        分组不存在
 * 2        要添加的用户已存在该分组
 * string   正常
 */
function FunAddSvnGroupUser($authzContent, $group, $user)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($group, $resultPreg[1])) {
                $groupContent = "\n";
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($groupArray as $groupStr => $userList) {
                    $groupContent .= $groupStr . '=';
                    if (trim($userList) == '') {
                        $userArray = array();
                        if ($groupStr == $group) {
                            array_push($userArray, $user);
                        }
                    } else {
                        $userArray = explode(',', $userList);
                        array_walk($userArray, 'ArrayValueTrim');
                        if ($groupStr == $group) {
                            if (in_array($user, $userArray)) {
                                return 2;
                            }
                            array_push($userArray, $user);
                        }
                    }
                    $groupContent .=  implode(',', $userArray) . "\n";
                }
                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 从分组删除用户
 * 
 * @param string $authzContent
 * @param string $group
 * @param string $user
 * @return 
 * 0        文件格式错误
 * 1        分组不存在
 * 2        要删除的用户不在该分组
 * string   正常
 */
function FunDelSvnGroupUser($authzContent, $group, $user)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($group, $resultPreg[1])) {
                $groupContent = "\n";
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($groupArray as $groupStr => $userList) {
                    $groupContent .= $groupStr . '=';
                    $userArray = explode(',', $userList);
                    array_walk($userArray, 'ArrayValueTrim');
                    if ($groupStr == $group) {
                        if (in_array($user, $userArray)) {
                            unset($userArray[array_search($user, $userArray)]);
                        } else {
                            return 2;
                        }
                    }
                    $groupContent .=  implode(',', $userArray) . "\n";
                }
                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            } {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个分组下的所有用户列表
 * 
 * @param string $authzContent
 * @param string $group
 * @return 
 * 0                文件格式错误
 * 1                分组不存在
 * array('u1','u2') 正常
 */
function FunGetSvnUserListByGroup($authzContent, $group)
{
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            if (in_array($group, $resultPreg[1])) {
                $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
                $userArray = explode(',', $groupArray[$group]);
                array_walk($userArray, 'ArrayValueTrim');
                return $userArray;
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个仓库路径下有权限的用户列表
 * 
 * @param string $authzContent
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0    不存在该仓库路径的记录
 * 1    记录为空
 */
function FunGetRepUserList($authzContent, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            return $resultPreg[1];
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个仓库路径下有权限的用户列表以及对应的权限
 * 
 * @param string $authzContent
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0                            不存在该仓库路径的记录
 * array('u1'=>'rw','u2'=>'r')  正常
 */
function FunGetRepUserPriList($authzContent, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
            return $groupArray;
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个仓库路径下有权限的分组列表
 * 
 * @param string $authzContent
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0                不存在该仓库路径的记录
 * array('g1','g2') 正常
 */
function FunGetRepGroupList($authzContent, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            return $resultPreg[1];
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个仓库路径下有权限的分组列表以及对应的权限
 * 
 * @param string $authzContent
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0                            不存在该仓库路径的记录
 * array('g1'=>'rw','g2'=>'r')  正常
 */
function FunGetRepGroupPriList($authzContent, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return null;
        } else {
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
            return $groupArray;
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
 * @param string $authzContent
 * @param string $user
 * @param string $privilege
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * string   正常 
 */
function FunSetRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            //添加用户
            if (trim($privilege) != '') {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n$user=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            //处理分组
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            if (in_array($user, $resultPreg[1])) {
                //编辑
                $userContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    if ($userStr == $user) {
                        if ($privilege == "") {
                            continue;
                        } else {
                            $userContent .= "$userStr=$privilege\n";
                        }
                    } else {
                        $userContent .= "$userStr=$userPri\n";
                    }
                }

                return str_replace($authzContentPreg[1][0], $userContent, $authzContent);
            } else {
                //新增

                $userContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                if ($privilege == "") {
                } else {
                    $userContent .= "$user=$privilege\n";
                }
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    $userContent .= "$userStr=$userPri\n";
                }
                return str_replace($authzContentPreg[1][0], $userContent, $authzContent);
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
 * @param string $authzContent
 * @param string $user
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * 1        已删除
 * string   正常
 */
function FunDelRepUserPri($authzContent, $user, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            //处理分组
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            if (in_array($user, $resultPreg[1])) {
                //删除
                $userContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    if ($userStr == $user) {
                        continue;
                    }
                    $userContent .= "$userStr=$userPri\n";
                }

                return str_replace($authzContentPreg[1][0], $userContent, $authzContent);
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
 * @param string $authzContent
 * @param string $user
 * @param string $privilege
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * string   正常
 */
function FunUpdRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != '') {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n$user=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            //处理分组
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            //编辑
            $userContent = "\n";

            //处理分组
            $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
            foreach ($groupArray as $groupStr => $groupPri) {
                $userContent .= "@$groupStr=$groupPri\n";
            }

            //处理用户
            $userArray = array_combine($resultPreg[1], $resultPreg[2]);
            foreach ($userArray as $userStr => $userPri) {
                if ($userStr == $user) {
                    if ($privilege == "") {
                        continue;
                    } else {
                        $userContent .= "$userStr=$privilege\n";
                        continue;
                    }
                } else {
                    $userContent .= "$userStr=$userPri\n";
                }
            }
            return str_replace($authzContentPreg[1][0], $userContent, $authzContent);
        }
    } else {
        return '0';
    }
}

/**
 * 为某个仓库路径设置分组权限
 * 包括为已有权限的分组修改权限
 * 包括为没有权限的分组增加权限
 * 其中如果分组和用户都设置了权限 但是权限一个为可读 一个为可写 应该遵循什么规则呢
 * 
 * @param string $authzContent
 * @param string $group
 * @param string $privilege
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * string   正常
 */
function FunSetRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != "") {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            //处理用户
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    if ($groupStr == $group) {
                        if ($privilege == "") {
                            continue;
                        } else {
                            $groupContent .= "@$group=$privilege\n";
                        }
                    } else {
                        $groupContent .= "@$groupStr=$groupPri\n";
                    }
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    $groupContent .= "$userStr=$userPri\n";
                }

                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            } else {
                //新增
                $groupContent = "\n";

                //处理分组
                if ($privilege == "") {
                } else {
                    $groupContent .= "@$group=$privilege\n";
                }
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    $groupContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    $groupContent .= "$userStr=$userPri\n";
                }

                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            }
        }
    } else {
        return '0';
    }
}

/**
 * 删除某个仓库的分组权限
 * 
 * @param string $authzContent
 * @param string $group
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * 1        已删除
 * string   正常
 */
function FunDelRepGroupPri($authzContent, $group, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            //处理用户
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    if ($groupStr == $group) {
                        continue;
                    } else {
                        $groupContent .= "@$groupStr=$groupPri\n";
                    }
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    $groupContent .= "$userStr=$userPri\n";
                }

                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
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
 * @param string $authzContent
 * @param string $group
 * @param string $privilege
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 0        不存在该仓库路径的记录
 * 1        该仓库下不存在该分组
 * string   正常
 */
function FunUpdRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != "") {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有用户数据
            preg_match_all("/^@([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'ArrayValueTrim');
            array_walk($resultPregGroup[2], 'ArrayValueTrim');

            //处理用户
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

                //处理分组
                $groupArray = array_combine($resultPregGroup[1], $resultPregGroup[2]);
                foreach ($groupArray as $groupStr => $groupPri) {
                    if ($groupStr == $group) {
                        if ($privilege == "") {
                            continue;
                        } else {
                            $groupContent .= "@$group=$privilege\n";
                        }
                    } else {
                        $groupContent .= "@$groupStr=$groupPri\n";
                    }
                }

                //处理用户
                $userArray = array_combine($resultPreg[1], $resultPreg[2]);
                foreach ($userArray as $userStr => $userPri) {
                    $groupContent .= "$userStr=$userPri\n";
                }

                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个用户有权限的所有仓库列表以及对应的权限
 *
 * @param string $authzContent
 * @param string $user
 * @param string $rootPath
 * @return 
 * null                             无记录
 * array('rep1'=>'r','rep2'=>'rw')  正常
 */
function FunGetUserPriRepList($authzContent, $user)
{
    preg_match_all("/^\[(.*?):(.*?)\][A-za-z0-9_=@\s]*?" . $user . "[\s]*=[\s]*([rw]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'ArrayValueTrim');
        array_walk($authzContentPreg[3], 'ArrayValueTrim');
        return array_combine($authzContentPreg[1], $authzContentPreg[3]);
    } else {
        return null;
    }
}

/**
 * 获取某个用户有权限的所有仓库列表
 *
 * @param string $authzContent
 * @param string $user
 * @param string $rootPath
 * @return 
 * null                   无记录
 * array('rep1','rep2'')  正常
 */
function FunGetUserPriRepListWithoutPri($authzContent, $user)
{
    preg_match_all("/^\[(.*?):(.*?)\][A-za-z0-9_=@\s]*?" . $user . "[\s]*=[\s]*([rw]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'ArrayValueTrim');
        return $authzContentPreg[1];
    } else {
        return null;
    }
}

/**
 * 获取某个分组有权限的仓库列表以及对应的权限
 *
 * @param string $authzContent
 * @param string $group
 * @param string $rootPath
 * @return 
 * null                             无记录
 * array('rep1'=>'r','rep2'=>'rw')  正常
 */
function FunGetGroupPriRepList($authzContent, $group)
{
    preg_match_all("/^\[(.*?):(.*?)\][A-za-z0-9_=@\s]*?" . "@$group" . "[\s]*=[\s]*([rw]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'ArrayValueTrim');
        array_walk($authzContentPreg[3], 'ArrayValueTrim');
        return array_combine($authzContentPreg[1], $authzContentPreg[3]);
    } else {
        return null;
    }
}

/**
 * 获取某个分组有权限的仓库列表
 *
 * @param string $authzContent
 * @param string $group
 * @param string $rootPath
 * @return 
 * null                  无记录
 * array('rep1','rep2')  正常
 */
function FunGetGroupPriRepListWithoutPri($authzContent, $group)
{
    preg_match_all("/^\[(.*?):(.*?)\][A-za-z0-9_=@\s]*?" . "@$group" . "[\s]*=[\s]*([rw]*)/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'ArrayValueTrim');
        return $authzContentPreg[1];
    } else {
        return null;
    }
}

/**
 * 向配置文件写入仓库路径
 *
 * @param string $authzContent
 * @param string $repName
 * @param string $repPath
 * @param string $rootPath
 * @return 
 * 1        已存在
 * string   正常
 */
function FunSetRepAuthz($authzContent, $repName, $repPath)
{
    preg_match_all("/^\[$repName:" . str_replace('/', '\/', $repPath) . "\]/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        return '1';
    } else {
        return $authzContent . "\n[$repName:$repPath]\n";
    }
}

/**
 * 从配置文件删除指定仓库的所有路径
 *
 * @param string $authzContent
 * @param string $repName
 * @param string $rootPath
 * @return 
 * 
 * 1        已删除
 * string   正常
 */
function FunDelRepAuthz($authzContent, $repName)
{
    preg_match_all("/^\[$repName:.*\][\s\S][^\[]*/m", $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        foreach ($authzContentPreg[0] as $key => $value) {
            $authzContent = str_replace($value, "\n", $authzContent);
        }
        return $authzContent;
    } else {
        return '1';
    }
}

/**
 * 从配置文件修改仓库名称
 * 修改该仓库所有路径的仓库名称
 *
 * @param string $authzContent
 * @param string $oldRepName
 * @param string $newRepName
 * @param string $rootPath
 * @return 
 * 1        仓库不存在
 * string   正常
 */
function FunUpdRepAuthz($authzContent, $oldRepName, $newRepName)
{
    preg_match_all("/^\[$oldRepName:(.*?)\]/m", $authzContent, $authzContentPreg);
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
 * 从所有仓库路径和分组下删除用户
 * 从所有分组中删除用户
 * 从所有仓库下删除用户
 * 
 * @param string $authzContent
 * @param string $username
 * @param string $rootPath
 * @return 
 * 1        用户不存在
 * string   正常
 */
function FunDelUserAuthz($authzContent, $username)
{
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all("/^\[groups\]([\s\S][^\[]*)/m", $authzContent, $authzContentPreg1);
    if (array_key_exists(0, $authzContentPreg1[0])) {
        if (!empty(trim($authzContentPreg1[1][0]))) {
            preg_match_all("/^([A-Za-z0-9_\s]*[^\s])\s*=(.*)/m", $authzContentPreg1[1][0], $resultPreg);
            array_walk($resultPreg[1], 'ArrayValueTrim');
            array_walk($resultPreg[2], 'ArrayValueTrim');
            $groupContent = "\n";
            $groupArray = array_combine($resultPreg[1], $resultPreg[2]);
            foreach ($groupArray as $groupStr => $userList) {
                $groupContent .= $groupStr . '=';
                $userArray = explode(',', $userList);
                array_walk($userArray, 'ArrayValueTrim');
                if (in_array($username, $userArray)) {
                    unset($userArray[array_search($username, $userArray)]);
                }
                $groupContent .=  implode(',', $userArray) . "\n";
            }
            $content1 .=  $groupContent;
        }
    }
    $content2 = preg_replace("/([^@]^$username" . "[\s]*=.*?)\n/m", "\n", str_replace($authzContentPreg1[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 从所有仓库路径和分组下修改用户名
 *
 * @param string $authzContent
 * @param string $oldUsername
 * @param string $newUsername
 * @param string $rootPath
 * @return 
 * string       正常
 */
function FunUpdUserAuthz($authzContent, $oldUsername, $newUsername)
{
}