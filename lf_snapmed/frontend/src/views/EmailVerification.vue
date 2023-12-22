<template>
    <div v-if="processing" class="processing"><svg-loader /></div>
    <div v-else class="verification">
        <svg-asset icon="email-verification" class="verification__icon"/>
        <div v-if="verified" class="verification__container">
            <div class="verification__text">{{$t('page.verification.verified')}}</div>
            <button @click="redirect" class="button button--large button--dark">{{$t('page.verification.login')}}</button>
        </div>
        <div v-else class="verification__container">
            <div class="verification__text">{{$t('page.verification.not_verified')}}</div>
            <button @click="redirect" class="button button--large button--dark">{{$t('page.verification.go_back')}}</button>
        </div>
    </div>
</template>

<script>
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'email-verification',
    data () {
        return {
            verified: false,
            processing: true,
            loginRedirectUrl: null
        };
    },
    methods: {
        ...mapActions({
            verifyEmail: ACTIONS.VERIFY_EMAIL
        }),
        redirect () {
            window.location.replace(process.env.VUE_APP_PATIENT_DASHBOARD_URL);
        }
    },
    async created () {
        await this.verifyEmail({'token': this.$route.params.token}).then(response => {
            if (response.status) {
                this.verified = true;
            }
        });
        this.processing = false;
    }
};
</script>

<style lang="scss">
.processing {
        background: rgba(color(white), 0.4);
        z-index: 1000;
        margin-top: 140px;
}
.verification {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    margin-top: 200px;
    &__container{
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
    &__icon {
        margin-bottom: 30px;
        .svg-asset__icon {
            width: 100px;
            height: 100px;
        }
    }
    &__text{
        font-size: 26px;
        margin-bottom: 30px;
    }
}
</style>
