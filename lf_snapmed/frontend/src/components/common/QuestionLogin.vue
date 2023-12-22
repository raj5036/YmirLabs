<template>
    <div class="question-login">
        <template v-if="!waitingOnCode">
            <div class="question-login__number">
                <span class="question-login__current">
                    <img src="@assets/flags/NO.svg" class="question-login__current-flag">
                    <span class="question-login__current-code" v-html="activeCountry.dialCode"></span>
                    <select class="question-login__current-select" v-model="dropdown" @change="dropdownChange">
                        <option v-for="pb in allCountries" :key="pb['iso2']" :value="pb['iso2']" v-html="pb.name"></option>
                    </select>
                </span>
                <input :placeholder="$t('questions.login.placeholder.phone')" ref="phoneInput" v-model="phone" v-on:keyup.enter.prevent="next" class="question-login__number-input">
            </div>
            <div class="question-login__password" v-if="validNumber">
                <input :placeholder="$t('questions.login.placeholder.password')" type="password" ref="passwordInput" v-model="password" v-on:keyup.enter.prevent="trylogin" class="question-login__input">
            </div>
            <span v-if="state" @click="trylogin" class="question-login__next" :class="nextClasses">{{$t('questions.login.cta.otp')}}</span>
        </template>
        <template v-else>
            <div class="question-login__ontime">
                <input :placeholder="$t('questions.login.placeholder.otp')" type="" ref="onetimeInput" v-model="onetime" v-on:keyup.enter.prevent="checklogin" class="question-login__input">
            </div>
            <span v-if="onetime" @click="checklogin" class="question-login__next" :class="nextClasses">{{$t('questions.login.cta.login')}}</span>
        </template>
    </div>
</template>

<script>

import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import { has } from 'lodash';
import { format, AsYouType, isValidNumber } from 'libphonenumber-js';
import allCountries from '@/countries.js';

export default {
    name: 'QuestionPhone',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data: function () {
        return {
            phone: null,
            password: null,
            allCountries,
            activeCountry: allCountries[0],
            dropdown: null,
            onetime: null,
            waitingOnCode: false
        };
    },
    computed: {
        ...mapGetters({
            loggedin: GETTERS.LOGGEDIN
        }),
        nextClasses () {
            // Return selected class when is set
            return this.state ? 'question-login__next--selected' : '';
        },
        phoneNumber () {
            // Remove 0 prefix
            let nr = this.phone || null;
            return nr && nr[0] === '0' ? nr.slice(1) : nr;
        },
        formattedResult () {
            // Formatted number
            return this.phoneNumber && this.activeCountry ? format(this.phoneNumber, this.activeCountry && this.activeCountry.iso2, 'International') : '';
        },
        validNumber () {
            // Is valid number
            return isValidNumber(this.formattedResult);
        },
        validPassword () {
            // Is valid password
            return this.password !== null;
        },
        state () {
            // True false if valid
            return this.validNumber && this.validPassword;
        }
    },
    mounted: function () {
        // Set answer to input
        this.phone = this.data.answer;
        // Set language to no
        this.changeLanguage((process.env.VUE_APP_COUNTRY || 'US').toUpperCase());
        // this.focus();
    },
    methods: {
        ...mapActions({
            login: ACTIONS.LOGIN,
            onetimekey: ACTIONS.VERIFY
        }),
        trylogin () {
            gtag('event', 'sign_up', {'event_category': 'engagement', 'event_label': 'login'});
            // Set the answer
            if (this.state) {
                // Try login
                this.login({'phone': this.phone, 'password': this.password});
                this.waitingOnCode = true;
            }
        },
        checklogin () {
            gtag('event', 'sign_up', {'event_category': 'engagement', 'event_label': 'otp'});
            if (this.state && this.waitingOnCode) {
                this.onetimekey(this.onetime);
            }
        },
        dropdownChange () {
            // On change | select html element
            this.changeLanguage(this.dropdown);
        },
        changeLanguage (langCode) {
            // Set active country by lang code
            for (let l of this.allCountries) {
                if (l['iso2'] === langCode) {
                    this.activeCountry = l;
                    this.focus();
                }
            }
        },
        focus () {
            // Set foucs to first input
            if (has(this, '$refs.phoneInput')) {
                this.$refs['phoneInput'].focus();
            }
        }
    },
    watch: {
        validNumber (value) {
            // If valid - formatt the input field
            if (value) {
                this.phone = this.formattedResult;
                window.setTimeout(() => {
                    if (has(this, '$refs.passwordInput')) {
                        this.$refs.passwordInput.focus();
                    }
                }, 100);
            }
        },
        phoneNumber () {
            // Try to format
            const asYouType = new AsYouType();
            asYouType.input(this.phoneNumber);
            if (asYouType.country !== undefined) {
                this.changeLanguage(asYouType.country);
            }
        },
        waitingOnCode (value) {
            if (value) {
                window.setTimeout(() => {
                    if (has(this, '$refs.onetimeInput')) {
                        this.$refs.onetimeInput.focus();
                    }
                }, 100);
            }
        }
    }
};
</script>

<style lang="scss">
.question-login {
    display: flex;
    flex-direction: column;
    &__next {
        // Next btn
        @include fadeInOut(true);
        @include chat-btn;
        align-self: flex-end;
        margin-top: spacing();
    }
    &__number {
        // Number row
        display: flex;
        flex-direction: row;
        flex-flow: nowrap;
        width: 300px;
        padding: 11px 30px;
        border-radius: 31px;
        background: color(very-light-pink);
        position: relative;
        &-input {
            // Input field
            display: flex;
            outline: none;
            border: none;
            font-size: $font-medium;
            color: color(blueberry);
            background: transparent;
            line-height: 1.38;
            flex-shrink: 1;
            width: calc(100% - 65px);
        }
    }
    &__input {
        display: flex;
        flex-direction: row;
        flex-flow: nowrap;
        padding: 11px 30px;
        outline: none;
        border: none;
        margin-left: auto;
        border-radius: 31px;
        line-height: 26px;
        margin-top: 7.5px;
        background: color(very-light-pink);
        position: relative;
        @include breakpoint(small only) {
            padding: 5px 16px;
        }
    }
    &__current {
        // Left aligned current language
        display: flex;
        flex-direction: row;
        align-items: center;
        width: 65px;
        cursor: pointer;
        position: relative;
        &:hover &-code {
            color: color(blueberry);
        }
        &-flag {
            // Country flag
            width: 20px;
            display: flex;
            margin-right: 4px;
        }
        &-code {
            // The prefix code
            display: flex;
            flex-flow: row nowrap;
            width: 50px;
            font-size: $font-small-medium;
            color: color(dark-grey);
            &:before {
                content: '+';
            }
        }
        &-select {
            // Select list with countries
            opacity: 0;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            cursor: pointer;
        }
    }
}
</style>
