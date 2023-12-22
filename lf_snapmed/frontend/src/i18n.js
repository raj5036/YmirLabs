import Vue from 'vue';
import VueI18n from 'vue-i18n';

Vue.use(VueI18n);

const currencyFormats = {
    'nb': { currency: { style: 'currency', currency: 'NOK', currencyDisplay: 'symbol' }, currencyNoCents: { style: 'currency', currency: 'NOK', minimumFractionDigits: 0, maximumFractionDigits: 0 } },
    'sv': { currency: { style: 'currency', currency: 'SEK', currencyDisplay: 'symbol' }, currencyNoCents: { style: 'currency', currency: 'SEK', minimumFractionDigits: 0, maximumFractionDigits: 0 } }
};

function loadLocaleMessages () {
    const locales = require.context('./locales', true, /[a-z0-9]+\.json$/i);
    const messages = {};
    locales.keys().forEach(key => {
        const matched = key.match(/([a-z0-9]+)\./i);
        if (matched && matched.length > 1) {
            const locale = matched[1];
            messages[locale] = locales(key);
        }
    });
    return messages;
}

export default new VueI18n({
    locale: process.env.VUE_APP_I18N_LOCALE || 'en',
    fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
    messages: loadLocaleMessages(),
    numberFormats: currencyFormats
});
