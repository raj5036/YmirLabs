<template>
    <div class="bankid-login" v-if="!bankIdSuccess">
        <div class="bankid-login__error" v-if="bankIdLoginFailed">
            <p>{{$t('page.login.no_bankid_user_found')}} (<span class="bankid-login__tryagain" @click="resetBankIdFailedState">{{$t('page.login.no_bankid_try_again')}}</span>)</p>
        </div>
        <div v-else>
            <div v-if="bankIdOperationId">
                <iframe :src="bankIdOperationUrl" v-if="locale !== 'sv'"></iframe>
                <transition name="fade" v-else-if="locale === 'sv'">
                    <div v-if="processing" class="result__processing"><svg-loader /></div>
                </transition>
            </div>
            <div v-else class="options-list">
                <button style="--order:0;" :disabled="bankIdRequestInProgress"
                    @click="bankIdButtonClick()"
                    class="button button--dark button--large">
                    {{$t('page.login.login_bankid_btn')}}
                </button>
            </div>
        </div>
    </div>
</template>

<script>

import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

import { has, get, startsWith } from 'lodash';

import helperMethods from '@/helpers.js';

export default {
    name: 'TheLogin',
    data () {
        return {
            loginSuccess: true,
            loginWithBankId: false,
            bankIdRequestInProgress: false,
            bankIdSuccess: false,
            bankIdLoginFailed: false,
            processing: false
        };
    },
    computed: {
        ...mapGetters({
            loggedin: GETTERS.LOGGEDIN
        }),
        ...mapState({
            locale: state => state.locale,
            bankIdOperationUrl: state => state.bankid.operationUrl,
            bankIdOperationId: state => state.bankid.operationId,
            user_uuid: state => state.user.uuid
        })
    },
    beforeDestroy () {
        window.removeEventListener('message', this.attachMessageEventListener);
    },
    mounted () {
        window.addEventListener('message', this.attachMessageEventListener);
    },
    methods: {
        ...helperMethods,
        attachMessageEventListener (e) {
            let $vm = this;
            // this is called by /bankid-return when we return from bankID
            if (e.data.event === 'bankid-finished') {
                $vm.loginBankId(true).then(() => {
                    $vm.loginBankIdThen();
                    window.removeEventListener('message', this.attachMessageEventListener);
                }).catch(reason => {
                    console.log(reason);
                    $vm.bankIdLoginFailed = true;
                });
            }
        },
        async resetBankIdFailedState () {
            await this.resetBankId();
            this.bankIdLoginFailed = false;
        },
        loginBankIdThen (response) {
            if (response.state === 'STATE_COMPLETED') {
                this.bankIdLoginFailed = false;
                this.setBankId({ id: 'bankid', input: true });
                this.bankIdSuccess = true;
            } else if (response.state === 'STATE_INPROGRESS') {
                setTimeout(() => {
                    this.loginBankId(true).then(this.loginBankIdThen).catch(reason => {
                        this.bankIdLoginFailed = true;
                    });
                }, 1000); // try again in 1 sec
            } else {
                // Failure.
                this.bankIdLoginFailed = true;
                this.logout(); // perform logout which will clear bankID request details
            }
        },
        ...mapActions({
            login: ACTIONS.LOGIN,
            logout: ACTIONS.LOGOUT,
            onetimekey: ACTIONS.LOGIN_VERIFY,
            initBankIdLogin: ACTIONS.INIT_BANKID_LOGIN,
            loginBankId: ACTIONS.LOGIN_BANKID,
            setBankId: ACTIONS.UPDATE_PAYMENT,
            resetBankId: ACTIONS.RESET_BANKID_LOGIN
        }),
        switchToPhoneLogin () {
            this.loginWithBankId = false;
        },
        async bankIdButtonClick () {
            this.bankIdRequestInProgress = true;
            this.processing = true;
            let provider;
            if (this.locale === 'sv') { // open new blank tab, update url once async call is complete
                this.browser = window.open('', '_blank');
            }
            provider = this.getProviderForBankId(this.locale);
            try {
                const response = await this.initBankIdLogin(provider);
                if (response) {
                    this.bankIdRequestInProgress = false;
                    if (this.locale === 'sv') {
                        this.openNewTab();
                    }
                }
            } catch (error) {
                this.bankIdRequestInProgress = false;
                console.log(error);
            }
        },
        async openNewTab () {
            this.browser.location = this.bankIdOperationUrl;
            this.browser.focus();
            var checkWindowClose = setInterval(() => {
                if (this.browser.closed) {
                    this.processing = false;
                    this.$store.commit('SET_BANKID_DETAILS', {
                        operationUrl: null,
                        operationId: null
                    });
                    clearInterval(checkWindowClose);
                }
            }, 1000);
        },
        async tryLogin () {
            gtag('event', 'login', {'event_category': 'engagement', 'event_label': 'login'});
            // Try to login
            if (!this.needKey && this.number && this.password) {
                this.disablePassword = true;
                // Is the user trying to reset his password?
                if (startsWith(this.password, 'tok_')) {
                    // Yes, then set passwordReset to true
                    this.passwordReset = true;
                } else {
                    this.passwordReset = false;
                }
                // Email field is not populated (only used to stop login attempts)
                this.loginSuccess = await this.login({ 'phonenumber': this.number, 'password': this.password, 'email': this.email });
                if (this.loginSuccess) {
                    this.needKey = true;
                }
                this.disablePassword = false;
                console.log('Login result:', this.loginSuccess);
            }
        },
        async tryVerify () {
            gtag('event', 'login', {'event_category': 'engagement', 'event_label': 'otp'});
            // Try to verify onetime key
            if (this.needKey && this.key) {
                const result = await this.onetimekey({key: this.key, newPassword: this.newPassword});
                if (result && result.error) {
                    this.needKey = false;
                    this.keyError = true;
                    this.key = null;
                    this.newPassword = '';
                }
            }
        },
        setFocus (ref) {
            // Set focus to input field
            if (has(this, '$refs.' + ref)) {
                get(this, '$refs.' + ref).focus();
            }
        }
    },
    watch: {
        number (value) {
            // When number is added - set focus to password
            if (value) {
                this.setFocus('password');
            }
        },
        needKey (value) {
            // When waiting on key - set focus to key field
            if (value) {
                setTimeout(() => {
                    this.setFocus('key');
                }, 100);
            }
        }
    }
};
</script>

<style lang="scss">
.login-component {
    width: 100%;
}
.login {
    &__input {
        // Inputs
        display: flex;
        align-items: center;
        height: 40px;
        width: 100%;
        border: 1px solid color(blueberry);
        border-radius: 5px;
        padding-left: 50px;
        position: relative;
        color: color(blueberry);
        // Icons
        &:before {
            content: '';
            height: 20px;
            width: 20px;
            position: absolute;
            left: 15px;
            top: 10px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        &--phone:before {
            background-image: url('~@assets/login/phone.svg');
        }
        &--new-password:before {
            background-image: url('~@assets/login/eye.svg');
        }
        &--key:before {
            background-image: url('~@assets/login/key.svg');
        }
        + #{&} {
            margin-top: 15px;
        }
        // Input and placeholder
        ::placeholder {
            color: color(blueberry);
            opacity: 0.5;
        }
        input {
            outline: none;
            border: none;
            width: 100%;
            color: color(blueberry);
        }
        &--email {
            height: 0;
            width: 0;
            overflow: hidden;
            z-index: -3212;
            margin-left: -9439px;
            margin-top: -15px;
        }
    }
    &__error {
        padding: 10px 0;
    }
    &__action-button {
        margin-top: spacing(2);
        align-self: center;
        cursor: pointer;
    }
}

.bankid-login {
    width: 100%;
    &__switch, &__tryagain {
        text-decoration: underline;
        cursor: pointer;
    }

    iframe {
        width: 100%;
        min-height: 300px;
        border: none;
    }

    &__error {
        margin-top: 64px;
        text-align:center;
        font-size: 1.2rem;
    }
    .options-list{
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    &__return{
        color: #fff;
    }
}
</style>
