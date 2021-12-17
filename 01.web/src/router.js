const publicRoutes = [
    {
        name: 'login',
        path: '/login',
        meta: {
            requireAuth: false,
        },
        component: (resolve) => require(['./views/login/login.vue'], resolve)
    },
    {
        name: 'manage',
        path: '/',
        redirect: 'repository',
        meta: {
            requireAuth: true,
            roles: [1, 2]
        },
        component: (resolve) => require(['./views/layout/layout.vue'], resolve),
        children: [
            {
                name: 'repository',
                path: 'repository',
                meta: {
                    title: '服务总览',
                    isNav: true,
                    requireAuth: true,
                    roles: [1, 2]
                },
                component: (resolve) => require(['./views/analysis/analysis.vue'], resolve)
            },
            {
                name: 'analysis',
                path: 'analysis',
                meta: {
                    title: '仓库管理',
                    isNav: true,
                    requireAuth: true,
                    roles: [1, 2]
                },
                component: (resolve) => require(['./views/repository/repository.vue'], resolve)
            },
            {
                name: 'user',
                path: 'user',
                meta: {
                    title: '用户管理',
                    isNav: true,
                    requireAuth: true,
                    roles: [1]
                },
                component: (resolve) => require(['./views/user/user.vue'], resolve)
            },
            {
                name: 'group',
                path: 'group',
                meta: {
                    title: '分组管理',
                    isNav: true,
                    requireAuth: true,
                    roles: [1]
                },
                component: (resolve) => require(['./views/group/group.vue'], resolve)
            },
            {
                name: 'sys',
                path: 'setting',
                meta: {
                    title: '系统设置',
                    isNav: true,
                    requireAuth: true,
                    roles: [1]
                },
                component: (resolve) => require(['./views/setting/setting.vue'], resolve),
            },
        ]
    },
];



export default publicRoutes;
