<?php

//declare(strict_types=1);

/**
 * 添加分组
 * 
 * 0                文件格式错误(不存在[groups]标识)
 * 1                分组已存在
 * string           正常
 */
function FunAddSvnGroup($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            $groupContent = "[groups]\n$groupName=\n";
            return str_replace($authzContentPreg[0][0], str_replace("[groups]", $groupContent, $authzContentPreg[0][0]), $authzContent);
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                return '1';
            } else {
                $groupContent = "\n";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
                }
                $groupContent .= "$groupName=\n";
                return str_replace($authzContentPreg[1][0], $groupContent, $authzContent);
            }
        }
    } else {
        return '0';
    }
}

/**
 * 删除分组（从所有仓库路径和分组下删除分组名称）
 * 
 * 操作后会将[aliases]的位置放于[groups]之后
 *
 * 0            文件格式错误(不存在[groups]标识)
 * 1            分组不存在
 * string       正常
 */
function FunDelSvnGroup($authzContent, $groupName)
{
    $groupName = trim($groupName);
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (!empty($temp1)) {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    //处理左值
                    if ($groupStr == $groupName) {
                        continue;
                    }
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    //处理右值
                    foreach ($userGroupArray as $key => $value) {
                        if ($value == "@$groupName") {
                            unset($userGroupArray[$key]);
                            break;
                        }
                    }
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
                }
                $content1 .= $groupContent;
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
    $content2 = preg_replace("/(^@" . $groupName . "[\s]*=.*?)\n/m", "", str_replace($authzContentPreg[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 修改分组（从所有仓库路径和分组下修改分组名称）
 * 
 * 操作后会将[aliases]的位置放于[groups]之后
 *
 * 0            文件格式错误(不存在[groups]标识)
 * string       正常
 */
function FunUpdSvnGroup($authzContent, $oldGroup, $newGroup)
{
    $oldGroup = trim($oldGroup);
    $newGroup = trim($newGroup);
    $content1 = "[groups]\n";
    $content2 = "";
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (!empty($temp1)) {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            $groupContent = "";
            foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                //处理左值
                if ($groupStr == $oldGroup) {
                    $groupStr = $newGroup;
                }
                $groupContent .= "$groupStr=";
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, 'FunArrayValueTrim');
                //处理右值
                foreach ($userGroupArray as $key => $value) {
                    if ($value == "@$oldGroup") {
                        $userGroupArray[$key] = "@$newGroup";
                        break;
                    }
                }
                $groupContent .=  implode(',', $userGroupArray) . "\n";
            }
            $content1 .= $groupContent;
        }
    } else {
        return '0';
    }
    $content2 = preg_replace("/(^@" . $oldGroup . "[\s]*)=/m", "@$newGroup=", str_replace($authzContentPreg[0][0], '', $authzContent));
    return $content1 . $content2;
}

/**
 * 获取分组列表
 * 
 * 文件格式错误(不存在[groups]标识)
 * 0
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => group1
 *     [1] => group2
 * )
 */
function FunGetSvnGroupList($authzContent)
{
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            return $resultPreg[1];
        }
    } else {
        return '0';
    }
}

/**
 * 获取某个分组所在的分组列表
 * 
 * 文件格式错误(不存在[groups]标识)
 * 0
 * 
 * 分组不存在
 * 1
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => group1
 *     [1] => group2
 * )
 */
function FunGetSvnGroupGroupList($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupArray = [];
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    if (in_array("@$groupName", $userGroupArray)) {
                        array_push($groupArray, $groupStr);
                    }
                }
                return $groupArray;
            } else {
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
 * 文件格式错误(不存在[groups]标识)
 * 0
 * 
 * 分组不存在
 * 1
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => user1
 *     [1] => user2
 * )
 */
function FunGetSvnUserListByGroup($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $userGroupStr = array_combine($resultPreg[1], $resultPreg[2])[$groupName];
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, 'FunArrayValueTrim');
                $userArray = [];
                foreach ($userGroupArray as $key => $value) {
                    if (substr($value, 0, 1) != '@') {
                        array_push($userArray, $value);
                    }
                }
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
 * 获取某个分组下的所有分组列表
 * 
 * 文件格式错误(不存在[groups]标识)
 * 0
 * 
 * 分组不存在
 * 1
 * 
 * 空列表
 * []
 * 
 * 正常数据
 * Array
 * (
 *     [0] => group1
 *     [1] => group2
 * )
 */
function FunGetSvnGroupListByGroup($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $userGroupStr = array_combine($resultPreg[1], $resultPreg[2])[$groupName];
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, 'FunArrayValueTrim');
                $groupArray = [];
                foreach ($userGroupArray as $key => $value) {
                    if (substr($value, 0, 1) == '@') {
                        array_push($groupArray, substr($value, 1));
                    }
                }
                return $groupArray;
            } else {
                return '1';
            }
        }
    } else {
        return '0';
    }
}

/**
 * 获取分组列表以及每个分组包含的用户列表和分组列表
 * 
 * 文件格式错误(不存在[groups]标识)
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
 *             [groupName] => commonAdmin
 *             [include] => Array
 *                 (
 *                     [users] => Array
 *                         (
 *                             [0] => Hachi
 *                             [1] => taosir
 *                         )
 *                     [groups] => Array
 *                         (
 *                         )
 *                 )
 *         )
 *     [1] => Array
 *         (
 *             [groupName] => g1
 *             [include] => Array
 *                 (
 *                     [users] => Array
 *                         (
 *                             [0] => Hachi
 *                         )
 *                     [groups] => Array
 *                         (
 *                             [0] => superAdmin
 *                             [1] => commonAdmin
 *                         )
 *                 )
 *         )
 * )
 */
function FunGetSvnGroupUserAndGroupList($authzContent)
{
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return [];
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            $result = [];
            foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                $userArray = [];
                $groupArray = [];
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr); //解决 key=value 中 value 为空的匹配的bug
                array_walk($userGroupArray, 'FunArrayValueTrim');
                foreach ($userGroupArray as $key => $value) {
                    substr($value, 0, 1) == '@' ? array_push($groupArray, substr($value, 1, strlen($value) - 1)) : array_push($userArray, $value);
                }
                array_push($result, [
                    'groupName' => $groupStr,
                    'include' => [
                        'users' => $userArray,
                        'groups' => $groupArray
                    ]
                ]);
            }
            return $result;
        }
    } else {
        return '0';
    }
}

/**
 * 为分组添加用户
 * 
 * 0        文件格式错误(不存在[groups]标识)
 * 1        分组不存在
 * 2        要添加的用户已存在该分组
 * string   正常
 */
function FunAddSvnGroupUser($authzContent, $groupName, $userName)
{
    $groupName = trim($groupName);
    $userName = trim($userName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "\n";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    if ($groupStr == $groupName) {
                        if (in_array($userName, $userGroupArray)) {
                            return '2';
                        }
                        array_push($userGroupArray, $userName);
                    }
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
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
 * 0        文件格式错误(不存在[groups]标识)
 * 1        分组不存在
 * 2        要删除的用户不在该分组
 * string   正常
 */
function FunDelSvnGroupUser($authzContent, $groupName, $userName)
{
    $groupName = trim($groupName);
    $userName = trim($userName);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "\n";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    if ($groupStr == $groupName) {
                        if (in_array($userName, $userGroupArray)) {
                            unset($userGroupArray[array_search($userName, $userGroupArray)]);
                        } else {
                            return '2';
                        }
                    }
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
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
 * 为分组添加分组
 * 
 * 0        文件格式错误(不存在[groups]标识)
 * 1        分组不存在
 * 2        要添加的分组已存在该分组
 * 3        不能添加本身
 * string   正常
 */
function FunAddSvnGroupGroup($authzContent, $groupName, $groupName2)
{
    $groupName = trim($groupName);
    $groupName2 = trim($groupName2);
    if ($groupName == $groupName2) {
        return '3';
    }
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "\n";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    if ($groupStr == $groupName) {
                        if (in_array("@$groupName2", $userGroupArray)) {
                            return '2';
                        }
                        array_push($userGroupArray, "@$groupName2");
                    }
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
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
 * 从分组删除分组
 * 
 * 0        文件格式错误(不存在[groups]标识)
 * 1        分组不存在
 * 2        要删除的分组不在该分组
 * string   正常
 */
function FunDelSvnGroupGroup($authzContent, $groupName, $groupName2)
{
    $groupName = trim($groupName);
    $groupName2 = trim($groupName2);
    preg_match_all(REG_AUTHZ_GROUP_WITH_CON, $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[0])) {
        $temp1 = trim($authzContentPreg[1][0]);
        if (empty($temp1)) {
            return '1';
        } else {
            preg_match_all(REG_AUTHZ_GROUP_KEY_VALUE, $authzContentPreg[1][0], $resultPreg);
            array_walk($resultPreg[1], 'FunArrayValueTrim');
            array_walk($resultPreg[2], 'FunArrayValueTrim');
            if (in_array($groupName, $resultPreg[1])) {
                $groupContent = "\n";
                foreach (array_combine($resultPreg[1], $resultPreg[2]) as $groupStr => $userGroupStr) {
                    $groupContent .= "$groupStr=";
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, 'FunArrayValueTrim');
                    if ($groupStr == $groupName) {
                        if (in_array("@$groupName2", $userGroupArray)) {
                            unset($userGroupArray[array_search("@$groupName2", $userGroupArray)]);
                        } else {
                            return '2';
                        }
                    }
                    $groupContent .=  implode(',', $userGroupArray) . "\n";
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
 * 获取某个分组有权限的仓库列表
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
function FunGetGroupPriRepListWithoutPri($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(sprintf(REG_AUTHZ_GROUP_PRI_REPS, $groupName), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'FunArrayValueTrim');
        return $authzContentPreg[1];
    } else {
        return [];
    }
}

/**
 * 获取某个分组有权限的仓库列表以及对应的权限
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
function FunGetGroupPriRepListWithPri($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(sprintf(REG_AUTHZ_GROUP_PRI_REPS, $groupName), $authzContent, $authzContentPreg);
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
 * 获取某个分组有权限的仓库列表以及对应的权限
 */
function FunGetGroupPriRepListWithPriAndPath($authzContent, $groupName)
{
    $groupName = trim($groupName);
    preg_match_all(sprintf(REG_AUTHZ_GROUP_PRI_REPS, $groupName), $authzContent, $authzContentPreg);
    if (array_key_exists(0, $authzContentPreg[1])) {
        array_walk($authzContentPreg[1], 'FunArrayValueTrim');
        array_walk($authzContentPreg[2], 'FunArrayValueTrim');
        array_walk($authzContentPreg[3], 'FunArrayValueTrim');
        $result = [];
        foreach ($authzContentPreg[1] as $key => $value) {
            array_push($result, [
                'repName' => $value,
                'priPath' => $authzContentPreg[2][$key],
                'repPri' => $authzContentPreg[3][$key],
                'unique' => $value . ':' . $authzContentPreg[2][$key] . $authzContentPreg[3][$key]
            ]);
        }
        return $result;
    } else {
        return [];
    }
}

// require_once '/var/www/html/config/reg.config.php';
// require_once '/var/www/html/app/function/array.function.php';

// $authzContent = file_get_contents('/home/svnadmin/authz');
// $passwd = file_get_contents('/home/svnadmin/passwd');

// print_r(FunAddSvnGroup($authzContent, 'group'));
// print_r(FunDelSvnGroup($authzContent, 'group1'));
// print_r(FunUpdSvnGroup($authzContent, 'group1', 'group33'));
// print_r(FunGetSvnGroupList($authzContent));
// print_r(FunGetSvnGroupGroupList($authzContent, 'group4'));
// print_r(FunGetSvnUserListByGroup($authzContent, 'group1'));
// print_r(FunGetSvnGroupListByGroup($authzContent, 'group2'));
// print_r(FunGetSvnGroupUserAndGroupList($authzContent));
// print_r(FunAddSvnGroupUser($authzContent, 'group1', 'user'));
// print_r(FunDelSvnGroupUser($authzContent, 'group2', 'user2'));
// print_r(FunAddSvnGroupGroup($authzContent, 'group1', 'group2'));
// print_r(FunDelSvnGroupGroup($authzContent, 'group1', 'group'));
// print_r(FunGetGroupPriRepListWithoutPri($authzContent, 'group4'));
// print_r(FunGetGroupPriRepListWithPri($authzContent, 'group4'));
