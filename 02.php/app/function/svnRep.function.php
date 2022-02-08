<?php

declare(strict_types=1);

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
function FunGetRepUserListWithoutPri($authzContent, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
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
function FunGetRepUserListWithPri($authzContent, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
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
function FunGetRepGroupListWithoutPri($authzContent, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPreg);
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
function FunGetRepGroupListWithPri($authzContent, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPreg);
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
function FunSetRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
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
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            //处理分组
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            if (in_array($user, $resultPreg[1])) {
                //编辑
                $userContent = "\n";

                //处理分组
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                    $userContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                if ($privilege == "") {
                } else {
                    $userContent .= "$user=$privilege\n";
                }
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
 * 0        不存在该仓库路径的记录
 * 1        已删除
 * string   正常
 */
function FunDelRepUserPri($authzContent, $user, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            //进一步判断有没有用户数据
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            //处理分组
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            if (in_array($user, $resultPreg[1])) {
                //删除
                $userContent = "\n";

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
 * 0        不存在该仓库路径的记录
 * string   正常
 */
function FunUpdRepUserPri($authzContent, $user, $privilege, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != '') {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n$user=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有用户数据
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            //处理分组
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            //编辑
            $userContent = "\n";

            //处理分组
            foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                $userContent .= "@$groupStr=$groupPri\n";
            }

            //处理用户
            foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
 * 0        不存在该仓库路径的记录
 * string   正常
 */
function FunSetRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != "") {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有分组数据
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            //处理用户
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

                //处理分组
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
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
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
                    $groupContent .= "@$groupStr=$groupPri\n";
                }

                //处理用户
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
 * 0        不存在该仓库路径的记录
 * 1        已删除
 * string   正常
 */
function FunDelRepGroupPri($authzContent, $group, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            return '1';
        } else {
            //进一步判断有没有分组数据
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            //处理用户
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

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
 * 0        不存在该仓库路径的记录
 * 1        该仓库下不存在该分组
 * string   正常
 */
function FunUpdRepGroupPri($authzContent, $group, $privilege, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        if (empty(trim($authzContentPreg[1][0]))) {
            if (trim($privilege) != "") {
                return str_replace($authzContentPreg[0][0], "\n[$repName:$repPath]\n@$group=$privilege\n", $authzContent);
            } else {
                return $authzContent;
            }
        } else {
            //进一步判断有没有分组数据
            preg_match_all(REG_AUTHZ_GROUP_PRI, $authzContentPreg[1][0], $resultPregGroup);
            array_walk($resultPregGroup[1], 'FunArrayValueTrim');
            array_walk($resultPregGroup[2], 'FunArrayValueTrim');

            //处理用户
            preg_match_all(REG_AUTHZ_USER_PRI, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');

            if (in_array($group, $resultPregGroup[1])) {
                //编辑
                $groupContent = "\n";

                //处理分组
                foreach (array_combine($resultPregGroup[1], $resultPregGroup[2]) as $groupStr => $groupPri) {
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
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $userStr => $userPri) {
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
 * 向配置文件写入仓库路径
 *
 * 1        已存在
 * string   正常
 */
function FunSetRepAuthz($authzContent, $repName, $repPath)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_SPECIAL_PATH_WITHOUT_CON, $repName, str_replace('/', '\/', $repPath)), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        return '1';
    } else {
        return $authzContent . "\n[$repName:$repPath]\n";
    }
}

/**
 * 从配置文件删除指定仓库的所有路径
 *
 * 1        已删除
 * string   正常
 */
function FunDelRepAuthz($authzContent, $repName)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_ALL_PATH_WITH_CON, $repName), $authzContent, $authzContentPreg);
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
 * 从配置文件修改仓库名称
 * 修改该仓库所有路径的仓库名称
 * 
 * 没有校验要修改的仓库是否已经存在 需要上层函数进行校验
 *
 * 1        仓库不存在
 * string   正常
 */
function FunUpdRepAuthz($authzContent, $oldRepName, $newRepName)
{
    preg_match_all(sprintf(REG_AUTHZ_REP_ALL_PATH_WITHOUT_CON, $oldRepName), $authzContent, $authzContentPreg);
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
function FunGetAllHavePriRepListWithPri($authzContent)
{
    $userName = '*';
    preg_match_all(sprintf(REG_AUTHZ_ALL_HAVE_PRI_REPS, $userName), $authzContent, $authzContentPreg);
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
function FunGetAllHavePriRepListWithoutPri($authzContent)
{
    $userName = '*';
    preg_match_all(sprintf(REG_AUTHZ_ALL_HAVE_PRI_REPS, $userName), $authzContent, $authzContentPreg);
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
function FunGetAllNoPriRepListWithoutPri($authzContent)
{
    $userName = '*';
    preg_match_all(sprintf(REG_AUTHZ_ALL_NO_PRI_REPS, $userName), $authzContent, $authzContentPreg);
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
function FunGetRepList()
{
    $repArray = [];
    $file_arr = scandir(SVN_REPOSITORY_PATH);
    foreach ($file_arr as $file_item) {
        if ($file_item != '.' && $file_item != '..') {
            if (is_dir(SVN_REPOSITORY_PATH . '/' . $file_item)) {
                $file_arr2 = scandir(SVN_REPOSITORY_PATH . '/' . $file_item);
                foreach ($file_arr2 as $file_item2) {
                    if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                        array_push($repArray, array(
                            'repName' => $file_item,
                            'repUrl' => SVN_REPOSITORY_PATH . '/' . $file_item,
                            'repSize' => round(FunGetDirSize(SVN_REPOSITORY_PATH . '/' . $file_item) / (1024 * 1024), 2),
                            'repCheckoutUrl' => 'svn://' . SERVER_DOMAIN . '/' . $file_item,
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
function FunGetSimpleRepList()
{
    $repArray = [];
    $file_arr = scandir(SVN_REPOSITORY_PATH);
    foreach ($file_arr as $file_item) {
        if ($file_item != '.' && $file_item != '..') {
            if (is_dir(SVN_REPOSITORY_PATH . '/' . $file_item)) {
                $file_arr2 = scandir(SVN_REPOSITORY_PATH . '/' . $file_item);
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
function FunInitRepStruct($repName, $initUser = "SVNAdmin", $initPass = "SVNAdmin")
{
    $randPrefix = FunGetRandStr();
    $tempPath = TEMP_PATH . '/' . $randPrefix;
    $repPath = SVN_REPOSITORY_PATH . '/' . $repName;

    $tempMkdirCmd = sprintf("mkdir -p %s", $tempPath);
    $svnCheckoutCmd =  sprintf("svn checkout file:///%s --quiet --username %s --password %s %s", $repPath, $initUser, $initPass, $tempPath);
    $repMkdirCmd = sprintf("mkdir -p %s/{trank/,branches/,tags/}", $tempPath);
    $svnAddCmd = sprintf("svn add --quiet --username %s --password %s %s/*", $initUser, $initPass, $tempPath);
    $svnCommitCmd = sprintf("svn commit --quiet --username %s --password %s --message 'Initial structure' %s/*", $initUser, $initPass, $tempPath);
    $delDirCmd = sprintf("rm -rf %s", $tempPath);

    $cmd = implode(';', [$tempMkdirCmd, $svnCheckoutCmd, $repMkdirCmd, $svnAddCmd, $svnCommitCmd, $delDirCmd]);

    FunRequestReplyExec($cmd);
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
 */
function FunGetRepInfo($repName)
{
    $repPath = SVN_REPOSITORY_PATH . '/' . $repName;

    $svnadminInfoCmd = sprintf('svnadmin info %s', $repPath);

    $cmdResult = FunRequestReplyExec($svnadminInfoCmd);

    preg_match_all(REG_REP_INFO, $cmdResult, $svnadminInfoPreg);

    $svnadminInfoResult =  array_combine($svnadminInfoPreg[1], $svnadminInfoPreg[2]);

    unset($svnadminInfoResult['Path'], $svnadminInfoResult['Configuration File']);

    return $svnadminInfoResult;
}

/**
 * 使用 svnlook tree $repPath 获取仓库的最新版本的目录树
 */
function FunGetRepTree($repName)
{
    $repPath = SVN_REPOSITORY_PATH . '/' . $repName;
    $svnadminInfoCmd = sprintf('svnlook tree %s', $repPath);
    $cmdResult = FunRequestReplyExec($svnadminInfoCmd);
    // $cmdResult = shell_exec($svnadminInfoCmd);
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
            'children' => FunGetFolderStruct($complateArray, 0)
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
function FunGetFolderStruct($complateArray, $fileIndex)
{
    //获取当前文件夹的目录内容 以子文件夹和子文件下标的形式返回
    $dirContent = FunGetFolderDirAndFiles($complateArray, $fileIndex);

    //按照特定结构拼接
    $result = [];
    foreach ($dirContent as $key => $value) {
        $children = FunGetFolderStruct($complateArray, $value);
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
function FunGetFolderDirAndFiles($complateArray, $fileIndex)
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



