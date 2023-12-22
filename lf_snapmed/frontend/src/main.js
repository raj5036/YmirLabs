import Vue from 'vue';
import './plugins/axios';
import i18n from './i18n';
import router from './router';
import { store } from './store';
import App from './App.vue';
import SvgLoader from '@common/SvgLoader.vue';
import Tick from '@common/Tick.vue';
import Cross from '@/components/uk/Cross.vue';
import SvgAsset from '@common/SvgAsset.vue';
import moment from 'moment';
import 'moment/locale/nb';
import vSelect from 'vue-select';

Vue.config.productionTip = false;

Vue.component('svg-loader', SvgLoader);
Vue.component('svg-asset', SvgAsset);
Vue.component('svg-tick', Tick);
Vue.component('svg-cross', Cross);
Vue.component('v-select', vSelect);

moment.locale(process.env.VUE_APP_I18N_LOCALE);
Vue.filter('formatDate', function (value) {
    if (value) {
        return moment(String(value)).format('LLLL');
    }
    return value;
});

// h-scroll directive
Vue.directive('h-scroll', {
    inserted: function (el, binding) {
        let f = function (evt) {
            if (binding.value(evt, el)) {
                el.removeEventListener('scroll', f);
            }
        };
        el.addEventListener('scroll', f);
    }
});

// v-scroll directive
Vue.directive('scroll', {
    inserted: function (el, binding) {
        let f = function (evt) {
            if (binding.value(evt, el)) {
                el.removeEventListener('scroll', f);
            }
        };
        el.addEventListener('scroll', f);
    }
});

// h-scroll-reset directive
Vue.directive('h-scroll-reset', {
    componentUpdated: function (el, binding) {
        if (binding.value !== binding.oldValue) {
            el.scrollLeft = 0;
        }
    }
});

// to change input to upper case
Vue.directive('touppercase', {
    update (el) {
        el.value = el.value.toUpperCase();
    }
});

(function () {
    // let scrollEnabled = false;
    // // Create a global function that is used to enable scrolling when first question is answered.
    // Vue.enableScrolling = () => {
    //     // We never turn on scroll flag anymore. Functionality is still here, but we never enable it.
    //     scrollEnabled = false;
    // };
    // Create vue instance
    const app = new Vue({
        i18n,
        router,
        store,
        render: h => h(App)
    });
    app.$mount('#app');
})();
