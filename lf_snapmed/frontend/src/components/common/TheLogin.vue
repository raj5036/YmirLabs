<template>
    <div class="login-component">
        <form class="login" autocomplete="off" v-if="!loginWithBankId">
            <template v-if="step === 'finish'">
                loggedin
            </template>
            <template v-else-if="step === 'login'">
                <div class="login__input login__input--email">
                    <input
                        autocomplete="new-email"
                        placeholder="Din epost"
                        type="email"
                        v-model="email"
                    />
                </div>
                <div class="login__input login__input--phone">
                    <phone-number
                        :placeholder="$t('questions.login.placeholder.phone')"
                        ref="number"
                        v-model="number"
                    />
                </div>
                <password-input v-model="password" @keyup.enter="tryLogin" />
                <transition name="fade">
                    <span v-if="!loginSuccess" class="login__error">
                        {{ $t('questions.login.error') }}
                    </span>
                </transition>
                <transition name="fade">
                    <span v-if="keyError" class="login__error">
                        {{ $t('questions.key.' + responseCode) }}
                    </span>
                </transition>
                <span
                    v-if="number && password && !disablePassword"
                    class="login__action-button button button--light button--tight"
                    @click="tryLogin"
                >
                    {{ $t('btn.otp') }}
                </span>
            </template>
            <template v-else-if="step === 'key'">
                <div class="login__input login__input--key">
                    <input
                        autocomplete="new-code"
                        :placeholder="$t('questions.login.placeholder.otp')"
                        ref="key"
                        v-model="key"
                        @keydown.enter.stop.prevent="tryVerify"
                    />
                </div>
                <span
                    class="login__action-button button button--light button--tight"
                    @click="tryVerify"
                >
                    {{ $t('btn.check_response') }}
                </span>
            </template>
        </form>
        <div v-else class="bankid-login">
            <div class="bankid-login__error" v-if="bankIdLoginFailed">
                <p>
                    {{ $t('page.login.no_bankid_user_found') }} (<span
                        class="bankid-login__tryagain"
                        @click="resetBankIdFailedState"
                        >{{ $t('page.login.no_bankid_try_again') }}</span
                    >)
                </p>
                <p>
                    <span
                        class="bankid-login__switch"
                        @click="switchToPhoneLogin"
                        >{{ $t('page.login.login_with_phone_instead') }}</span
                    >
                </p>
            </div>
            <div v-else class="bankid-login__container">
                <div v-if="bankIdOperationId">
                    <iframe
                        :src="bankIdOperationUrl"
                        v-if="locale !== 'sv'"
                    ></iframe>
                    <transition name="fade" v-else-if="locale === 'sv'">
                        <div v-if="processing" class="result__processing">
                            <svg-loader />
                        </div>
                    </transition>
                </div>
                <div v-else class="bankid-login__button-container">
                    <button
                        :disabled="bankIdRequestInProgress"
                        @click="bankIdButtonClick('desktop')"
                        class="button button--dark button--large bankid-login__button"
                    >
                        {{ $t('page.login.login_bankid_btn') }}
                    </button>
                    <button
                        :disabled="bankIdRequestInProgress"
                        v-if="showMobileBankIDButton(locale)"
                        @click="bankIdButtonClick('mobile')"
                        class="button button--dark button--large bankid-login__button"
                    >
                        {{ $t('page.login.login_bankid_mobile_btn') }}
                    </button>
                    <span
                        v-if="locale == 'nb'"
                        class="bankid-login__switch"
                        @click="switchToPhoneLogin"
                        >{{ $t('page.login.login_with_phone_instead') }}</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

import { has, get, startsWith } from 'lodash';

import PhoneNumber from '@common/PhoneNumber';
import PasswordInput from '@common/PasswordInput';

import helperMethods from '@/helpers.js';

export default {
    name: 'TheLogin',
    props: {
        loginOrCreate: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            number: null,
            email: '',
            password: '',
            passwordReset: false,
            disablePassword: false,
            loginSuccess: true,
            loginWithBankId: false,
            bankIdRequestInProgress: false,
            bankIdLoginFailed: false,
            key: null,
            keyError: false,
            needKey: false,
            responseCode: '',
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
            bankIdOperationId: state => state.bankid.operationId
        }),
        step () {
            // Controll the view
            if (this.loggedin) {
                return 'finish';
            }
            if (this.needKey) {
                return 'key';
            }
            return 'login';
        }
    },
    mounted () {
        if (this.locale === 'nb' || this.locale === 'sv') {
            this.loginWithBankId = true; // for nb users we default to login with bankID
        }
        let $vm = this;

        window.addEventListener('message', function (e) {
            // this is called by /bankid-return when we return from bankID
            if (e.data.event === 'bankid-finished') {
                $vm.loginBankId()
                    .then($vm.loginBankIdThen)
                    .catch(reason => {
                        console.log(reason);
                        $vm.bankIdLoginFailed = true;
                    });
            }
        });
    },
    methods: {
        ...helperMethods,
        resetBankIdFailedState () {
            this.bankIdLoginFailed = false;
        },
        loginBankIdThen (response) {
            if (response.state === 'STATE_COMPLETED') {
                this.bankIdLoginFailed = false;
            } else if (response.state === 'STATE_INPROGRESS') {
                setTimeout(() => {
                    this.loginBankId()
                        .then(this.loginBankIdThen)
                        .catch(reason => {
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
            loginPhoneOrCreate: ACTIONS.PHONE_LOGIN_OR_CREATE
        }),
        switchToPhoneLogin () {
            this.loginWithBankId = false;
        },
        async bankIdButtonClick (device) {
            this.bankIdRequestInProgress = true;
            this.processing = true;
            let provider;
            if (this.locale === 'sv') {
                // open new blank tab, update url once async call is complete
                this.browser = window.open('', '_blank');
            }
            if (device === 'desktop') {
                provider = this.getProviderForBankId(this.locale);
            } else if (device === 'mobile') {
                provider = this.getProviderForMobileBankId(this.locale);
            }
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
            gtag('event', 'login', {
                event_category: 'engagement',
                event_label: 'login'
            });
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
                if (this.loginOrCreate) {
                    this.loginSuccess = await this.loginPhoneOrCreate({
                        phonenumber: this.number,
                        password: this.password,
                        email: this.email
                    });
                } else {
                    this.loginSuccess = await this.login({
                        phonenumber: this.number,
                        password: this.password,
                        email: this.email
                    });
                }
                if (this.loginSuccess) {
                    this.needKey = true;
                }
                this.disablePassword = false;
                console.log('Login result:', this.loginSuccess);
            }
        },
        async tryVerify () {
            gtag('event', 'login', {
                event_category: 'engagement',
                event_label: 'otp'
            });
            // Try to verify onetime key
            if (this.needKey && this.key) {
                const result = await this.onetimekey({
                    key: this.key,
                    passwordReset: this.passwordReset
                });
                if (result && result.error) {
                    if (result.reason.response.data.error === 'unauthorized') {
                        this.responseCode = 'otp_error';
                    } else {
                        this.responseCode = 'error';
                    }
                } else {
                    this.responseCode = result.status;
                }
                this.needKey = false;
                this.keyError = true;
                this.key = null;
                this.number = null;
                this.password = '';
                this.passwordReset = false;
                this.$emit('confirmVideo');
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
    },
    components: {
        PhoneNumber,
        PasswordInput
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
    div{
        display: flex;
        flex-direction: column;
        align-items: center;
        row-gap: 10px;
        }
    @include breakpoint(small only) {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    &__switch,
    &__tryagain {
        text-decoration: underline;
        cursor: pointer;
        @include breakpoint(small only) {
            text-align: center;
        }
    }
    &__container {
        @include breakpoint(small only) {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        button{
            @include dd-button;
        }
    }
    &__button-container {
        @include breakpoint(small only) {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    }
    &__button {
        @include breakpoint(small only) {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 0px !important;
        }
    }

    iframe {
        width: 100%;
        min-height: 300px;
        border: none;
    }

    &__error {
        @media #{$small-only} {
            text-align: center;
        }
    }
}
</style>
