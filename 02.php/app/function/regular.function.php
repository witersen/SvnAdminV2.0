<?php

//declare(strict_types=1);

/**
 * 检查SVN仓库名称
 */
function FunCheckRepName($repName, $message = 'SVN仓库名称只能包含字母、数字、破折号、下划线、点，不能以点开头或结尾')
{
    if (preg_match(REG_SVN_REP_NAME, $repName) != 1) {
        FunMessageExit(200, 0, $message);
    }
}

/**
 * 检查SVN用户名称
 */
function FunCheckRepUser($repUserName)
{
    if (preg_match(REG_SVN_USER_NAME, $repUserName) != 1) {
        FunMessageExit(200, 0, 'SVN用户名只能包含字母、数字、破折号、下划线、点');
    }
}

/**
 * 检查SVN用户组名称
 */
function FunCheckRepGroup($repGroupName)
{
    if (preg_match(REG_SVN_GROUP_NAME, $repGroupName) != 1) {
        FunMessageExit(200, 0, 'SVN分组名只能包含字母、数字、破折号、下划线、点');
    }
}

/**
 * 邮箱检查
 */
function FunCheckMail($mail)
{
    if (preg_match_all(REG_MAIL, $mail) == 1) {
        FunMessageExit(200, 0, '邮箱错误');
    }
}
