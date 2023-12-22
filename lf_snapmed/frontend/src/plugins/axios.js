'use strict';

import Vue from 'vue';
import axios from 'axios';
import { store } from '@/store';

// Full config:  https://github.com/axios/axios#request-config
// axios.defaults.baseURL = process.env.baseURL || process.env.apiUrl || '';
// axios.defaults.headers.common['Authorization'] = AUTH_TOKEN;
// axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';

let config = {
    debug: process.env.NODE_ENV !== 'production',
    baseURL: '/api' // process.env.baseURL || process.env.apiUrl || ""
    // timeout: 60 * 1000, // Timeout
    // withCredentials: true, // Check cross-site Access-Control
};

const _axios = axios.create(config);

const token = store.state.token;
if (token) {
    _axios.defaults.headers.common['token'] = token;
}

const log = (level, ...messages) => console[level]('[Axios]', ...messages);

_axios.interceptors.request.use(
    function (config) {
        let token = store.state.token;
        if (token) {
            config.headers.common['token'] = token;
        }
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
        if (config.debug) {
            log('info', `[${response.status} ${response.statusText}]`, `[${response.config.method.toUpperCase()}]`, response.config.url);
        }
        // Do something with response data
        return response;
    },
    function (error) {
        if (!error.response ||
            error.response.status === 400 ||
            error.response.status === 401) {
            // show login page for uk region
            let ignoreApis = ['/api/upload', '/api/verify-otp', '/api/verify-password', '/api/login'];
            let importantApis = ['/api/diagnoses'];
            if (store.state.region === 'uk' &&
                (!ignoreApis.includes(error.response.config.url) || (error.response.data.error === 'Provided token is expired.'))) {
                store.state.uk = {
                    authentication: true,
                    secondAuthentication: false,
                    flowStart: false,
                    patientDetails: false,
                    medicalIssue: false,
                    medicalHistory: false,
                    userAuthentication: false,
                    userHistory: false,
                    payment: false,
                    tokenExpired: true,
                    stepper: {
                        checkStepper: 0
                    }
                };
                store.state.checkFlow = false;
                store.state.videoFlow = false;
                store.state.check.answers.key = null;
                store.state.check.answers.password = null;
                store.state.check.answers.email = null;
                store.state.check.answers.phone = null;
                store.state.user.email = null;
                store.state.token = null;
                store.state.uk.authentication = true;
                localStorage.setItem('data', JSON.stringify(store.state));
                window.location.replace('/login');
            }
            if (!store.state.partner.active) { // do not log out user on error if partner
                // Remove token
                _axios.defaults.headers.common['token'] = null;
                sessionStorage.removeItem('token');
            }
            if (importantApis.includes(error.response.config.url) || (error.response.data.error === 'Provided token is expired.')) {
                store.state.loggedin = false;
                store.state.bankid.operationUrl = null;
                store.state.bankid.operationId = null;
                store.state.check.answers.bankid = null;
            }
        }
        if (config.debug) {
            log('error', 'Response error:', error);
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
