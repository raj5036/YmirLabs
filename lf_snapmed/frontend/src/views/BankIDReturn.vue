<template>
    <div class="bankid-login__return">
        Din BankID er verifisert
    </div>
</template>

<script>

import { mapActions, mapState } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'bank-id-return',
    computed: {
        ...mapState({
            locale: state => state.locale
        })
    },
    methods: {
        ...mapActions({
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER
        })
    },
    mounted () {
        this.showPageHeader(false);
        if (this.locale !== 'sv') {
            window.parent.postMessage({'event': 'bankid-finished'}, '*');
        } else {
            setTimeout(function () {
                window.parent.opener.postMessage({'event': 'bankid-finished'}, '*');
                window.close();
            }, 1000);
        }
    }
};
</script>
