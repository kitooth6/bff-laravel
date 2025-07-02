require("./bootstrap");

import Vue from "vue";
import VueRouter from "vue-router";
import Vuex from "vuex";

// Vue プラグインを使用
Vue.use(VueRouter);
Vue.use(Vuex);

// ルーター設定
const routes = [
    { path: "/", component: () => import("./components/Home.vue") },
    { path: "/about", component: () => import("./components/About.vue") },
];

const router = new VueRouter({
    mode: "history",
    routes,
});

// Vuex ストア
const store = new Vuex.Store({
    state: {
        user: null
    },
    mutations: {
        setUser(state, user) {
            state.user = user;
        }
    }
});

// Vue インスタンス
const app = new Vue({
    el: "#app",
    router,
    store,
});
