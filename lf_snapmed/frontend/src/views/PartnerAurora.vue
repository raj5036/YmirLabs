<template>
    <transition name="fade">
        <div class="page">
            <div v-if="isProcessing" class="page__processing"><svg-loader /></div>
        </div>
    </transition>
</template>

<script>
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';
import cryptoJS from 'crypto-js';

export default {
    name: 'partner-aurora',
    data () {
        return {
            isProcessing: true
        };
    },
    methods: {
        decryptText (token) {
            // remove spaces
            token = token.split(' ').join('+');
            // decrypt token
            var Key = cryptoJS.enc.Utf8.parse(process.env.VUE_APP_SHARED_KEY);
            var IV = cryptoJS.enc.Utf8.parse(process.env.VUE_APP_SALT);
            var decryptedText = cryptoJS.AES.decrypt(token, Key, {
                iv: IV,
                mode: cryptoJS.mode.CBC,
                padding: cryptoJS.pad.Pkcs7
            });
            decryptedText = decryptedText.toString(cryptoJS.enc.Utf8);
            // remove trailing ""
            decryptedText = decryptedText.split('"').join('');
            return decryptedText;
        },
        ...mapActions({
            partnerAuroraCheck: ACTIONS.PARTNER_AURORA_CHECK
        })
    },
    created () {
        const queryString = window.location.search;
        if (queryString) {
            // get url parameters
            const urlParams = new URLSearchParams(queryString);
            var token = urlParams.get('token');
            const service = urlParams.get('service');
            // decrypt the token available in query params
            var decryptedText = this.decryptText(token);
            // check whether the service provided is correct or not
            if (service === process.env.VUE_APP_AURORA_SERVICE_NAME) {
                this.partnerAuroraCheck({'token': decryptedText}).then(loginResponse => {
                    if (loginResponse) {
                        this.isProcessing = false;
                        this.$router.push('/partner/select');
                    } else {
                        this.isProcessing = false;
                        this.$router.push({ name: 'partner-aurora-error' });
                    }
                }).catch(error => {
                    if (error.response && error.response.data && error.response.data.error) {
                        this.isProcessing = false;
                        this.$router.push({ name: 'partner-aurora-error' });
                    }
                });
            }
        } else {
            this.isProcessing = false;
            this.$router.push({ name: 'partner-aurora-error' });
        }
    }
};
</script>

<style lang="scss">
.page {
    &__processing {
        text-align: center;
        margin: 34px auto;
        width: 100px;
    }
}
</style>
