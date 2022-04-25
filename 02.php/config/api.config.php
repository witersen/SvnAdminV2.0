<?php

/**
 * 小程序接口无需校验白名单
 */
define('miniWhiteList', serialize([]));

/**
 * 管理系统接口无需鉴权白名单
 */
define('webWhiteList', serialize([
    'Login',
    'GetVeryfyCode'
]));

/**
 * SVN用户有权限的函数列表
 */
define('svnUserPriFunction',serialize([
    
]));
