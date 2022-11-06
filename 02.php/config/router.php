<?php
/*
 * @Author: witersen
 * @Date: 2022-04-26 00:24:31
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 01:31:13
 * @Description: QQ:1801168257
 */

/**
 * 自定义全局权限路由表
 * 
 * 路由规则：控制器/方法
 * 白名单路由：即无需鉴权的请求
 */

return [
    /**
     * 管理系统白名单路由
     */
    "public" => [
        'web' => [
            'Common/Login',
            'Common/GetVerifyCode',
            'Safe/GetVerifyOption',
            'Svnrep/DownloadRepBackup'
        ],
        'mini' => []
    ],

    /**
     * SVN用户有权限路由
     */
    'svn_user_routers' => [
        'Svnrep/GetSvnUserRepList',
        'Svnrep/GetUserRepCon',

        /**
         * 二次授权范围
         */
        //获取仓库树
        'Svnrep/GetRepTree',
        //获取仓库路径的权限列表
        'Svnrep/GetRepPathAllPri',
        //获取用户列表
        'Svnuser/GetUserList',
        //获取分组列表
        'Svngroup/GetGroupList',
        //获取分组成员
        'Svngroup/GetGroupMember',
        //获取别名列表
        'Svnaliase/GetAliaseList',
        //为某仓库路径下增加权限
        'Svnrep/AddRepPathPri',
        //修改某个仓库路径下的权限
        'Svnrep/EditRepPathPri',
        //修改某个仓库路径下的权限
        'Svnrep/DelRepPathPri',


        'Common/Logout',

        'Svn/GetCheckout',
        'Svn/GetStatus',

        'Personal/EditSvnUserPass',
    ],
];
