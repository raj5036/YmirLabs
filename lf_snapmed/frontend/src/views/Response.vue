<template>
    <section class="page-response">
        <template v-if="loggedin">
            <div class="page-response__email-not-verified" v-if="!email_verified">
                <span class="page-response__text">{{ $t('page.response.email_not_verified') }}</span><span @click="resendEmail" class="page-response__resend-email">{{ $t('page.response.resend_email') }}</span>
            </div>
            <header class="page-response__header">
                <a
                    @click.prevent="logout"
                    href="#"
                    class="page-response__header-logout">
                    {{ $t('page.response.logout') }}
                </a>
                <a
                    @click.prevent="toggleStep('password')"
                    href="#"
                    class="page-response__header-logout">
                    {{ $t('page.response.password.anchor') }}
                </a>
            </header>
            <display-diagnoses v-if="step==='result'" />
            <change-password @displayResult="toggleStep('result')" v-if="step==='password'" />
        </template>
        <template v-else>
            <div class="page-response__login">
                <div class="page-response__login-half page-response__login-half-image">
                    <img class="page-response__login-illustration" src="@assets/illustrations/woman-with-computer.svg">
                </div>
                <div class="page-response__login-half">
                    <h1 class="page-response__title" v-t="'page.response.login.title'" :style="region === 'se' ? {'margin-bottom': '24px'} : null"></h1>
                    <div v-if="region === 'se'" class="page-response__microcopy">{{ $t('page.response.login.microcopy') }}</div>
                    <the-login />
                </div>
            </div>
        </template>
    </section>
</template>

<script>

import { mapGetters, mapActions, mapState } from 'vuex';
import { GETTERS, ACTIONS } from '@/store';
import TheLogin from '@common/TheLogin';
import DisplayDiagnoses from '@common/DisplayDiagnoses';
import ChangePassword from '@common/ChangePassword';

export default {
    name: 'response',
    metaInfo () {
        return {
            title: this.$t('title.response')
        };
    },
    data: function () {
        return {
            step: 'result',
            email_verified: true
        };
    },
    computed: {
        ...mapGetters({
            loggedin: GETTERS.LOGGEDIN
        }),
        ...mapState({
            region: state => state.region
        })
    },
    methods: {
        ...mapActions({
            logout: ACTIONS.LOGOUT,
            emailVerified: ACTIONS.USER_EMAIL_VERIFIED,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            resetStore: ACTIONS.RESET_STORE,
            restoreState: ACTIONS.RESTORE_STATE

        }),
        toggleStep (value) {
            this.step = value;
        },
        resendEmail () {
            this.$router.push('/resend-email');
        }
    },
    watch: {
        async loggedin () {
            if (this.loggedin) {
                await this.emailVerified().then(response => {
                    if (response.status) {
                        this.email_verified = response.is_email_verified;
                    }
                });
            }
        }
    },
    async created () {
        await this.restoreState();
        await this.showPageHeader(false);
        if (this.loggedin) {
            await this.emailVerified().then(response => {
                if (response.status) {
                    this.email_verified = response.is_email_verified;
                }
            });
        }
    },
    beforeDestroy: function () {
        this.logout();
    },
    components: {
        TheLogin,
        DisplayDiagnoses,
        ChangePassword
    }
};

</script>

<style lang="scss">
.page-response {
    &__email-not-verified{
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #5e4098;
        color: white;
        margin-bottom: spacing(3);
        padding: spacing(0.5);
    }
    &__resend-email{
        margin-left: spacing(1);
        cursor: pointer;
        text-decoration: underline;
        font-size: 16px;
    }
    &__header{
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding-left: spacing(1);
        padding-right: spacing(1);
        margin-bottom: spacing(3);
        &-logout{
            margin-left: auto;
        }
    }
    &__text{
        font-size: 16px;
    }
    &__microcopy{
        color: color(default-text);
        font-weight: 500;
        font-size: 16px;
        margin-bottom: 16px;
        @include breakpoint(small only) {
            text-align: center;
        }
    }
    $block: &;
    background: color(white);
    width: 100%;
    max-width: 960px;
    padding: 80px 0;
    @include breakpoint(medium down) {
        padding: 20px 0;
    }
    &__title {
        margin-bottom: spacing(4);
    }
    &__login {
        width: 100%;
        display: flex;
        &-half {
            width: 100%;
            display: flex;
            flex-direction: column;
            @include breakpoint(large) {
                text-align: left;
                $space: 80px;
                width: calc(50% - #{$space / 2});
                &:nth-of-type(odd) {
                    margin-right: $space;
                }
            }
            @include breakpoint(small only) {
                padding: 20px;
                align-items: center;
            }
        }
        &-half-image{
            @include breakpoint(small only) {
               display: none;
            }
        }
        &-illustration {
            width: 100%;
            @include breakpoint(medium down) {
                width: 70%;
            }
            @include breakpoint(small only) {
               display: none;
            }
        }
    }
    &__result {
        display: flex;
        flex-direction: column;
        width: 100%;
        #{$block}__title {
            text-align: center;
        }
    }
}
</style>
