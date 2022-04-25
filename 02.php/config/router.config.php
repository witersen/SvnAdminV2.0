<?php

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
        'svnrep/GetRepCon',

        'common/Logout',

        'subversion/GetCheckout',
        'subversion/GetStatus',

        'svnuser/EditSvnUserPass',
    ],
];
