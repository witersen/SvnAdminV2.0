const routers = [
    {
        name: 'login',
        path: '/login',
        meta: {
            title: '',
            requireAuth: false
        },
        component: (resolve) => require(['./views/login/index.vue'], resolve)
    },
    {
        name: 'manage',
        path: '/',
        redirect: { name: 'login' },
        meta: {
            title: 'SVNAdmin',
            requireAuth: false
        },
        component: (resolve) => require(['./views/layout/basicLayout/index.vue'], resolve),
        children: [
            {
                name: 'index',
                path: '/index',
                meta: {
                    title: '信息统计',
                    icon: "ios-stats",
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "仓库",
                        num: 1
                    }
                },
                component: (resolve) => require(['./views/index/index.vue'], resolve)
            },
            {
                name: 'repositoryInfo',
                path: '/repositoryInfo',
                meta: {
                    title: 'SVN仓库',
                    icon: 'logo-buffer',
                    requireAuth: true,
                    user_role_id: ['1', '2'],
                    group: {
                        name: "",
                        num: 1
                    }
                },
                component: (resolve) => require(['./views/repositoryInfo/index.vue'], resolve)
            },
            {
                name: 'repositoryUser',
                path: '/repositoryUser',
                meta: {
                    title: 'SVN用户',
                    icon: 'md-person',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "",
                        num: 1
                    }
                },
                component: (resolve) => require(['./views/repositoryUser/index.vue'], resolve),
            },
            {
                name: 'repositoryGroup',
                path: '/repositoryGroup',
                meta: {
                    title: 'SVN分组',
                    icon: 'md-people',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "",
                        num: 1
                    }
                },
                component: (resolve) => require(['./views/repositoryGroup/index.vue'], resolve),
            },
            {
                name: 'systemLog',
                path: '/systemLog',
                meta: {
                    title: '系统日志',
                    icon: 'md-bug',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "运维",
                        num: 2
                    }
                },
                component: (resolve) => require(['./views/systemLog/index.vue'], resolve),
            },
            {
                name: 'crond',
                path: '/crond',
                meta: {
                    title: '任务计划',
                    icon: 'md-bug',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "",
                        num: 2
                    }
                },
                component: (resolve) => require(['./views/systemLog/index.vue'], resolve),
            },
            {
                name: 'personal',
                path: '/personal',
                meta: {
                    title: '个人中心',
                    icon: 'md-cube',
                    requireAuth: true,
                    user_role_id: ['1', '2'],
                    group: {
                        name: "高级",
                        num: 3
                    }
                },
                component: (resolve) => require(['./views/personal/index.vue'], resolve),
            },
            {
                name: 'subadmin',
                path: '/subadmin',
                meta: {
                    title: '子管理员',
                    icon: 'md-settings',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "",
                        num: 3
                    }
                },
                component: (resolve) => require(['./views/advance/index.vue'], resolve),
            },
            {
                name: 'advance',
                path: '/advance',
                meta: {
                    title: '系统配置',
                    icon: 'md-settings',
                    requireAuth: true,
                    user_role_id: ['1'],
                    group: {
                        name: "",
                        num: 3
                    }
                },
                component: (resolve) => require(['./views/advance/index.vue'], resolve),
            }
        ]
    },
];
export default routers;