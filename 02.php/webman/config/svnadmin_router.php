<?php
/*
 * @Author: witersen
 * @Date: 2022-04-26 00:24:31
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 19:23:17
 * @Description: QQ:1801168257
 */

/**
 * 自定义全局权限路由表
 * 
 * 路由规则：控制器\\方法
 * 白名单路由：即无需鉴权的请求
 */

return [
    /**
     * 管理系统白名单路由
     */
    "public" => [
        'web' => [
            //测试
            'app\\controller\\Index\\token1',
            //测试
            'app\\controller\\Index\\token2',

            'app\\controller\\Unimportant\\Login',
            'app\\controller\\Unimportant\\GetVeryfyCode'
        ],
        'mini' => []
    ],

    /**
     * SVN用户有权限路由
     */
    'svn_user_routers' => [
        'app\\controller\\Svnrep\\GetSvnUserRepList',
        'app\\controller\\Svnrep\\GetUserRepCon',

        'app\\controller\\Unimportant\\Logout',

        'app\\controller\\Svn\\GetCheckout',
        'app\\controller\\Svn\\GetStatus',

        'app\\controller\\Personal\\EditSvnUserPass',
    ],
];
