<template>
    <div class="email-resend">
        <div class="email-resend__title"> {{$t('page.response.resend_email')}} </div>
        <div class="login__input email-resend__email">
            <input
                autofocus
                autocomplete="off"
                :placeholder="$t('questions.login.placeholder.email')"
                type="email"
                v-model="email">
        </div>
        <span
            v-if="email"
            class="button button--dark button--tight email-resend__button"
            @click="resendEmail">
            {{$t('page.response.resend_email')}}
        </span>
        <span class="email-resend__error" v-if="showInvalidError">
            {{$t('page.check.email_error')}}
        </span>
        <span class="email-error" v-if="showAlreadyExistsError">
            {{$t('page.check.email_already_exists')}}
        </span>
    </div>
</template>

<script>
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'resend-email',
    data () {
        return {
            email: null,
            authenticating: false,
            showInvalidError: false,
            showAlreadyExistsError: false
        };
    },
    methods: {
        ...mapActions({
            setEmail: ACTIONS.SET_EMAIL
        }),
        validateEmail (email) {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        },
        resendEmail () {
            this.showInvalidError = false;
            this.showAlreadyExistsError = false;
            // validate email
            if (!this.validateEmail(this.email)) {
                this.showInvalidError = true;
            } else {
                // set email
                if (this.email) {
                    this.setEmail(this.email).then(response => {
                        if (response === 'alreadyExists') {
                            this.showAlreadyExistsError = true;
                        } else {
                            window.location.replace(process.env.VUE_APP_PATIENT_DASHBOARD_URL);
                        }
                    });
                }
            }
        }
    }
};
</script>

<style lang="scss">
.email-resend{
    margin-top: 200px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: flex-start;
    max-width: 500px;
    &__title{
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 30px;
    }
    &__email{
        width: 400px;
        padding-left: 10px;
        margin-bottom: 30px;
        @media #{$small-only} {
            width: 300px;
        }
    }
    &__button{
        margin-bottom: 20px;
    }
    &__error{
        width: 400px
    }
}
</style>
