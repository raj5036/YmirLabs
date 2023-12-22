const providerForBankId = {
    nb: 'no-bankid',
    sv: 'se-bankid'
};

const urlProvider = {
    co: 'http://snapmed.co',
    no: 'http://snapmed.no',
    se: 'http://snapmed.se',
    uk: 'http://snapmed.co.uk',
    de: 'http://snapmed.de'
};

export default {
    getProviderForBankId (locale) {
        return providerForBankId[locale] || null;
    },
    getRedirectUrl (region) {
        return urlProvider[region] || null;
    }
};
