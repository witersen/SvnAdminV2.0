<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 17:45:47
 * @Description: QQ:1801168257
 */

/**
 * 正则匹配规则
 */

/**
 * 禁用SVN用户前缀
 */
define('REG_SVN_USER_DISABLED', '#disabled#');

/**
 * 校验SVN仓库名称
 * 
 * 1、可以包含中文、字母、数字、下划线、破折号、点
 * 2、不能以点开头或结尾
 */
define('REG_SVN_REP_NAME', "/^[^.][\x{4e00}-\x{9fa5}A-Za-z0-9-_.]*[^.]$/u");

/**
 * 校验SVN用户名称
 * 
 * 1、可以包含字母、数字、下划线、破折号、点
 * 2、字符串中包含空格的情况不会被匹配
 */
define('REG_SVN_USER_NAME', "/^[A-Za-z0-9-_.]+$/");

/**
 * 校验SVN用户组名称
 * 
 * 1、可以包含字母、数字、下划线、破折号、点
 * 2、字符串中包含空格的情况不会被匹配
 */
define('REG_SVN_GROUP_NAME', "/^[A-Za-z0-9-_.]+$/");

/**
 * 匹配authz文件中的用户权限
 */
define('REG_AUTHZ_USER_PRI', "/^([A-Za-z0-9-_.\s]*[^\s])\s*=(.*)/m");

/**
 * 匹配authz文件中的用户组权限
 */
define('REG_AUTHZ_GROUP_PRI', "/^@([A-Za-z0-9-_.\s]*[^\s])\s*=(.*)/m");

/**
 * 匹配authz文件中[groups]下的分组以及成员
 * 
 * [groups]
 * group1=u1,@group2
 * group1=u2
 */
define('REG_AUTHZ_GROUP_KEY_VALUE', "/^([A-Za-z0-9-_.\s]*[^\s])\s*=(.*)/m");

/**
 * 
 * 匹配authz文件中的[groups]及其内容
 * 
 * 如
 * [groups]
 * g1=u1,u2
 * g2=u1,u3
 * 
 */
define('REG_AUTHZ_GROUP_WITH_CON', "/^\[groups\]([\s\S][^\[]*)/m");

/**
 * 匹配passwd文件中的[users]及其内容
 * 
 * 如
 * [users]
 * u1=password
 * u2=password
 */
define('REG_PASSWD_USER_WITH_CON', "/^\[users\]([\s\S][^\[]*)/m");

/**
 * 匹配passwd文件中的用户以及密码
 * 
 * 如
 * [users]
 * u1=password
 * u2=password
 * 中的后两行
 */
define('REG_PASSWD_USER_PASSWD', "/^((%s)*[A-Za-z0-9-_.]+)\s*=(.*)/m");

/**
 * 匹配authz配置文件中某个分组有权限的仓库列表
 * 
 * %s => $group
 */
define('REG_AUTHZ_GROUP_PRI_REPS', "/^\[(.*?):(.*?)\][A-za-z0-9_=@*\s]*?@%s[\s]*=[\s]*([rw]+)$\n/m");

/**
 * 匹配authz配置文件中某个用户有权限的仓库列表
 * 
 * %s => $user
 */
define('REG_AUTHZ_USER_PRI_REPS', "/^\[(.*?):(.*?)\][A-za-z0-9_=@*\s]*?%s[\s]*=[\s]*([rw]+)$\n/m");

/**
 * 匹配authz配置文件中所有用户有权限的仓库列表
 * 
 * *=r、*=rw
 */
define('REG_AUTHZ_ALL_HAVE_PRI_REPS', "/^\[(.*?):(.*?)\][A-za-z0-9_=@*\s]*?\*[\s]*=[\s]*([rw]+)$\n/m");

/**
 * 匹配authz配置文件中所有用户无权限的仓库列表
 * 
 * *=
 */
define('REG_AUTHZ_ALL_NO_PRI_REPS', "/^\[(.*?):(.*?)\][A-za-z0-9_=@*\s]*?\*[\s]*=[\s]*$\n/m");

/**
 * 匹配通过魔力符号配置的相关信息
 * 
 * 包含 ~ $等前缀
 */
//todo

/**
 * 匹配authz配置文件中指定仓库的指定路径 包含内容
 * 
 * 如
 * [rep1:/floder]
 * u1=r
 * @g1=rw
 * 
 * %s => $repName
 * %s => $repPath
 */
define('REG_AUTHZ_REP_SPECIAL_PATH_WITH_CON', "/^\[%s:%s\]([\s\S][^\[]*)/m");

/**
 * 匹配authz配置文件中指定仓库的指定路径 不包含内容
 * 
 * 如
 * [rep1:/]
 * 或
 * [rep2:/floder]
 * 等
 * 
 * %s => $repName
 * %s => $repPaht str_replace('/', '\/', $repPath)
 */
define('REG_AUTHZ_REP_SPECIAL_PATH_WITHOUT_CON', "/^\[%s:%s\]/m");

/**
 * 匹配authz配置文件中指定仓库的所有路径 不包含内容
 * 
 * %s => $repName
 */
define('REG_AUTHZ_REP_ALL_PATH_WITHOUT_CON', "/^\[%s:(.*?)\]/m");

/**
 * 匹配authz配置文件中指定仓库的所有路径以及包含的内容
 * 
 * %s => $repName
 */
define('REG_AUTHZ_REP_ALL_PATH_WITH_CON', "/^\[%s:.*\][\s\S][^\[]*/m");

/**
 * 匹配authz配置文件中的所有仓库名称
 * 
 * 不匹配这些仓库的内容和具体路径
 */
define('REG_AUTHZ_ALL_REP_WITHOUT_PATH_AND_CON', "/^\[(.*?):.*?\]/m");

/**
 * 将 svnadmin info $repPaht 的结果匹配为 key => value 形式
 */
define('REG_REP_INFO', "/(.*):[\S]*(.*)/m");

/**
 * 邮箱格式校验
 */
define('REG_MAIL', "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/");

/**
 * 自定义配置文件读取
 * 
 * %s => $key
 */
define('REG_CONFIG', "/define\(\"*'*%s'*\"*\s*,\s*'*(.*?)'*\)/");

/**
 * 匹配subversion版本号
 */
define('REG_SUBVERSION_VERSION', "/\bversion[\s]+(.*?)[\s]+/m");
