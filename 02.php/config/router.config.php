<?php
/*
 * @Author: witersen
 * @Date: 2022-04-26 00:24:31
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 18:24:04
 * @Description: QQ:1801168257
 */

/**
 * 自定义全局权限路由表
 * 
 * 路由规则：控制器名/方法名
 * 白名单路由：即无需鉴权的请求
 */

$ROUTERS = [
    /**
     * 管理系统白名单路由
     */
    'WEB_PUBLIC_ROUTERS' => [
        'common/Login',
        'common/GetVeryfyCode'
    ],

    /**
     * 小程序白名单路由
     */
    'MINI_PUBLIC_ROUTERS' => [],

    /**
     * SVN用户有权限路由
     */
    'SVN_USER_ROUTERS' => [
        'svnrep/GetSvnUserRepList',
        'svnrep/GetUserRepCon',

        'common/Logout',

        'subversion/GetCheckout',
        'subversion/GetStatus',

        'svnuser/EditSvnUserPass',
    ],
];
