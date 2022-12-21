import Vue from 'vue';
import ViewUI from 'view-design';
import VueRouter from 'vue-router';
import Routers from './router';
import Util from './libs/util';
import App from './app.vue';
import 'view-design/dist/styles/iview.css';

/**
 * 以下为手动安装配置的依赖
 * 通过 Vue.prototype.$name 的方式 使$name在所有的Vue实例中可用
 */
//安装babel-polyfill进行ES6转ES5来兼容ie8+
import "babel-polyfill";

//http请求 -> axios
import axios from 'axios';
Vue.prototype.$axios = axios;

import VueClipboard from 'vue-clipboard2';
Vue.use(VueClipboard);

import JsonExcel from 'vue-json-excel';
Vue.component('downloadExcel', JsonExcel);

Vue.use(VueRouter);
Vue.use(ViewUI);

// 路由配置
const RouterConfig = {
    mode: 'hash',
    routes: Routers
};
const router = new VueRouter(RouterConfig);

//由于使用动态路由 登录后立即 router.push 没有指定的路由会抛出错误但不影响运行 此配置能捕获错误抛出
const originalPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location, onResolve, onReject) {
    if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject)
    return originalPush.call(this, location).catch(err => err)
}

//路由拦截器 beforeEach
let noRouter = true
router.beforeEach((to, from, next) => {
    ViewUI.LoadingBar.start();
    Util.title(to.meta.title);
    //页面跳转逻辑
    if (sessionStorage.token) {
        if (noRouter && sessionStorage.route) {
            var accessRouteses = JSON.parse(sessionStorage.route);
            accessRouteses.children = routerChildren(accessRouteses.children);
            accessRouteses.component = routerCom(accessRouteses.component);
            router.addRoute(accessRouteses);
            noRouter = false;
            next({
                //保证路由添加完了再进入页面 可以理解为重进一次
                ...to,
                // 重进一次 不保留重复历史
                replace: true,
            });
        }
        next();
    } else {
        if (to.path == '/login') {
            next();
        } else {
            next({ path: '/login' })
        }
    }
});

//路由拦截器 afterEach
router.afterEach((to, from, next) => {
    ViewUI.LoadingBar.finish();
    window.scrollTo(0, 0);
});

/**
 * 请求拦截器
 */
axios.interceptors.request.use(function (config) {
    if (window.sessionStorage.token) {
        //将token加入到请求头
        config.headers.common['token'] = window.sessionStorage.token;
    }
    return config
}, function (error) {
    return Promise.reject(error);
});

/**
 * 响应拦截器
 */
axios.interceptors.response.use(function (response) {
    if (response.data.code != undefined && response.data.code != '') {
        if (response.data.code == 401) {
            sessionStorage.clear();
            router.push('/');
        }
    }
    return response;
}, function (error) {
    return Promise.reject(error);
});

//对子路由的component解析
function routerChildren(children) {
    children.forEach(v => {
        v.component = routerCom(v.component);
        if (v.children != undefined) {
            v.children = routerChildren(v.children)
        }
    })
    return children
}

//对路由的component解析
function routerCom(path) {
    return (resolve) => require([`@/views/${path}`], resolve);
}

new Vue({
    el: '#app',
    router: router,
    render: h => h(App)
});
