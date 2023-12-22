<template>
    <section class="partner-select-product">
        <div class="page-checkup__heading">
               <span>{{ $t('storebrand.select.heading') }}</span>
        </div>
        <div class="partner-select-product__container partner-select-product__choices">
            <a
                @click="selectPicture"
                class="partner-select-product__choice-link">
                <div class="partner-select-product__choice-link-text">
                    <div class="partner-select-product__choice-link-heading">{{ $t('storebrand.select.picture-header') }}</div>
                    <div class="partner-select-product__choice-link-intro">{{ $t('storebrand.select.picture-text') }}</div>
                </div>
            </a>
            <a
                @click="selectVideo"
                class="partner-select-product__choice-link">
                <div class="partner-select-product__choice-link-text">
                    <div class="partner-select-product__choice-link-heading">{{ $t('storebrand.select.video-header') }}</div>
                    <div class="partner-select-product__choice-link-intro">{{ $t('storebrand.select.video-text') }}</div>
                </div>
            </a>
        </div>
    </section>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import { GETTERS, ACTIONS } from '@/store';

export default {
    name: 'partner-select-product',
    computed: {
        ...mapGetters({
            isPartnerCheckComplete: [GETTERS.IS_PARTNER_CHECK_COMPLETE],
            isPartnerVideoComplete: [GETTERS.IS_PARTNER_VIDEO_COMPLETE]
        })
    },
    methods: {
        ...mapActions({
            setVideoFlowState: ACTIONS.SET_PARTNER_FLOW_STATE_VIDEO,
            setCheckFlowState: ACTIONS.SET_PARTNER_FLOW_STATE_CHECK,
            introPage: ACTIONS.INTRO_PAGE,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER
        }),
        selectVideo () {
            this.setVideoFlowState();
            this.$router.push('/intro?flow=video');
        },
        selectPicture () {
            this.setCheckFlowState();
            this.$router.push('/intro?flow=picture');
        }
    },
    mounted () {
        this.introPage(true);
        this.showPageHeader(true);
    }
};
</script>

<style lang="scss" scoped>
.partner-select-product{
    @include grid-width-limit-padding-no;
    @include breakpoint (small only) {
        padding-inline: 16px;
    }
    &__container{
        max-width: $partner-max-width;
        margin: 0 auto;
    }
    &__choices{
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin: spacing(10) spacing(5) spacing(5);
        @include breakpoint (small only) {
            margin: spacing(5) spacing(2);
        }
    }
    &__choice-link{
        display: flex;
        padding: spacing(2) spacing(2);
        border: solid 1px color(mid-purple);
        border-radius: 8px;
        width: 100%;
        align-items: center;
        text-decoration: none;
        background: #fff;
        &-heading{
            font-size: 17px;
            margin-bottom: .125em;
            color: #000;
        }
        &-intro{
            padding-right: spacing(2);
            color: rgba(0, 0, 0, 0.72);
            font-size: 15px;
        }
        &:not(:last-child){
            margin-bottom: spacing(2);
        }
    }
}
</style>
