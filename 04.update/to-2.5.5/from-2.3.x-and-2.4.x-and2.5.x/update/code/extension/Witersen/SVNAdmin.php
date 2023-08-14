<?php

/*
 * @Author: www.witersen.com
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace Witersen;

class SVNAdmin
{
    /**
     * 文件内容又不能设置唯一值 因此总会出现存在两个用户、分组、别名、仓库路径等的情况 要扫描并处理 todo
     * 很多操作如为仓库路径授权 考虑是否直接在没有扫描到路径的时候不返回错误码而是直接增加一条该仓库的记录 todo
     * 分组节中不存在但是仓库路径中存在的分组情况需要处理 以什么形式处理呢 todo
     * 一个函数修改多个类型的对象的情况 要注意在不同的节和仓库路径下修改 在节中左值没有符号 在节中右值有符号 在仓库路径下有符号 todo
     * 替换操作 不仅要检查新值是否冲突 还要检查旧值是否存在 todo
     */

    /**
     * 函数错误码说明
     * 
     * 0 未知错误
     * 
     * num PCRE正则抛出错误(具体数值由 preg_last_error() 返回)
     * 
     * 6xx
     * ├─ 600 参数内容或格式错误
     * │  └─── 601 $objectName不能为空
     * ├─ 610 auth文件格式错误
     * │  ├─── 611 authz文件格式错误(不存在[aliases]标识)
     * │  └─── 612 authz文件格式错误(不存在[groups]标识)
     * └─ 620 passwd文件格式错误
     *    └───621 passwd文件格式错误(不存在[users]标识)
     *  
     * 7xx
     * ├─ 700 目标对象不存在
     * │  ├─── 701 仓库路径下不存在该对象的权限记录
     * │  └─── 703 要删除的对象不存在该分组
     * ├─ 710 用户不存在
     * ├─ 720 分组不存在
     * ├─ 730 别名不存在
     * │  └─── 731 要修改的别名不存在
     * ├─ 740 仓库不存在
     * └─ 750 仓库路径不存在
     *    └─── 751 不存在该仓库路径
     *    └─── 752 仓库路径需以/开始
     * 
     * 8xx
     * ├─ 800 目标对象已存在
     * │  ├─── 801 仓库路径下已经存在该对象的权限记录
     * │  ├─── 802 能添加相同名称的分组
     * │  └─── 803 要添加的对象已存在该分组
     * ├─ 810 用户已存在
     * │  └─── 811 要修改的新用户已经存在
     * ├─ 820 分组已存在
     * │  └─── 821 要修改的新分组已经存在
     * ├─ 830 别名已存在
     * │  └─── 831 要修改的新别名已经存在
     * ├─ 840 仓库已存在
     * └─ 850 仓库路径已存在
     *    └─── 851 已存在该仓库路径
     * 
     * 9xx
     * └─ 900 参数类型错误
     *    ├─── 901 不支持的授权对象类型
     *    └─── 902 不支持的操作类型
     * 
     */

    /**
     * @var string 禁用用户前缀
     */
    private $reg_1 = '#disabled#';

    /**
     * @var string 匹配指定节及其内容
     */
    private $reg_2 = "/^[ \t]*\[%s\](((?!\n[ \t]*\[)[\s\S])*)/m";

    /**
     * @var string 匹配 %s=[rw] 形式
     */
    private $reg_3 = "/^[ \t]*(%s)[ \t]*=[ \t]*([rw]%s)[ \t]*$/m";

    /**
     * @var string 匹配 %skey=[rw] 形式
     */
    private $reg_4 = "/^[ \t]*%s([A-Za-z0-9-_.一-龥]+)[ \t]*=[ \t]*([rw]%s)[ \t]*$/m";

    /**
     * @var string 匹配 %s=value 形式
     */
    private $reg_5 = "/^[ \t]*(%s)[ \t]*=[ \t]*(.*)[ \t]*$/m";

    /**
     * @var string 匹配 %s=value 形式
     */
    private $reg_8 = "/^[ \t]*(%s)[ \t]*:[ \t]*(.*)[ \t]*$/m";

    /**
     * @var string 匹配 %s= 形式
     */
    private $reg_6 = "/^[ \t]*(%s)[ \t]*=/m";

    /**
     * @var string 匹配仓库路径节及其内容
     */
    private $reg_7 = "/^[ \t]*(\[(.*):(.*)\])((?!\n[ \t]*\[)[\s\S])*\n[ \t]*%s[ \t]*=[ \t]*([rw]+)[ \t]*$/m";

    /**
     * @var array 授权类型与对应的前缀/内容关系
     */
    private $array_objectType = [
        'user' => '',
        'group' => '@',
        'aliase' => '&',
        '*' => '\*',
        '$authenticated' => '\$authenticated',
        '$anonymous' => '\$anonymous'
    ];

    function __construct()
    {
    }

    /**
     * 对数据每项键值进行trim操作
     *
     * @param string $value
     * @param string $key
     * @return void
     */
    private function ArrayValueTrim(&$value, $key)
    {
        $value = trim($value);
    }

    /**
     * 对数据每项键值进行去除 #disabled# 操作
     *
     * @param string $value
     * @param string $key
     * @return void
     */
    private function ArrayValueEnable(&$value, $key)
    {
        $REG_SVN_USER_DISABLED = '#disabled#';

        if (substr($value, 0, strlen($REG_SVN_USER_DISABLED)) == $REG_SVN_USER_DISABLED) {
            $value = substr($value, strlen($REG_SVN_USER_DISABLED));
        }
    }

    /**
     * 仓库
     * ---------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * 根据 是否反转、授权对象类型、授权对象名称 生成对应的匹配正则来用于匹配 $key = $value 的场景
     *
     * @param string $objectType    授权类型    user  group  aliase  * $authenticated $anonymous
     * @param boolean $invert       是否反转    true  false
     * @param string $objectName    授权名称    user1 group1 aliase1 * $authenticated $anonymous （为空表示不指定 $key 值）
     * @return array|int
     *
     * 901      不支持的授权对象类型
     * array    返回反转和非反转两种模式的 $key
     */
    private function GetReg($objectType, $invert = false, $objectName = '')
    {
        //参数检查
        if (!in_array($objectType, array_keys($this->array_objectType))) {
            return 901;
        }

        // * 无反转
        $invert = $objectName == '*' ? false : $invert;

        // 对象名称不应该包含反转符号~
        $objectName = substr($objectName, 0, 1) == '~' ? substr($objectName, 1) : $objectName;

        $invert = $invert ? '~' : '';

        if ($objectType == '*') {
            $normal = sprintf($this->reg_3, $this->array_objectType[$objectType], '*');
            return [
                'normal' => $normal,
                'noInvert' => $normal,
                'hasInvert' => $normal,
                'quote_normal' => $normal,
                'quote_noInvert' => $normal,
                'quote_hasInvert' => $normal,
            ];
        }

        if ($objectType == '$authenticated' || $objectType == '$anonymous') {
            return [
                'normal' => sprintf($this->reg_3, $invert . $this->array_objectType[$objectType], '*'),
                'noInvert' => sprintf($this->reg_3, $this->array_objectType[$objectType], '*'),
                'hasInvert' => sprintf($this->reg_3, '~' . $this->array_objectType[$objectType], '*'),
                'quote_normal' => sprintf($this->reg_3, $invert . $this->array_objectType[$objectType], '*'),
                'quote_noInvert' => sprintf($this->reg_3, $this->array_objectType[$objectType], '*'),
                'quote_hasInvert' => sprintf($this->reg_3, '~' . $this->array_objectType[$objectType], '*'),
            ];
        }

        return [
            'normal' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, $invert . $this->array_objectType[$objectType] . $objectName, '*'),
            'noInvert' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, $this->array_objectType[$objectType] . $objectName, '*'),
            'hasInvert' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, '~' . $this->array_objectType[$objectType] . $objectName, '*'),
            'quote_normal' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, $invert . $this->array_objectType[$objectType] . preg_quote($objectName), '*'),
            'quote_noInvert' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, $this->array_objectType[$objectType] . preg_quote($objectName), '*'),
            'quote_hasInvert' => sprintf($objectName == '' ? $this->reg_4 : $this->reg_3, '~' . $this->array_objectType[$objectType] . preg_quote($objectName), '*'),
        ];
    }

    /**
     * 根据 是否反转、授权对象类型、授权对象名称 生成 $key = $value 场景中的 $key
     *
     * @param string $objectType    授权类型    user  | group  | aliase  | * | $authenticated  | $anonymous
     * @param boolean $invert       是否反转    true  | false
     * @param string $objectName    授权名称    user1 | group1 | aliase1 | * | $authenticated  | $anonymous ($objectName 不能为空)
     * @return array|int
     *
     * 901      不支持的授权对象类型
     * 601      $objectName 不能为空
     * array    返回反转和非反转两种模式的 $key
     */
    private function GetKey($objectType, $invert = false, $objectName = '')
    {
        //参数检查
        if (!in_array($objectType, array_keys($this->array_objectType))) {
            return 901;
        }

        if ($objectName == '') {
            return 601;
        }

        // * 无反转
        $invert = $objectName == '*' ? false : $invert;

        // 对象名称不应该包含反转符号~
        $objectName = substr($objectName, 0, 1) == '~' ? substr($objectName, 1) : $objectName;

        $invert = $invert ? '~' : '';

        if ($objectType == '*') {
            return [
                'normal' => '*',
                'noInvert' => '*',
                'hasInvert' => '*',
                'quote_normal' => '*',
                'quote_noInvert' => '*',
                'quote_hasInvert' => '*'
            ];
        }

        if ($objectType == '$authenticated' || $objectType == '$anonymous') {
            return [
                'normal' => $invert . $objectName,
                'noInvert' => $objectName,
                'hasInvert' => '~' . $objectType,
                'quote_normal' => $invert . $objectName,
                'quote_noInvert' => $objectName,
                'quote_hasInvert' => '~' . $objectType,
            ];
        }

        return [
            'normal' => $invert . $this->array_objectType[$objectType] . $objectName,
            'noInvert' => $this->array_objectType[$objectType] . $objectName,
            'hasInvert' => '~' . $this->array_objectType[$objectType] . $objectName,
            'quote_normal' => $invert . $this->array_objectType[$objectType] . preg_quote($objectName),
            'quote_noInvert' => $this->array_objectType[$objectType] . preg_quote($objectName),
            'quote_hasInvert' => '~' . $this->array_objectType[$objectType] . preg_quote($objectName),
        ];
    }

    /**
     * 获取指定仓库路径下的对象的权限列表
     * 不指定对象则获取全部对象的权限列表
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @param string $objectType    授权对象    user  | group  | aliase  | * | $authenticated  | $anonymous
     * @return array|int
     *
     * 注意 * 不支持反转
     *
     * 751      不存在该仓库路径
     * 752      仓库路径需以/开始
     * 901      不支持的授权对象类型
     */
    public function GetRepPathPri($authzContent, $repName, $repPath, $objectType = '')
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        if ($objectType == '') {
            $regArray = [
                //针对 *
                [
                    'type' => '*',
                    'invert' => false,
                    'reg' => sprintf($this->reg_3, $this->array_objectType['*'], '*')
                ],

                //针对 $authenticated 和 有无反转
                [
                    'type' => '$authenticated',
                    'invert' => false,
                    'reg' => sprintf($this->reg_3, $this->array_objectType['$authenticated'], '*')
                ],
                [
                    'type' => '$authenticated',
                    'invert' => true,
                    'reg' => sprintf($this->reg_3, '~' . $this->array_objectType['$authenticated'], '*')
                ],

                //针对 $anonymous 和 有无反转
                [
                    'type' => '$anonymous',
                    'invert' => false,
                    'reg' => sprintf($this->reg_3, $this->array_objectType['$anonymous'], '*')
                ],
                [
                    'type' => '$anonymous',
                    'invert' => true,
                    'reg' => sprintf($this->reg_3, '~' . $this->array_objectType['$anonymous'], '*')
                ],

                //针对 其它 和 有无反转
                [
                    'type' => 'user',
                    'invert' => false,
                    'reg' => sprintf($this->reg_4, $this->array_objectType['user'], '*')
                ],
                [
                    'type' => 'user',
                    'invert' => true,
                    'reg' => sprintf($this->reg_4, '~' . $this->array_objectType['user'], '*')
                ],
                [
                    'type' => 'group',
                    'invert' => false,
                    'reg' => sprintf($this->reg_4, $this->array_objectType['group'], '*')
                ],
                [
                    'type' => 'group',
                    'invert' => true,
                    'reg' => sprintf($this->reg_4, '~' . $this->array_objectType['group'], '*')
                ],
                [
                    'type' => 'aliase',
                    'invert' => false,
                    'reg' => sprintf($this->reg_4, $this->array_objectType['aliase'], '*')
                ],
                [
                    'type' => 'aliase',
                    'invert' => true,
                    'reg' => sprintf($this->reg_4, '~' . $this->array_objectType['aliase'], '*')
                ],
            ];
        } else {
            //类型检查
            if (!in_array($objectType, array_keys($this->array_objectType))) {
                return 901;
            }
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                if ($objectType == '') {
                    $result = [];
                    foreach ($regArray as $reg) {
                        preg_match_all($reg['reg'], $authzContentPreg[1][0], $resultPreg);
                        if (preg_last_error() != 0) {
                            return preg_last_error();
                        }
                        foreach ($resultPreg[0] as $key => $value) {
                            $result[] = [
                                'objectType' => $reg['type'],
                                'objectName' => $resultPreg[1][$key],
                                'objectPri' => trim($resultPreg[2][$key]) == '' ? 'no' : $resultPreg[2][$key],
                                'invert' => $reg['invert']
                            ];
                        }
                    }

                    return $result;
                } else {
                    $regArray = $this->GetReg($objectType);
                    if (is_numeric($regArray)) {
                        return $regArray;
                    }

                    preg_match_all($regArray['noInvert'], $authzContentPreg[1][0], $resultPreg1);
                    if (preg_last_error() != 0) {
                        return preg_last_error();
                    }
                    preg_match_all($regArray['hasInvert'], $authzContentPreg[1][0], $resultPreg2);
                    if (preg_last_error() != 0) {
                        return preg_last_error();
                    }
                    $result = [];
                    foreach ($resultPreg1[0] as $key => $value) {
                        array_push($result, [
                            'objectType' => $objectType,
                            'objectName' => $resultPreg1[1][$key],
                            'objectPri' => trim($resultPreg1[2][$key]) == '' ? 'no' : $resultPreg1[2][$key],
                            'invert' => 0
                        ]);
                    }
                    foreach ($resultPreg2[0] as $key => $value) {
                        if ($objectType == '*') {
                            break;
                        }
                        array_push($result, [
                            'objectType' => $objectType,
                            'objectName' => $resultPreg2[1][$key],
                            'objectPri' => trim($resultPreg2[2][$key]) == '' ? 'no' : $resultPreg2[2][$key],
                            'invert' => 1
                        ]);
                    }

                    return $result;
                }
            }
        } else {
            return 751;
        }
    }

    /**
     * 为某仓库路径下增加权限
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @param string $objectType    授权对象    user  | group  | aliase  | * | $authenticated  | $anonymous
     * @param boolean $invert       是否反转    true  | false
     * @param string $objectName    授权名称    user1 | group1 | aliase1 | * | $authenticated  | $anonymous : 无需携带 @ &
     * @param string $privilege     权限        [rw]+
     * @return string|int
     *
     * 751      不存在该仓库路径
     * 752      仓库路径需以/开始
     * 901      不支持的授权对象类型
     * 801      仓库路径下已经存在该对象的权限记录
     * string   正常
     */
    public function AddRepPathPri($authzContent, $repName, $repPath, $objectType, $objectName, $privilege, $invert = false)
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        //$objectType 检查
        if (!in_array($objectType, array_keys($this->array_objectType))) {
            return 901;
        }

        //处理对象名称与反转关系
        $objectKey = $this->GetKey($objectType, $invert, $objectName);
        if (is_numeric($objectKey)) {
            return $objectKey;
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . $objectKey['normal'] . "=$privilege\n", $authzContent);
            } else {
                $regArray = $this->GetReg($objectType, $invert, $objectName);
                if (is_numeric($regArray)) {
                    return $regArray;
                }

                preg_match_all($regArray['quote_hasInvert'], $authzContentPreg[1][0], $resultPreg1);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                preg_match_all($regArray['quote_noInvert'], $authzContentPreg[1][0], $resultPreg2);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }

                //如果反转或者非反转有一个匹配则提示已存在并返回
                if (array_key_exists(0, $resultPreg1[0]) || array_key_exists(0, $resultPreg2[0])) {
                    return 801;
                }

                return str_replace($authzContentPreg[0][0], trim($authzContentPreg[0][0]) . "\n" . $objectKey['normal'] . "=$privilege\n", $authzContent);
            }
        } else {
            return 751;
        }
    }

    /**
     * 为某仓库路径下修改权限
     * 包括修改读写权限和修改反转
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @param string $objectType    授权对象    user  | group  | aliase  | * | $authenticated  | $anonymous
     * @param boolean $invert       是否反转    true  | false
     * @param string $objectName    授权名称    user1 | group1 | aliase1 | * | $authenticated  | $anonymous : 无需携带 @ &
     * @param string $privilege     权限        [rw]+
     * @return string|int
     *
     * 751      不存在该仓库路径
     * 752      仓库路径需以/开始
     * 901      不支持的授权对象类型
     * 701      仓库路径下不存在该对象的权限记录
     * string   正常
     */
    public function EditRepPathPri($authzContent, $repName, $repPath, $objectType, $objectName, $privilege, $invert = false)
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        //$objectType 检查
        if (!in_array($objectType, array_keys($this->array_objectType))) {
            return 901;
        }

        //处理对象名称与反转关系
        $objectKey = $this->GetKey($objectType, $invert, $objectName);
        if (is_numeric($objectKey)) {
            return $objectKey;
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . $objectKey['normal'] . "=$privilege\n", $authzContent);
            } else {
                $regArray = $this->GetReg($objectType, $invert, $objectName);
                if (is_numeric($regArray)) {
                    return $regArray;
                }

                preg_match_all($regArray['quote_hasInvert'], $authzContentPreg[1][0], $resultPreg1);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                preg_match_all($regArray['quote_noInvert'], $authzContentPreg[1][0], $resultPreg2);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }

                //如果反转或者非反转都不存在则返回无该条路径记录提示
                if (!array_key_exists(0, $resultPreg1[0]) && !array_key_exists(0, $resultPreg2[0])) {
                    return 701;
                }

                if (array_key_exists(0, $resultPreg1[0])) {
                    /**
                     * 现在为反转状态
                     * 如果传入不反转状态则需要修改状态为不反转
                     */
                    if ($invert) {
                        /**
                         * 不需要修改反转状态
                         * 接下来修改读写权限
                         */
                        return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_hasInvert'], $objectKey['hasInvert'] . "=$privilege", $authzContentPreg[1][0])), $authzContent);
                    } else {
                        /**
                         * 需要修改反转状态为去掉反转
                         * 接下来修改读写权限
                         */
                        return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_hasInvert'], $objectKey['noInvert'] . "=$privilege", $authzContentPreg[1][0])), $authzContent);
                    }
                } elseif (array_key_exists(0, $resultPreg2[0])) {
                    /**
                     * 现在为非反转状态
                     * 如果传入反转状态则需要修改状态为反转
                     */
                    if ($invert) {
                        /**
                         * 需要修改反转状态为加入反转
                         * 接下来修改读写权限
                         */
                        return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_noInvert'], $objectKey['hasInvert'] . "=$privilege", $authzContentPreg[1][0])), $authzContent);
                    } else {
                        /**
                         * 不需要修改反转状态
                         * 接下来修改读写权限
                         */
                        return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_noInvert'], $objectKey['noInvert'] . "=$privilege", $authzContentPreg[1][0])), $authzContent);
                    }
                }
            }
        } else {
            return 751;
        }
    }

    /**
     * 为某仓库路径下删除权限
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @param string $objectType        授权对象    user  | group  | aliase  | * | $authenticated  | $anonymous
     * @param string $objectName        授权名称    user1 | group1 | aliase1 | * | $authenticated  | $anonymous : 无需携带 @ &
     * @return string|int
     *
     * 751      不存在该仓库路径
     * 752      仓库路径需以/开始
     * 901      不支持的授权对象类型
     * 701      仓库路径下不存在该对象的权限记录
     * string   正常
     */
    public function DelRepPathPri($authzContent, $repName, $repPath, $objectType, $objectName)
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        //$objectType 检查
        if (!in_array($objectType, array_keys($this->array_objectType))) {
            return 901;
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return 701;
            } else {
                $regArray = $this->GetReg($objectType, false, $objectName);
                if (is_numeric($regArray)) {
                    return $regArray;
                }

                preg_match_all($regArray['quote_hasInvert'], $authzContentPreg[1][0], $resultPreg1);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                preg_match_all($regArray['quote_noInvert'], $authzContentPreg[1][0], $resultPreg2);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }

                //如果反转或者非反转都不存在则返回无该条路径记录提示
                if (!array_key_exists(0, $resultPreg1[0]) && !array_key_exists(0, $resultPreg2[0])) {
                    return 701;
                }

                if (array_key_exists(0, $resultPreg1[0])) {
                    return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_hasInvert'], '', $authzContentPreg[1][0])), $authzContent);
                } elseif (array_key_exists(0, $resultPreg2[0])) {
                    return str_replace($authzContentPreg[0][0], "[$repName:$repPath]\n" . trim(preg_replace($regArray['quote_noInvert'], '', $authzContentPreg[1][0])), $authzContent);
                }
            }
        } else {
            return 751;
        }
    }

    /**
     * 向配置文件写入仓库路径
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @return string|int
     *
     * 851      已存在该仓库路径
     * 752      仓库路径需以/开始
     * string   正常
     */
    public function WriteRepPathToAuthz($authzContent, $repName, $repPath)
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            return 851;
        } else {
            return $authzContent . "\n[$repName:$repPath]\n";
        }
    }

    /**
     * 从配置文件删除指定仓库的指定路径
     *
     * @param string $authzContent
     * @param string $repName
     * @param string $repPath
     * @return string|int
     *
     * 751      不存在该仓库路径（已删除）
     * 752      仓库路径需以/开始
     * string   正常
     */
    public function DelRepPathFromAuthz($authzContent, $repName, $repPath)
    {
        //不以/开始
        if (substr($repPath, 0, 1) != '/') {
            return 752;
        }

        //处理路径结尾
        if ($repPath != '/') {
            $repPath = rtrim($repPath, '/');
        }

        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . preg_quote($repPath, '/')), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            return str_replace($authzContentPreg[0][0], '', $authzContent);
        } else {
            return 751;
        }
    }

    /**
     * 从配置文件删除指定仓库的所有路径
     *
     * @param string $authzContent
     * @param string $repName
     * @return string|int
     *
     * 751      已删除该仓库路径
     * string   正常
     */
    public function DelRepFromAuthz($authzContent, $repName)
    {
        preg_match_all(sprintf($this->reg_2, preg_quote($repName) . ':' . '.*'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            foreach ($authzContentPreg[0] as $key => $value) {
                $authzContent = str_replace($value, '', $authzContent);
            }
            return $authzContent;
        } else {
            return 751;
        }
    }

    /**
     * 从配置文件获取所有的仓库列表
     *
     * @param string $authzContent
     * @return array
     * 
     * array    正常
     */
    public function GetRepListFromAuthz($authzContent)
    {
        preg_match_all(sprintf($this->reg_2, '(.*?)' . ':' . '.*?'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        return array_values(array_unique($authzContentPreg[1]));
    }

    /**
     * 从配置文件修改仓库名称
     * 包括修改该仓库所有路径的仓库名称
     * 没有校验要修改的仓库是否已经存在 需要上层函数进行校验 不在工作范围内
     *
     * @param $authzContent
     * @param $oldRepName
     * @param $newRepName
     * @return array|int
     *
     * 740      不存在该仓库路径
     * array    正常
     */
    public function UpdRepFromAuthz($authzContent, $oldRepName, $newRepName)
    {
        preg_match_all(sprintf($this->reg_2, preg_quote($oldRepName) . ':' . '(.*?)'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[1])) {
            foreach ($authzContentPreg[0] as $key => $value) {
                $authzContent = str_replace($value, '[' . $newRepName . ':' . $authzContentPreg[1][$key] . ']' . $authzContentPreg[2][$key], $authzContent);
            }
            return $authzContent;
        } else {
            return 740;
        }
    }

    /**
     * 分组
     * ---------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * 添加分组
     *
     * @param $authzContent
     * @param $groupName
     * @return array|int
     *
     * 612      文件格式错误(不存在[groups]标识)
     * 820      分组已存在
     * string   正常
     */
    public function AddGroup($authzContent, $groupName)
    {
        $groupName = trim($groupName);
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            preg_match_all(sprintf($this->reg_5, preg_quote($groupName)), $authzContentPreg[1][0], $resultPreg);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if (array_key_exists(0, $resultPreg[0])) {
                return 820;
            } else {
                return preg_replace(sprintf($this->reg_2, 'groups'), trim($authzContentPreg[0][0]) . "\n$groupName=\n", $authzContent);
            }
        } else {
            return 612;
        }
    }

    /**
     * 从所有仓库路径和分组下删除 用户|分组|别名 及其反转
     *
     * @param $authzContent
     * @param $objectName
     * @param $type
     * @return array|int
     *
     * 612      文件格式错误(不存在[groups]标识)
     * 901      不支持的授权对象类型
     * string   正常
     */
    public function DelObjectFromAuthz($authzContent, $objectName, $objectType)
    {
        $objectName = trim($objectName);

        if ($objectType == 'user') {
        } elseif ($objectType == 'group') {
            $objectName = "@$objectName";
        } elseif ($objectType == 'aliase') {
            $objectName = "&$objectName";
        } else {
            return 901;
        }

        //从全局的仓库路径下删除
        $authzContent = preg_replace(sprintf($this->reg_5, "(" . preg_quote($objectName) . ")|(~" . preg_quote($objectName) . ")"), '', $authzContent);

        //从 [groups] 节中删除
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            preg_match_all(sprintf($this->reg_5, '[A-Za-z0-9-_.一-龥]+'), $authzContentPreg[1][0], $resultPreg);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            $groupContent = "";
            foreach ($resultPreg[1] as $key => $groupStr) {
                if ($objectType == 'group') {
                    //删除左值
                    if ($groupStr == substr($objectName, 1)) {
                        continue;
                    }
                }
                $userGroupStr = trim($resultPreg[2][$key]);
                $groupContent .= "$groupStr=";
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, [$this, 'ArrayValueTrim']);
                //从右值中删除
                foreach ($userGroupArray as $key => $value) {
                    if ($value == $objectName) {
                        unset($userGroupArray[$key]);
                        // break;
                    }
                }
                $groupContent .= implode(',', $userGroupArray) . "\n";
            }
            return preg_replace(sprintf($this->reg_2, 'groups'), "[groups]\n$groupContent", $authzContent);
        } else {
            return 612;
        }
    }

    /**
     * 从所有仓库路径和分组下修改用户、分组、别名及其反转形式
     *
     * @param $authzContent
     * @param $oldObjectName
     * @param $newObjectName
     * @param $objectType
     * @return int|string
     *
     * 611      authz文件格式错误(不存在[aliases]标识)
     * 612      authz文件格式错误(不存在[groups]标识)
     * 901      不支持的授权对象类型
     * 821      要修改的新分组已经存在
     * 831      要修改的新别名已经存在
     * 731      要修改的别名不存在
     * string   正常
     */
    public function UpdObjectFromAuthz($authzContent, $oldObjectName, $newObjectName, $objectType)
    {
        $oldObjectName = trim($oldObjectName);
        $newObjectName = trim($newObjectName);

        if ($objectType == 'user') {
        } elseif ($objectType == 'group') {
            $oldObjectName = "@$oldObjectName";
            $newObjectName = "@$newObjectName";
        } elseif ($objectType == 'aliase') {
            $oldObjectName = "&$oldObjectName";
            $newObjectName = "&$newObjectName";
        } else {
            return 901;
        }

        //从全局的仓库路径下修改 用户、分组、别名
        $authzContent = preg_replace(sprintf($this->reg_6, preg_quote($oldObjectName)), "$newObjectName=", $authzContent);
        $authzContent = preg_replace(sprintf($this->reg_6, '~' . preg_quote($oldObjectName)), "~$newObjectName=", $authzContent);

        //从 [aliases] 节中修改别名
        if ($objectType == 'aliase') {
            preg_match_all(sprintf($this->reg_2, 'aliases'), $authzContent, $authzContentPreg1);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if (array_key_exists(0, $authzContentPreg1[0])) {
                //要修改的新别名已经存在
                preg_match_all(sprintf($this->reg_5, substr(preg_quote($newObjectName), 1)), $authzContentPreg1[1][0], $resultPreg1);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg1[0])) {
                    return 831;
                }
                //继续处理
                preg_match_all(sprintf($this->reg_5, substr(preg_quote($oldObjectName), 1)), $authzContentPreg1[1][0], $resultPreg1);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg1[0])) {
                    $authzContent = preg_replace(sprintf($this->reg_2, 'aliases'), "[aliases]\n" . trim(preg_replace(sprintf($this->reg_5, substr(preg_quote($oldObjectName), 1)), substr($newObjectName, 1) . '=' . $resultPreg1[2][0], $authzContentPreg1[1][0])) . "\n", $authzContent);
                } else {
                    //要修改的别名不存在
                    return 731;
                }
            } else {
                return 611;
            }
        }

        //从 [groups] 节中从左值修改分组，从右值修改分组、别名、用户
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg2);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg2[0])) {
            preg_match_all(sprintf($this->reg_5, '[A-Za-z0-9-_.一-龥]+'), $authzContentPreg2[1][0], $resultPreg2);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if ($objectType == 'group') {
                //要修改的新分组已经存在
                if (in_array(substr($newObjectName, 1), $resultPreg2[1])) {
                    return 821;
                }
            }
            $groupContent = "";
            foreach ($resultPreg2[1] as $key => $groupStr) {
                if ($objectType == 'group') {
                    //修改左值
                    if ($groupStr == substr($oldObjectName, 1)) {
                        $groupStr = substr($newObjectName, 1);
                    }
                }
                $userGroupStr = trim($resultPreg2[2][$key]);
                $groupContent .= "$groupStr=";
                $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                array_walk($userGroupArray, [$this, 'ArrayValueTrim']);
                //从右值中修改
                foreach ($userGroupArray as $key => $value) {
                    if ($value == $oldObjectName) {
                        $userGroupArray[$key] = $newObjectName;
                        // break;
                    }
                }
                $groupContent .= implode(',', $userGroupArray) . "\n";
            }
            return preg_replace(sprintf($this->reg_2, 'groups'), "[groups]\n$groupContent", $authzContent);
        } else {
            return 612;
        }
    }

    /**
     * 获取分组信息
     * 不指定分组名则返回所有分组信息
     * 
     * @param string $authzContent
     * @param string $groupName
     * @return array|int
     *
     * 612      文件格式错误(不存在[groups]标识)
     * 720      指定的分组不存在
     * array    正常
     */
    public function GetGroupInfo($authzContent, $groupName = '')
    {
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return $groupName == '' ? [] : 720;
            } else {
                $list = [];
                preg_match_all(sprintf($this->reg_5, $groupName == '' ? '[A-Za-z0-9-_.一-龥]+' : preg_quote($groupName)), $authzContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (empty($resultPreg[0])) {
                    return $groupName == '' ? [] : 720;
                }
                foreach ($resultPreg[1] as $key => $groupStr) {
                    $userGroupStr = trim($resultPreg[2][$key]);
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    $item = [
                        'groupName' => $groupStr,
                        'include' => [
                            'users' => [
                                'count' => 0,
                                'list' => []
                            ],
                            'groups' => [
                                'count' => 0,
                                'list' => []
                            ],
                            'aliases' => [
                                'count' => 0,
                                'list' => []
                            ]
                        ]
                    ];
                    foreach ($userGroupArray as $value) {
                        $value = trim($value);
                        $prefix = substr($value, 0, 1);
                        if ($prefix == '@') {
                            $item['include']['groups']['list'][] = substr($value, 1);
                            $item['include']['groups']['count'] = $item['include']['groups']['count'] + 1;
                        } elseif ($prefix == '&') {
                            $item['include']['aliases']['list'][] = substr($value, 1);
                            $item['include']['aliases']['count'] = $item['include']['aliases']['count'] + 1;
                        } else {
                            $item['include']['users']['list'][] = $value;
                            $item['include']['users']['count'] = $item['include']['users']['count'] + 1;
                        }
                    }
                    $list[] = $item;
                }
                return $groupName == '' ? $list : (empty($list) ? 720 : $list[0]);
            }
        } else {
            return 612;
        }
    }

    /**
     * 清空[groups]下的内容
     *
     * @param string $authzContent
     * @return string|int
     */
    public function ClearGroupSection($authzContent)
    {
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            return preg_replace(sprintf($this->reg_2, 'groups'), "[groups]\n", $authzContent);
        } else {
            return 612;
        }
    }

    /**
     * 获取指定对象所在的分组列表 只包括直接包含关系
     * 非递归获取
     *
     * @param $authzContent
     * @param $objectName
     * @param $objectType user|group|aliase
     * @return array|int
     * 
     * 612      文件格式错误(不存在[groups]标识)
     * 700      对象不存在
     * 901      不支持的授权对象类型
     * array    正常
     */
    public function GetObjBelongGroupList($authzContent, $objectName, $objectType)
    {
        $objectName = trim($objectName);
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return [];
            } else {
                preg_match_all(sprintf($this->reg_5, '[A-Za-z0-9-_.一-龥]+'), $authzContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }

                if ($objectType == 'user') {
                    //无操作
                } elseif ($objectType == 'group') {
                    $objectName = "@$objectName";
                } elseif ($objectType == 'aliase') {
                    $objectName = "&$objectName";
                } else {
                    return 901;
                }

                $groupArray = [];
                foreach ($resultPreg[1] as $key => $groupStr) {
                    $userGroupStr = trim($resultPreg[2][$key]);
                    $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                    array_walk($userGroupArray, [$this, 'ArrayValueTrim']);
                    if (in_array($objectName, $userGroupArray)) {
                        $groupArray[] = $groupStr;
                    }
                }

                return $groupArray;
            }
        } else {
            return 612;
        }
    }

    /**
     * 获取分组所在的所有分组 包括直接包含和间接包含关系
     * 递归获取
     *
     * @param string $groupName
     * @return array|int
     * 
     * 612      文件格式错误(不存在[groups]标识)
     * 720      指定的分组不存在
     * 700      对象不存在
     * 901      不支持的授权对象类型
     */
    public function GetSvnGroupAllGroupList($authzContent, $groupName)
    {
        $parentGroupName = $groupName;

        //所有的分组列表
        //所有的分组列表
        $groupInfo = $this->GetGroupInfo($authzContent);
        if (is_numeric($groupInfo)) {
            return $groupInfo;
        }
        $allGroupList = array_column($groupInfo, 'groupName');

        //分组所在的分组列表 
        $groupGroupList = $this->GetObjBelongGroupList($authzContent, $parentGroupName, 'group');
        if (is_numeric($groupGroupList)) {
            return $groupGroupList;
        }

        //剩余的分组列表
        $leftGroupList = array_diff($allGroupList, $groupGroupList);

        //循环匹配
        loop:
        $userGroupListBack = $groupGroupList;
        foreach ($groupGroupList as $group1) {
            $newList = $this->GetObjBelongGroupList($authzContent, $group1, 'group');
            if (is_numeric($newList)) {
                return $newList;
            }
            foreach ($leftGroupList as $key2 => $group2) {
                if (in_array($group2, $newList)) {
                    array_push($groupGroupList, $group2);
                    unset($leftGroupList[$key2]);
                }
            }
        }
        if ($groupGroupList != $userGroupListBack) {
            goto loop;
        }

        return $groupGroupList;
    }

    /**
     * 获取用户所在的所有分组 包括直接包含和间接包含关系
     * 递归获取
     * 
     * @param $authzContent
     * @param $userName
     * @return array|int
     * 
     * 612      文件格式错误(不存在[groups]标识)
     * 700      对象不存在
     * 901      不支持的授权对象类型
     * array    正常
     */
    public function GetUserBelongGroupList($authzContent, $userName)
    {
        //所有的分组列表
        $groupInfo = $this->GetGroupInfo($authzContent);
        if (is_numeric($groupInfo)) {
            return $groupInfo;
        }
        $allGroupList = array_column($groupInfo, 'groupName');

        //用户所在的分组列表
        $userGroupList = $this->GetObjBelongGroupList($authzContent, $userName, 'user');
        if (is_numeric($userGroupList)) {
            return $userGroupList;
        }

        //剩余的分组列表
        $leftGroupList = array_diff($allGroupList, $userGroupList);

        //循环匹配 直到匹配到与该用户相关的有权限的用户组为止
        loop:
        $userGroupListBack = $userGroupList;
        foreach ($userGroupList as $group1) {
            $newList = $this->GetObjBelongGroupList($authzContent, $group1, 'group');
            if (is_numeric($newList)) {
                return $newList;
            }
            foreach ($leftGroupList as $key2 => $group2) {
                if (in_array($group2, $newList)) {
                    array_push($userGroupList, $group2);
                    unset($leftGroupList[$key2]);
                }
            }
        }
        if ($userGroupList != $userGroupListBack) {
            goto loop;
        }

        return $userGroupList;
    }

    /**
     * 为分组添加或者删除所包含的对象
     * 对象包括：用户、分组、用户别名
     *
     * @param $authzContent
     * @param $groupName
     * @param $objectName 不带符号的用户、分组、用户别名
     * @param $objectType user|group|aliase
     * @param $actionType add|delete
     * @return int|string
     *
     * 612      文件格式错误(不存在[groups]标识)
     * 720      分组不存在
     * 803      要添加的对象已存在该分组
     * 703      要删除的对象不存在该分组
     * 901      无效的对象类型 user|group|aliase
     * 902      无效的操作类型 add|delete
     * 802      不能操作相同名称的分组
     * string   正常
     */
    public function UpdGroupMember($authzContent, $groupName, $objectName, $objectType, $actionType)
    {
        $groupName = trim($groupName);
        $objectName = trim($objectName);
        //不能添加相同名称的分组
        if ($objectType == 'group' && $groupName == $objectName) {
            return 802;
        }
        preg_match_all(sprintf($this->reg_2, 'groups'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[0])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return 720;
            } else {
                preg_match_all(sprintf($this->reg_5, preg_quote($groupName)), $authzContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    foreach ($resultPreg[1] as $key => $groupStr) {
                        $userGroupStr = trim($resultPreg[2][$key]);
                        $userGroupArray = $userGroupStr == '' ? [] : explode(',', $userGroupStr);
                        array_walk($userGroupArray, [$this, 'ArrayValueTrim']);

                        if ($objectType == 'user') {
                            //无操作
                        } elseif ($objectType == 'group') {
                            $objectName = "@$objectName";
                        } elseif ($objectType == 'aliase') {
                            $objectName = "&$objectName";
                        } else {
                            return 901;
                        }

                        if ($actionType == 'add') {
                            if (in_array($objectName, $userGroupArray)) {
                                return 803;
                            } else {
                                //添加操作
                                $userGroupArray[] = $objectName;
                                $groupContent = "$groupStr=" . implode(',', $userGroupArray);

                                //替换和返回
                                return preg_replace(sprintf($this->reg_2, 'groups'), "[groups]\n" . trim(preg_replace(sprintf($this->reg_5, preg_quote($groupName)), $groupContent, $authzContentPreg[1][0])) . "\n", $authzContent);
                            }
                        } elseif ($actionType == 'delete') {
                            if (in_array($objectName, $userGroupArray)) {
                                //删除操作
                                unset($userGroupArray[array_search($objectName, $userGroupArray)]);
                                $groupContent = "$groupStr=" . implode(',', $userGroupArray);

                                //替换和返回
                                return preg_replace(sprintf($this->reg_2, 'groups'), "[groups]\n" . trim(preg_replace(sprintf($this->reg_5, preg_quote($groupName)), $groupContent, $authzContentPreg[1][0])) . "\n", $authzContent);
                            } else {
                                return 703;
                            }
                        } else {
                            return 902;
                        }
                    }
                } else {
                    return 720;
                }
            }
        } else {
            return 612;
        }
    }

    /**
     * 获取分组有权限的仓库路径列表
     *
     * @param string $authzContent
     * @param string $groupName
     * @return array
     */
    public function GetGroupHasPri($authzContent, $groupName)
    {
        preg_match_all(sprintf($this->reg_7, "(@" . preg_quote($groupName) . ")"), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }

        $result = [];
        foreach ($authzContentPreg[5] as $key => $value) {
            $result[] = [
                'repName' => $authzContentPreg[2][$key],
                'priPath' => $authzContentPreg[3][$key],
                'repPri' => $authzContentPreg[6][$key],
            ];
        }

        return $result;
    }

    /**
     * 用户
     * ---------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * 获取用户有权限的仓库路径列表
     *
     * @param $authzContent
     * @param $userName
     * @return array
     * 
     * 612      文件格式错误(不存在[groups]标识)
     * 700      对象不存在
     * 901      不支持的授权对象类型
     * array    正常
     */
    public function GetUserAllPri($authzContent, $userName)
    {
        /**
         * [a:/]
         * user1=[rw]+
         *
         * [b:/]
         * # user2 指所有非 user1 用户
         * ~user2=[rw]+
         * 需要从结果中过滤掉 ~user1=[rw]+
         *
         * [c:/]
         * *=[rw]+
         *
         * [d:/]
         * # 表示匿名用户具备 [rw]+ 但是匿名提交修改需要已验证的用户身份
         * ~$authenticated=[rw]+
         *
         * [e:/]
         * $anonymous=[rw]+
         *
         * [f:/]
         * $authenticated=[rw]+
         *
         * [g:/]
         * # aliase1 指所有不等于 user1 的别名用户
         * todo
         * ~&aliase1=[rw]+
         *
         * [h:/]
         * # group1 直接或者间接包含 user1
         * @group1=[rw]+
         *
         * [i:/]
         * # group1 不直接或间接包含 user1
         * ~@group1=[rw]+
         */

        //非捕获分组减少开销
        $pregArray = [
            '(?:' . preg_quote($userName) . ')',
            '(?:~[A-Za-z0-9-_.一-龥]+)',
            '(?:\*)',
            '(?:~\$authenticated)',
            '(?:\$anonymous)',
            '(?:\$authenticated)',
            '(?:~&[A-Za-z0-9-_.一-龥]+)',
        ];

        //获取 user1 所在的所有分组列表
        $part1 = $this->GetUserBelongGroupList($authzContent, $userName);
        if (is_numeric($part1)) {
            return $part1;
        }
        foreach ($part1 as $value) {
            $pregArray[] = '(?:@' . preg_quote($value) . ')';
        }

        //获取 user1 所不在的分组列表
        $groupInfo = $this->GetGroupInfo($authzContent);
        if (is_numeric($groupInfo)) {
            return $groupInfo;
        }
        $all = array_column($groupInfo, 'groupName');
        $part2 = array_diff($all, $part1);
        foreach ($part2 as $value) {
            $pregArray[] = '(?:~@' . preg_quote($value) . ')';
        }

        preg_match_all(sprintf($this->reg_7, '(' . implode('|', $pregArray) . ')'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }

        //两个满足条件在同一个路径下的问题 会被一个条件匹配到 而且不会重复 所以无需去重

        // 从结果中过滤掉 ~user1=[rw]+
        $result = [];
        foreach ($authzContentPreg[5] as $key => $value) {
            if ($value == "~$userName") {
                unset($authzContentPreg[5][$key]);
            } else {
                $result[] = [
                    'repName' => $authzContentPreg[2][$key],
                    'priPath' => $authzContentPreg[3][$key],
                    'repPri' => $authzContentPreg[6][$key],
                    // 'unique' => '' //兼容2.3.3及之前版本 从2.3.3.1版本开始无实际意义
                ];
            }
        }

        return $result;
    }

    /**
     * 添加用户
     *
     * @param $passwdContent
     * @param $userName
     * @param $userPass
     * @return int|string
     *
     * 621      文件格式错误(不存在[users]标识)
     * 810      用户已存在
     * string   正常
     */
    public function AddUser($passwdContent, $userName, $userPass)
    {
        $userName = trim($userName);
        $userPass = trim($userPass);
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return preg_replace(sprintf($this->reg_2, 'users'), trim($passwdContentPreg[0][0]) . "\n$userName=$userPass\n", $passwdContent);
            } else {
                preg_match_all(sprintf($this->reg_5, '(' . $this->reg_1 . ')*[A-Za-z0-9-_.一-龥]+'), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                array_walk($resultPreg[1], [$this, 'ArrayValueEnable']);
                if (in_array($userName, $resultPreg[1])) {
                    return 810;
                }
                return preg_replace(sprintf($this->reg_2, 'users'), trim($passwdContentPreg[0][0]) . "\n$userName=$userPass\n", $passwdContent);
            }
        } else {
            return 621;
        }
    }

    /**
     * 从 passwd 文件修改用户的用户名
     * 一般不提供修改用户名的方法 一个不变的用户对应SVN仓库所有的历史记录是非常有必要的
     * 
     * @param $passwdContent
     * @param $oldUserName
     * @param $newUserName
     * @return void
     * 
     * 621      文件格式错误(不存在[users]标识)
     * 710      用户不存在
     * 811      要修改的新用户已经存在
     * string   正常
     */
    public function UpdUserFromPasswd($passwdContent, $oldUserName, $newUserName, $isDisabledUser)
    {
        $oldUserName = trim($oldUserName);
        $newUserName = trim($newUserName);
        $oldUserName = $isDisabledUser ? ($this->reg_1 . preg_quote($oldUserName)) : preg_quote($oldUserName);
        $newUserName = $isDisabledUser ? ($this->reg_1 . $newUserName) : $newUserName;
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return 710;
            } else {
                //检查目标用户是否已经存在
                preg_match_all(sprintf($this->reg_5, '(' . $this->reg_1 . ')*[A-Za-z0-9-_.一-龥]+'), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                array_walk($resultPreg[1], [$this, 'ArrayValueEnable']);
                if (in_array($newUserName, $resultPreg[1])) {
                    return 811;
                }
                //继续处理
                preg_match_all(sprintf($this->reg_5, $oldUserName), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    return preg_replace(sprintf($this->reg_2, 'users'), "[users]\n" . trim(preg_replace(sprintf($this->reg_5, $oldUserName), $newUserName . '=' . $resultPreg[2][0], $passwdContentPreg[1][0])) . "\n", $passwdContent);
                } else {
                    return 710;
                }
            }
        } else {
            return 621;
        }
    }

    /**
     * 从 passwd 文件删除用户
     *
     * @param $passwdContent
     * @param $userName
     * @param $isDisabledUser
     * @return int|string
     *
     * 621      文件格式错误(不存在[users]标识)
     * 710      用户不存在
     * string   正常
     */
    public function DelUserFromPasswd($passwdContent, $userName, $isDisabledUser = false)
    {
        $userName = trim($userName);
        $userName = $isDisabledUser ? ($this->reg_1 . preg_quote($userName)) : preg_quote($userName);
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return 710;
            } else {
                preg_match_all(sprintf($this->reg_5, $userName), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    return preg_replace(sprintf($this->reg_2, 'users'), "[users]\n" . trim(preg_replace(sprintf($this->reg_5, $userName), '', $passwdContentPreg[1][0])) . "\n", $passwdContent);
                } else {
                    return 710;
                }
            }
        } else {
            return 621;
        }
    }

    /**
     * 获取用户信息
     * 不指定用户则返回所有用户信息
     *
     * @param $passwdContent
     * @param $userName
     * @return array|int
     *
     * 621      文件格式错误(不存在[users]标识)
     * 710      指定用户不存在
     * array    正常
     */
    public function GetUserInfo($passwdContent, $userName = '')
    {
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return $userName == '' ? [] : 710;
            } else {
                preg_match_all(sprintf($this->reg_5, "($this->reg_1)*" . ($userName == '' ? '[A-Za-z0-9-_.一-龥]+' : preg_quote($userName))), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (empty($resultPreg[0])) {
                    return $userName == '' ? [] : 710;
                }
                $result = [];
                foreach ($resultPreg[1] as $key => $value) {
                    $item = [];
                    if (substr($value, 0, strlen($this->reg_1)) == $this->reg_1) {
                        $item['userName'] = substr($value, strlen($this->reg_1));
                        $item['userPass'] = $resultPreg[3][$key];
                        $item['disabled'] = '1';
                    } else {
                        $item['userName'] = $value;
                        $item['userPass'] = $resultPreg[3][$key];
                        $item['disabled'] = '0';
                    }
                    $result[] = $item;
                }
                return $userName == '' ? $result : (empty($result) ? 710 : $result[0]);
            }
        } else {
            return 621;
        }
    }

    /**
     * 获取用户信息 http
     * 不指定用户则返回所有用户信息
     *
     * @param string $passwdContent
     * @param string $userName
     * @return array
     * 
     * 710      指定用户不存在
     * array    正常
     */
    public function GetUserInfoHttp($passwdContent, $userName = '')
    {
        $passwdContent = trim($passwdContent);
        if (empty($passwdContent)) {
            return $userName == '' ? [] : 710;
        } else {
            preg_match_all(sprintf($this->reg_8, "($this->reg_1)*" . ($userName == '' ? '[A-Za-z0-9-_.一-龥]+' : preg_quote($userName))), $passwdContent, $resultPreg);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if (empty($resultPreg[0])) {
                return $userName == '' ? [] : 710;
            }
            $result = [];
            foreach ($resultPreg[1] as $key => $value) {
                $item = [];
                if (substr($value, 0, strlen($this->reg_1)) == $this->reg_1) {
                    $item['userName'] = substr($value, strlen($this->reg_1));
                    $item['userPass'] = $resultPreg[3][$key];
                    $item['disabled'] = '1';
                } else {
                    $item['userName'] = $value;
                    $item['userPass'] = $resultPreg[3][$key];
                    $item['disabled'] = '0';
                }
                $result[] = $item;
            }
            return $userName == '' ? $result : (empty($result) ? 710 : $result[0]);
        }
    }

    /**
     * 修改指定用户的密码
     *
     * @param string $passwdContent
     * @param string $userName
     * @param string $userPass
     * @param boolean $isDisabledUser
     * @return string|int
     * 
     * 621      文件格式错误(不存在[users]标识)
     * 710      用户不存在
     * string   正常
     */
    public function UpdUserPass($passwdContent, $userName, $userPass, $isDisabledUser = false)
    {
        $userName = trim($userName);
        $userName = $isDisabledUser ? ($this->reg_1 . $userName) : $userName;
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return 710;
            } else {
                preg_match_all(sprintf($this->reg_5, preg_quote($userName)), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    return preg_replace(sprintf($this->reg_2, 'users'), "[users]\n" . trim(preg_replace(sprintf($this->reg_5, preg_quote($userName)), "$userName=$userPass", $passwdContentPreg[1][0])) . "\n", $passwdContent);
                } else {
                    return 710;
                }
            }
        } else {
            return 621;
        }
    }

    /**
     * 修改指定用户的密码 http
     *
     * @param string $passwdContent
     * @param string $userName
     * @param string $userPass
     * @param boolean $isDisabledUser
     * @return string|int
     * 
     * 710      用户不存在
     * string   正常
     */
    public function UpdUserPassHttp($passwdContent, $userName, $userPass, $isDisabledUser = false)
    {
        $userName = trim($userName);
        $userName = $isDisabledUser ? ($this->reg_1 . $userName) : $userName;

        $passwdContent = trim($passwdContent);
        if (empty($passwdContent)) {
            return 710;
        } else {
            preg_match_all(sprintf($this->reg_8, preg_quote($userName)), $passwdContent, $resultPreg);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if (array_key_exists(0, $resultPreg[0])) {
                return trim(preg_replace(sprintf($this->reg_8, preg_quote($userName)), "$userName:$userPass", $passwdContent)) . "\n";
            } else {
                return 710;
            }
        }
    }

    /**
     * 启用或禁用用户
     *
     * @param $passwdContent
     * @param $userName
     * @param $disable true 原来为启用状态现在要禁用 false 原来为禁用状态现在要启用
     * @return int|string
     *
     * 621      文件格式错误(不存在[users]标识)
     * 710      用户不存在
     * string   正常
     */
    public function UpdUserStatus($passwdContent, $userName, $disable = false)
    {
        $userName = trim($userName);
        preg_match_all(sprintf($this->reg_2, 'users'), $passwdContent, $passwdContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $passwdContentPreg[1])) {
            $temp1 = trim($passwdContentPreg[1][0]);
            if (empty($temp1)) {
                return 710;
            } else {
                $preg = $disable ? $userName : ($this->reg_1 . $userName);
                preg_match_all(sprintf($this->reg_5, preg_quote($preg)), $passwdContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    $replace = ($disable ? $this->reg_1 : '') . $userName . '=' . $resultPreg[2][0];
                    return preg_replace(sprintf($this->reg_2, 'users'), "[users]\n" . trim(preg_replace(sprintf($this->reg_5, preg_quote($preg)), $replace, $passwdContentPreg[1][0])) . "\n", $passwdContent);
                } else {
                    return 710;
                }
            }
        } else {
            return 621;
        }
    }

    /**
     * 启用或禁用用户 http
     *
     * @param $passwdContent
     * @param $userName
     * @param $disable true 原来为启用状态现在要禁用 false 原来为禁用状态现在要启用
     * @return int|string
     *
     * 710      用户不存在
     * string   正常
     */
    public function UpdUserStatusHttp($passwdContent, $userName, $disable = false)
    {
        $userName = trim($userName);
        $passwdContent = trim($passwdContent);
        if (empty($passwdContent)) {
            return 710;
        } else {
            $preg = $disable ? $userName : ($this->reg_1 . $userName);
            preg_match_all(sprintf($this->reg_8, preg_quote($preg)), $passwdContent, $resultPreg);
            if (preg_last_error() != 0) {
                return preg_last_error();
            }
            if (array_key_exists(0, $resultPreg[0])) {
                $replace = ($disable ? $this->reg_1 : '') . $userName . ':' . $resultPreg[2][0];
                return trim(preg_replace(sprintf($this->reg_8, preg_quote($preg)), $replace, $passwdContent)) . "\n";
            } else {
                return 710;
            }
        }
    }

    /**
     * 别名
     * ---------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * 添加别名
     */
    public function AddAliase()
    {
    }

    /**
     * 删除别名
     */
    public function DelAliase()
    {
    }

    /**
     * 修改别名
     */
    public function EditAliase()
    {
    }

    /**
     * 修改别名内容
     */
    public function EditAliaseCon()
    {
    }

    /**
     * 启用或禁用指定别名
     */
    public function UpdAliaseStatus()
    {
    }

    /**
     * 获取别名信息
     * 不指定别名则返回所有别名信息
     *
     * @param string $authzContent
     * @param string $aliaseName
     * @return array|int
     * 
     * 611      authz文件格式错误(不存在[aliases]标识)
     * 730      指定的别名不存在
     */
    public function GetAliaseInfo($authzContent, $aliaseName = '')
    {
        preg_match_all(sprintf($this->reg_2, 'aliases'), $authzContent, $authzContentPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $authzContentPreg[1])) {
            $temp1 = trim($authzContentPreg[1][0]);
            if (empty($temp1)) {
                return $aliaseName == '' ? [] : 730;
            } else {
                preg_match_all(sprintf($this->reg_5, ($aliaseName == '' ? '[A-Za-z0-9-_.一-龥]+' : preg_quote($aliaseName))), $authzContentPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (empty($resultPreg[0])) {
                    return 730;
                }
                $result = [];
                foreach ($resultPreg[1] as $key => $value) {
                    $item = [];
                    $item['aliaseName'] = $value;
                    $item['aliaseCon'] = $resultPreg[2][$key];
                    $result[] = $item;
                }
                return $aliaseName == '' ? $result : (empty($result) ? 730 : $result[0]);
            }
        } else {
            return 611;
        }
    }
}
