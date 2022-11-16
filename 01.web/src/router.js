const routers = [
    {
        name: 'login',
        path: '/login',
        meta: {
            title: '',
            requireAuth: false
        },
        component: (resolve) => require(['@/views/login/index.vue'], resolve)
    }
];
export default routers;