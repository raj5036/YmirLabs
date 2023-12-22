import '@babel/polyfill';
import Vue from 'vue';
import VueChartist from 'vue-chartist';

import '@/plugins/axios';
import '@/plugins/vuetify';
import i18n from '@/plugins/i18n';

import router from '@/router';
import store from '@/store';
import App from '@/App';

Vue.use(VueChartist);

Vue.config.productionTip = false;

new Vue({
    router,
    store,
    i18n,
    render: h => h(App)
}).$mount('#app');
