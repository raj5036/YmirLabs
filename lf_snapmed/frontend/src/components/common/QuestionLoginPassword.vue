<template>
    <div :class="region === 'uk' ?  'question-login-password-uk' : 'question-login-password'">
        <div v-if="emptyError" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.empty_error')}}</span>
        </div>
        <div v-if="passwordNotSame" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.password_not_same')}}</span>
        </div>
        <div v-if="error" class="question-login-password__error-container">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.password_error')}}</span>
        </div>
        <input class="question-login-password__input" :placeholder="$t('questions.login.placeholder.password')" type="password" ref="password" v-on:keyup.enter="set" v-model="password">
        <div class="question-login-password__container">
            <span v-if="userPasswordAlredaySet" class="question-login-password__forgot-password"  @click="forgotPasswordClicked">{{$t('page.check.forgot_password')}}</span>
            <span class="question-login-password__button button button--light button--tight" @click="set" v-if="password">{{$t('btn.next')}}</span>
        </div>
        <span v-if="showMessage" class="question-login-password__message">An email has been sent to your register email address with link to reset password</span>
        <AutoFocus :disabled="(region !== 'uk')||(answers[data.id] !== null)"/>
    </div>
</template>

<script>
// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'QuestionLoginPassword',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    components: {
        AutoFocus
    },
    data: function () {
        return {
            password: null,
            showMessage: false,
            emptyError: false,
            passwordNotSame: false
        };
    },
    mounted: function () {
        this.password = this.answers[this.data.id];
    },
    computed: {
        ...mapGetters({
            error: GETTERS.PASSWORD_ERROR,
            answers: GETTERS.ANSWERS,
            newUser: GETTERS.NEW_USER,
            userPasswordAlredaySet: GETTERS.SET_PASSWORD
        }),
        ...mapState({
            region: state => state.region
        })
    },
    methods: {
        ...mapActions({
            setPassword: ACTIONS.UPDATE_PAYMENT,
            setAnswer: ACTIONS.UPDATE_ANSWER,
            resetPasswordError: ACTIONS.RESET_PASSWORD_ERROR,
            forgotpassword: ACTIONS.FORGOT_PASSWORD
        }),
        async set () {
            if (this.password) {
                if (this.data.id === 'confirmPassword') {
                    if (this.password === this.answers.password) {
                        this.setPassword({'input': this.password, 'id': this.data.id});
                    } else {
                        this.passwordNotSame = true;
                        this.setAnswer({ 'answer': null, 'id': this.data.id });
                    }
                } else {
                    if (this.region === 'uk') {
                        if (this.newUser) {
                            if (this.userPasswordAlredaySet) {
                                this.setPassword({'input': this.password, 'id': this.data.id});
                            } else {
                                this.setAnswer({ 'answer': this.password, 'id': this.data.id });
                            }
                        } else {
                            this.setPassword({'input': this.password, 'id': this.data.id});
                        }
                    } else {
                        await this.setPassword({'input': this.password, 'id': this.data.id});
                    }
                }
            }
        },
        forgotPasswordClicked () {
            this.showMessage = true;
            this.forgotpassword();
        }
    },
    watch: {
        password () {
            this.emptyError = false;
            this.passwordNotSame = false;
            if (this.password === '' || this.password === null) {
                this.emptyError = true;
                this.setPassword({'input': null, 'id': this.data.id});
            }
            this.resetPasswordError();
        }
    }

};
</script>
