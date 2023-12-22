<template>
    <section class="page-video" v-if="!paymentOk && !complete">
        <header class="page-video__header">
            <h3 class="page-video__header-steps-heading">{{$t('page.video.steps', [step, totalSteps])}}</h3>
            <h1 v-t="pagekey"></h1>
        </header>
        <article class="page-video__steps">
            <transition :name="transitionName">
                <router-view class="page-video__content" v-on:addPaymentInfo="addPaymentInfo"/>
            </transition>
        </article>
    </section>
    <section v-else>
        <div v-if="this.isPartnerFlow">
            <partner-video-confirm></partner-video-confirm>
        </div>
        <div v-else>
            <ThankYou/>
        </div>
    </section>
</template>

<script>

import {mapState, mapGetters, mapActions} from 'vuex';
import { GETTERS, ACTIONS } from '@/store';
import ThankYou from './ThankYou.vue';

export default {
    name: 'Video',
    data () {
        return {
            transitionName: 'slide-left',
            totalSteps: 2,
            paymentOk: false,
            complete: false
        };
    },
    metaInfo () {
        return {
            title: this.$t('title.video')
        };
    },
    components: {
        ThankYou
    },
    computed: {
        pagekey () {
            // If the route path ends with a slash we need to remove it to enable text lookup.
            let path = this.$route.path.endsWith('/') ? this.$route.path.slice(0, -1) : this.$route.path;
            return `page${path.replace(/\//g, '.')}.short_title`;
        },
        step () {
            return this.$route.name === 'confirm' ? 2 : 1;
        },
        ...mapState({
            locale: state => state.locale,
            env: process.env.NODE_ENV
        }),
        ...mapGetters({
            isAuroraFlow: GETTERS.IS_AURORA_FLOW,
            userUuid: GETTERS.USER_UUID,
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE
        })
    },
    methods: {
        ...mapActions({
            restoreState: ACTIONS.RESTORE_STATE,
            removeLandingPage: ACTIONS.REMOVE_LANDING_PAGE,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            showStepper: ACTIONS.SHOW_STEPPER
        }),
        addPaymentInfo (value) {
            this.paymentOk = value.paymentOk;
            this.complete = value.complete;
        }
    },
    beforeRouteUpdate (to, from, next) {
        if (to.name === 'book') {
            this.transitionName = 'slide-right';
        } else if (to.name === 'confirm' || to.name === 'confirm-partner') {
            this.transitionName = 'slide-left';
        } else {
            this.transitionName = 'fade';
        }
        next();
    },
    mounted () {
        if (document.location.href === localStorage.getItem('website_url')) {
            this.restoreState();
        }
        if (!this.isPartnerFlow) {
            this.showPageHeader(true);
            this.showStepper();
        }
        this.removeLandingPage();
        localStorage.setItem('website_url', document.location.href);
        this.question = this.questions[this.questions.length - 1];
        this.stepCount = (this.questions.length * 100) / 20;
    }
};
</script>
