'use strict';

import Vue from 'vue';
import axios from 'axios';
import router from '@/router';
import store from '@/store';

// Full config:  https://github.com/axios/axios#request-config
// axios.defaults.baseURL = process.env.baseURL || process.env.apiUrl || '';
// axios.defaults.headers.common['Authorization'] = AUTH_TOKEN;
// axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';

const config = {
    debug: process.env.NODE_ENV !== 'production',
    baseURL: '/admin/api' // process.env.baseURL || process.env.apiUrl || ""
    // timeout: 60 * 1000, // Timeout
    // withCredentials: true, // Check cross-site Access-Control
};

const _axios = axios.create(config);

const token = sessionStorage.getItem('token');
if (token) {
    _axios.defaults.headers.common['token'] = token;
}

const log = (level, ...messages) => console[level]('[Axios]', ...messages);

_axios.interceptors.request.use(
    function (config) {
        // Do something before request is sent
        return config;
    },
    function (error) {
        if (config.debug) log('error', 'Request error:', error);
        // Do something with request error
        return Promise.reject(error);
    }
);

// Add a response interceptor
_axios.interceptors.response.use(
    function (response) {
        // Set token in header
        if (response.status === 200 && response.data.hasOwnProperty('token')) {
            _axios.defaults.headers.common['token'] = response.data.token;
            sessionStorage.setItem('token', response.data.token);
        }
        if (response.status === 200 && response.data.hasOwnProperty('refreshToken')) {
            sessionStorage.setItem('refreshToken', response.data.refreshToken);
        }
        if (config.debug) {
            log('info', `[${response.status} ${response.statusText}]`, `[${response.config.method.toUpperCase()}]`, response.config.url);
        }
        // Do something with response data
        return response;
    },
    function (error) {
        const response = error.response || { config: { method: 'unknown', debug: config.debug } };
        if (!response ||
            response.status === 400 ||
            response.status === 401) {
            // Remove token and then goto "home page"
            _axios.defaults.headers.common['token'] = null;
            sessionStorage.removeItem('token');
            sessionStorage.removeItem('refreshToken');
            store.commit('setAuthenticated', false);
            router.push('/');
        }
        if (config.debug) {
            log('error', `[${response.status} ${response.statusText}]`, `[${response.config.method.toUpperCase()}]`, response.config.url, error);
        }
        // Do something with response error
        return Promise.reject(error);
    }
);

Plugin.install = function (Vue, options) {
    Vue.axios = _axios;
    window.axios = _axios;
    Object.defineProperties(Vue.prototype, {
        axios: {
            get () {
                return _axios;
            }
        },
        $axios: {
            get () {
                return _axios;
            }
        }
    });
};

Vue.use(Plugin);

export default Plugin;
