<template>
<div class="landing-page-uk">
    <div class="doctors-div">
    <span class="landing-headers">Meet our trusted doctors</span>
        <div class="doctors" >
            <div v-for="doctor in doctors" :key="doctor.display_image">
                <div class="doctor-image" :style="{ 'background-image': `url(${`/doctors/${doctor.display_image}`})`}" />
                <span>{{doctor.display_name}}</span>
            </div>
        </div>
    </div>
    <div class="others-div">
        <span class="landing-headers">Ready to start?</span>
        <!-- <img class="stepper-image" src="@assets/stepper/landing-stepper-image.svg" alt="loading"/> -->
        <div class="stepper-image"/>
        <span class="landing-link">Photo consult ￡75</span>
    </div>
    <div class="page-checkup__continue">
        <span/>
        <div class="buttons-div">
            <button
                class="button--next"
                @click="proceed"
            >
                <span>Let’s Get Started</span>
            </button>
        </div>
    </div>
</div>
</template>
<script>
import { ACTIONS } from '@/store';
import { mapActions, mapState } from 'vuex';
import helperMethods from '@/helpers.js';

export default {
    name: 'uk-landing',
    metaInfo () {
        return {
            title: 'Home'
        };
    },
    data () {
        return {
            doctors: null
        };
    },
    computed: {
        ...mapState({
            region: state => state.region,
            goingBack: state => state.goingBack
        }),
        flow () {
            let flow = this.$route.query.flow;
            if (flow === 'chat') {
                this.setCheckFlow();
            } else if (flow === 'video') {
                this.setVideoFlow();
            }
            return flow;
        }
    },
    methods: {
        ...helperMethods,
        ...mapActions({
            setRedirectUrl: ACTIONS.SET_REDIRECT_URL,
            setCheckFlow: ACTIONS.SET_CHECK_FLOW,
            setLandingPage: ACTIONS.SET_LANDING_PAGE,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            checkExamination: ACTIONS.CHECK_EXAMINATION
        }),
        proceed () {
            let redirectUrl = this.getRedirectUrl(this.region);
            this.setRedirectUrl(redirectUrl);
            this.setLandingPage();
            this.$router.push('/check');
        }
    },
    async mounted () {
        await this.checkExamination();
        this.setLandingPage();
        this.showPageHeader(true);
    }
};
</script>
