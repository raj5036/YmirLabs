<template>
    <section class="landing-page">
        <div class="page-checkup__heading">
            <span>{{ $t("storebrand." + flow + ".title") }}</span>
        </div>
        <div class="landing-page__intro">
            {{ $t("storebrand." + flow + ".intro") }}
        </div>
        <ul class="landing-page__list">
            <li>
                <img src="@assets/icons/check-bullets.svg" title="Snapmed" alt="Snapmed" class="landing-page__list-icon">
                <span>{{ $t("storebrand." + flow + ".list_1") }}</span>
            </li>
            <li>
                <img src="@assets/icons/check-bullets.svg" title="Snapmed" alt="Snapmed" class="landing-page__list-icon">
                <span>{{ $t("storebrand." + flow + ".list_2") }}</span>
            </li>
            <li>
                <img src="@assets/icons/check-bullets.svg" title="Snapmed" alt="Snapmed" class="landing-page__list-icon">
                <span>{{ $t("storebrand." + flow + ".list_3", [amount]) }}</span>
            </li>
        </ul>
        <button @click="proceed" class="dd-button proceed-button">
            {{ $t("storebrand." + flow + ".button") }}
        </button>
    </section>
</template>

<script>
import { ACTIONS } from '@/store';
import { mapState, mapActions } from 'vuex';
import helperMethods from '@/helpers.js';

export default {
    name: 'landing-common',
    metaInfo () {
        return {
            title: 'Home'
        };
    },
    data () {
        return {
            doctors: null,
            flow: null
        };
    },
    methods: {
        ...helperMethods,
        ...mapActions({
            showHeader: ACTIONS.SHOW_HEADER,
            setCheckFlow: ACTIONS.SET_CHECK_FLOW,
            setVideoFlow: ACTIONS.SET_VIDEO_FLOW,
            restoreState: ACTIONS.RESTORE_STATE,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            setTagRid: ACTIONS.SET_TAGRID,
            setFlow: ACTIONS.SET_FLOW
        }),
        proceed () {
            if (this.flow === 'picture') {
                this.$router.push('/book?flow=picture');
            } else if (this.flow === 'video') {
                this.$router.push('/book?flow=video');
            }
        },
        getFlow () {
            let flow = this.$route.query.flow;
            this.setFlow(flow);
            this.flow = flow;
        }
    },
    computed: {
        ...mapState({
            region: state => state.region,
            goingBack: state => state.goingBack
        }),
        amount () {
            const currencyLocale = `VUE_APP_CURRENCY_${process.env.VUE_APP_CURRENCY}_LOCALE`;
            if (this.flow === 'video') {
                const amountKey = `VUE_APP_AMOUNT_VIDEO_${process.env.VUE_APP_CURRENCY}`;
                return this.$n(process.env[amountKey], 'currencyNoCents', process.env[currencyLocale]);
            } else {
                const amountKey = `VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_NEW_USER`;
                return this.$n(process.env[amountKey], 'currencyNoCents', process.env[currencyLocale]);
            }
        },
        returningAmount () {
            const currencyLocale = `VUE_APP_CURRENCY_${process.env.VUE_APP_CURRENCY}_LOCALE`;
            const amountKey = `VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_RETURNING_USER`;
            return this.$n(process.env[amountKey], 'currencyNoCents', process.env[currencyLocale]);
        }
    },
    async created () {
        await this.getFlow();
        this.showPageHeader(true);
        let tagrid = this.$store.getters.GET_COOKIE('tagrid');
        if (tagrid) {
            this.setTagRid(tagrid);
        }
    }
};
</script>

<style lang="scss">
.landing-page {
    @include grid-width-limit-padding-no;
    color: #fff;
    &__intro{
        text-align: center;
        font-size: 20px;
        margin: 64px auto;
        @include breakpoint(small only) {
            font-size: $font-medium;
            padding-inline: spacing(2);
        }
    }
    &__list {
        list-style: none;
        margin: 12px 0;
        padding: 0;
        li {
            position: relative;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-left: 40px;
            padding: spacing(1) 0;
            color: #5E4098;
            font-weight: 600;
            border-bottom: none;
            @include breakpoint(small only) {
                margin-left: 32px;
            }
        }
        &-icon {
            margin-right: 24px;
            width: 24px;
        }
    }
    span{
        color: #fff;
        font-size: 20px;
        font-weight: 400;
    }
}
</style>
