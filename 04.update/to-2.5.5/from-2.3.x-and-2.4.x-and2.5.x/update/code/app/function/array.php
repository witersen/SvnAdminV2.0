<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 对数据每项键值进行trim操作 
 */
function funArrayValueTrim(&$value, $key)
{
    $value = trim($value);
}

/**
 * 去除数组中的空字符串
 */
function funArrayValueFilter($value)
{
    return trim($value) != '';
}

/**
 * 过滤 /db
 */
function funArrayValueFilterDb($value)
{
    return substr($value, -3) == '/db';
}

/**
 * 过滤 /hooks
 */
function funArrayValueFilterHooks($value)
{
    return substr($value, -6) == '/hooks';
}

/**
 * 过滤 /locks
 */
function funArrayValueFilterLocks($value)
{
    return substr($value, -6) == '/locks';
}

/**
 * 过滤 /conf
 */
function funArrayValueFilterConf($value)
{
    return substr($value, -5) == '/conf';
}

/**
 * 对数据每项键值进行去除 #disabled# 操作
 */
function funArrayValueEnabled(&$value, $key)
{
    $REG_SVN_USER_DISABLED = '#disabled#';

    if (substr($value, 0, strlen($REG_SVN_USER_DISABLED)) == $REG_SVN_USER_DISABLED) {
        $value = substr($value, strlen($REG_SVN_USER_DISABLED));
    }
}

/**
 * 检查表单的参数
 */
function funCheckForm($form, $columns)
{
    //检查数组本身是否合法
    if (empty($form) || !isset($form)) {
        return message(200, 0, '参数不合法', [
            'column' => ''
        ]);
    }
    foreach ($columns as $column => $columnCheck) {
        if (!isset($columnCheck['required'])) {
            $columnCheck['required'] = true;
        }
        //检查数组中是否包含指定的变量
        if ($columnCheck['required']) {
            if (!array_key_exists($column, $form)) {
                return message(200, 0, '缺少参数', [
                    'column' => $column
                ]);
            }
        } else {
            if (!array_key_exists($column, $form)) {
                continue;
            }
        }
        //检查变量类型是否正确
        $types = explode('|', $columnCheck['type']);
        $typeStatus = false;
        foreach ($types as $types) {
            if ($types == gettype($form[$column])) {
                $typeStatus = true;
            }
        }
        if (!$typeStatus) {
            return message(200, 0, '参数类型错误', [
                'column' =>  sprintf('参数[%s]-需要[%s]-实际为[%s]', $column, $columnCheck['type'], gettype($form[$column]))
            ]);
        }
        //检查是否可以为空
        if (isset($columnCheck['notNull']) && $columnCheck['notNull']) {
            if ($columnCheck['type'] == 'integer') {
            } elseif ($columnCheck['type'] == 'boolean') {
            } elseif (empty($form[$column])) {
                return message(200, 0, '参数值不能为空', [
                    'column' => $column
                ]);
            }
        }
    }

    return message();
}
