<?php

declare(strict_types=1);

/**
 * 检查SVN仓库名称
 */
function FunIsValidRepName($repName)
{
    return preg_match(REG_SVN_REP_NAME, $repName) == 1;
}

/**
 * 检查SVN用户名称
 */
function FunIsValidRepUser($repUserName)
{
    return preg_match(REG_SVN_USER_NAME, $repUserName) == 1;
}

/**
 * 检查SVN用户组名称
 */
function FunIsValidRepGroup($repGroupName)
{
    return preg_match(REG_SVN_GROUP_NAME, $repGroupName) == 1;
}

/**
 * 邮箱检查
 */
function FunIsValidMail($mail)
{
    return preg_match_all(REG_MAIL, $mail) == 1;
}
