import Vue from 'vue';
import App from './app.vue';
import Routers from './router';
import Vuex from 'vuex';
import store from './vuex/store';
import ViewUI from 'view-design';
Vue.use(ViewUI);

import SlideVerify from 'vue-monoplasty-slide-verify';
Vue.use(SlideVerify);

import VueRouter from 'vue-router';
Vue.use(VueRouter);

import Util from './libs/util';
Vue.prototype.$util = Util;

import axios from 'axios';
Vue.prototype.$axios = axios;

import echarts from 'echarts'
Vue.prototype.$echarts = echarts

import 'view-design/dist/styles/iview.css';

/**
 * 路由配置
 */
const RouterConfig = {
    mode: 'hash',
    routes: Routers
};

const router = new VueRouter(RouterConfig);

router.beforeEach((to, from, next) => {
    ViewUI.LoadingBar.start();
    Util.title(to.meta.title);
    if (to.matched.some(m => m.meta.requireAuth)) {
        if (window.sessionStorage.token) {
            if (to.meta.roles.indexOf(Number(window.sessionStorage.roleid)) == -1) {
                // console.log("要访问的页面需要登录->本地存在token->要访问的页面不在允许范围内->跳转到首页");
                next('/repository');
            }else if (to.path == '/login') {
                // console.log("要访问的页面需要登录->本地存在token->要访问的页面为登录页,则跳转到首页");
                next({ path: '/repository' })
            } else {
                // console.log("要访问的页面需要登录->本地存在token->要访问的页面不为登录页,则放行");
                next();
            }
        } else {
            // console.log("要访问的页面需要登录->本地不存在token->跳转到登录页");
            next({ path: '/login' })
        }
    } else if (to.path == '/login' && window.sessionStorage.token) {
        // console.log("要访问的页面不需要登录->要访问的页面为登录页且存在token->跳转到首页");
        next({ path: '/repository' })
    }
    else {
        // console.log("要访问的页面不需要登录，则放行");
        next();
    }
});

router.afterEach((to, from, next) => {
    ViewUI.LoadingBar.finish();
    window.scrollTo(0, 0);
});

/**
 * 配置请求拦截器
 */
axios.defaults.baseURL = "/"; //公共url

axios.interceptors.request.use(config => {
    if (window.sessionStorage.token) {
        config.headers.common['token'] = window.sessionStorage.token; //将token加入到请求头
    } else {
    }
    return config
}, error => {
    // 异常
    if (error.response) {
        // switch (error.response.status) {
        //     case 401:
        //         sessionStorage.removeItem('token');
        //         router.replace({
        //             path: '/',
        //             query: { redirect: router.currentRoute.fullPath }//登录成功后跳入浏览的当前页面
        //         })
        // }
    }
})

/**
 * 配置响应拦截器
 */
axios.interceptors.response.use(response => {
    if (response.data.code != undefined && response.data.code != '') {
        if (response.data.code != 200) {
            window.sessionStorage.removeItem('token');//清除本地token
            router.push('/');
        }
    }
    return response
}, error => {
    // 异常
    return Promise.reject(error)
})

new Vue({
    el: '#app',
    router: router,
    store,
    render: h => h(App)
});
