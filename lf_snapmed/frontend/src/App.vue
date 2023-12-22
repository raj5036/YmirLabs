<template>
    <div id="app">
        <page-header v-if="showPageHeader"/>
        <main role="Main" :class="pageClass">
            <transition name="fade">
                <router-view :key="$route.fullPath"></router-view>
            </transition>
        </main>
    </div>
</template>

<script>
import { kebabCase } from 'lodash';
import { mapState } from 'vuex';
import PageHeader from '@common/PageHeader';

export default {
    metaInfo () {
        return {
            titleTemplate: value => {
                if (value !== 'Dr. Dropin Hud') {
                    return `${value} - Dr. Dropin Hud`;
                }
                return value;
            },
            meta: [{
                vmid: 'description',
                name: 'description',
                content: this.$t('meta.description')
            }, {
                vmid: 'ogimg',
                name: 'og:image',
                property: 'og:image',
                content: this.$t('meta.og.image')
            }, {
                vmid: 'og:description',
                name: 'og:description',
                content: this.$t('meta.description')
            }],
            htmlAttrs: {
                lang: this.$i18n.locale
            },
            script: [{
                vmid: 'gtag',
                async: '',
                src: `https://www.googletagmanager.com/gtag/js?id=${process.env.VUE_APP_GA_ID}`
            }, {
                vmid: 'gtagcontent',
                innerHTML: `window.dataLayer = window.dataLayer || [];
                            function gtag(){dataLayer.push(arguments);}
                            gtag('js', new Date());
                            gtag('config', '${process.env.VUE_APP_GA_ID}');`
            }]
        };
    },
    computed: {
        ...mapState(['showPageHeader', 'showHeader', 'showHeaderBackButton', 'stickyHeader', 'redirectUrl', 'region', 'showStepper']),
        pageClass () {
            // return this.$route.name;
            return (this.$route.name && kebabCase(this.$route.name)) || '';
        }
    },
    methods: {
        amount (hours) {
            const {AMOUNT_KEY} = this.$store.getters.AMOUNT(false, hours);
            return AMOUNT_KEY;
        }
    },
    components: {
        PageHeader
    },
    created () {
        localStorage.clear();
    }
};
</script>

<style lang="scss">
@import '@/scss/app.scss';
@import '@/scss/common.scss';
@import '@/scss/uk.scss';
</style>
