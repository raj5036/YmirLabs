import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export const SET_SNACKBAR_DISPLAY = 'SET_SNACKBAR_DISPLAY';
export const SET_SNACKBAR_MESSAGE = 'SET_SNACKBAR_MESSAGE';
export const SET_CURRENTUSER_UUID = 'SET_CCURRENTUSER_UUID';
export const SET_SUPERADMIN = 'SET_SUPERADMIN';
export const SET_REFRESH_TASK = 'SET_REFRESH_TASK';
export const REMOVE_REFRESH_TASK = 'REMOVE_REFRESH_TASK';

export const ACTIONS = {
    LOGIN: 'LOGIN',
    VERIFY: 'VERIFY',
    CURRENTUSER_UUID_RECEIVED: 'CURRENTUSER_UUID_RECEIVED',
    REFRESH_TOKEN: 'REFRESH_TOKEN',
    SEND_OTP: 'SEND_OTP',
    UPDATE_PASSWORD: 'UPDATE_PASSWORD',
    CHANGE_PASSWORD: 'CHANGE_PASSWORD'
};

const setAuthenticated = 'setAuthenticated';

export let store = new Vuex.Store({
    state: {
        isAuthenticated: !!sessionStorage.getItem('token'),
        isSuperadmin: sessionStorage.getItem('superadmin') === 'true',
        currentUserUUID: false,
        snackbarMessage: '',
        snackbarShow: false,
        test: 'hello',
        refreshTimeout: 3000000, // 50 minutes in millis
        refreshTaskHandler: null
    },
    getters: {
        isAuthenticated: state => state.isAuthenticated,
        isSuperadmin: state => state.isSuperadmin,
        currentUserUUID: state => state.currentUserUUID,
        refreshTimeout: state => state.refreshTimeout
    },
    mutations: {
        [SET_SNACKBAR_DISPLAY] (state, value) {
            state.snackbarShow = value;
        },
        [SET_SNACKBAR_MESSAGE] (state, value) {
            state.snackbarMessage = value;
            state.snackbarShow = true;
        },
        [SET_CURRENTUSER_UUID] (state, value) {
            sessionStorage.setItem('currentUserUUID', value);
            state.currentUserUUID = value;
        },
        [SET_SUPERADMIN] (state, value) {
            state.isSuperadmin = value;
        },
        [SET_REFRESH_TASK] (state, value) {
            state.refreshTaskHandler = value;
        },
        [REMOVE_REFRESH_TASK] (state) {
            clearTimeout(state.refreshTaskHandler);
        },
        [setAuthenticated] (state, value) {
            state.isAuthenticated = value;
        }
    },
    actions: {
        [ACTIONS.CURRENTUSER_UUID_RECEIVED] ({ commit }, value) {
            commit(SET_CURRENTUSER_UUID, value);
        },
        async [ACTIONS.LOGIN] ({ commit }, data) {
            try {
                const response = await Vue.axios.post('/login', data);
                if (response && response.data && response.data.token) {
                    commit(SET_CURRENTUSER_UUID, response.data.uuid);
                    sessionStorage.setItem('superadmin', response.data.superadmin);
                    commit(SET_SUPERADMIN, response.data.superadmin);
                    return true;
                }
            } catch (error) {
                console.warn('There was an error during authentication.');
            }
            return false;
        },
        async [ACTIONS.CHANGE_PASSWORD] ({ commit }, data) {
            try {
                const response = await Vue.axios.post('/change-password', data);
                if (response && response.data) {
                    return response.data;
                }
            } catch (error) {
                console.warn('There was an error during password reset.');
                return false;
            }
        },
        async [ACTIONS.UPDATE_PASSWORD] ({ commit }, data) {
            try {
                const response = await Vue.axios.post('/update-password', data);
                if (response && response.data && response.data.status) {
                    return true;
                } else {
                    return false;
                }
            } catch (error) {
                console.warn('There was an error during password reset.');
                return false;
            }
        },
        async [ACTIONS.SEND_OTP] ({ commit }, data) {
            try {
                const response = await Vue.axios.post('/send-otp', data);
                if (response && response.data) {
                    return response.data;
                }
            } catch (error) {
                console.warn('There was an error during authentication.');
            }
            return false;
        },
        async [ACTIONS.VERIFY] ({ commit, dispatch, state }, data) {
            try {
                const response = await Vue.axios.post(`/verify/${data.otp}`, { 'passwordReset': data.passwordReset });
                if (response && response.data && (response.data.status === 'ok' || response.data.status === 'email_sent')) {
                    commit(setAuthenticated, true);
                    const refreshTask = setTimeout(() => dispatch(ACTIONS.REFRESH_TOKEN), state.refreshTimeout);
                    commit(SET_REFRESH_TASK, refreshTask);
                    return true;
                }
            } catch (error) {
                // We handle error in login with the same as not getting ok here.
            }
            commit(setAuthenticated, false);
            console.warn('There was an error during authentication.');
            return false;
        },
        async [ACTIONS.REFRESH_TOKEN] ({ commit, dispatch, state }) {
            try {
                let config = {
                    headers: {
                        refreshToken: sessionStorage.getItem('refreshToken')
                    }
                };
                await Vue.axios.get('/refresh-token', config);
                const refreshTask = setTimeout(() => dispatch(ACTIONS.REFRESH_TOKEN), state.refreshTimeout);
                commit(SET_REFRESH_TASK, refreshTask);
            } catch (error) {
                console.warn('There was an error fetching token.');
            }
        }
    }
});

export default store;
