<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-03 16:24:29
 * @Description: QQ:1801168257
 */

/**
 * 正则匹配规则
 */

return [
    /**
     * 校验SVN仓库名称
     * 
     * 1、可以包含中文、字母、数字、下划线、破折号、点
     * 2、不能以点开头或结尾
     */
    'REG_SVN_REP_NAME' => "/^[\x{4e00}-\x{9fa5}A-Za-z0-9-_]+(\.+[\x{4e00}-\x{9fa5}A-Za-z0-9-_]+)*$/u",

    /**
     * 校验SVN用户名称
     * 
     * 1、可以包含字母、数字、下划线、破折号、点
     * 2、字符串中包含空格的情况不会被匹配
     */
    'REG_SVN_USER_NAME' => "/^[A-Za-z0-9-_.]+$/",

    /**
     * 校验SVN用户组名称
     * 
     * 1、可以包含字母、数字、下划线、破折号、点
     * 2、字符串中包含空格的情况不会被匹配
     */
    'REG_SVN_GROUP_NAME' => "/^[A-Za-z0-9-_.]+$/",

    /**
     * 邮箱格式校验
     */
    'REG_MAIL' => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",

    /**
     * 自定义配置文件读取
     * 
     * %s => $key
     */
    'REG_CONFIG' => "/define\(\"*'*%s'*\"*\s*,\s*'*(.*?)'*\)/",

    /**
     * 匹配subversion版本号
     */
    'REG_SUBVERSION_VERSION' => "/svnserve.*?\b([0-9.]+)\b/m",
];
