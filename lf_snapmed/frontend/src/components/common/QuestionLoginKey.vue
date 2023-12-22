<template>
    <div class="question-login-password">
        <div class="timer-div">
            <div ref="timer_div" class="timer-text">
                <span>{{ $t('btn.resend_otp_in') }}</span
                ><span ref="timer_ref" />
            </div>
            <span class="link" @click="resendOtp" v-if="showResendOtp">
                {{ $t('btn.resend_otp') }}
            </span>
        </div>
        <div
            v-if="emptyError"
            class="question-login-password__error-container question-large-text__error"
        >
            <img
                src="@assets/icons/error.svg"
                title="Snapmed"
                alt="Snapmed"
                class="question-login-password__icon"
            />
            <span class="question-login-password__error">{{
                $t('page.check.empty_error')
            }}</span>
        </div>
        <div v-if="error" class="question-login-password__error-container">
            <img
                src="@assets/icons/error.svg"
                title="Snapmed"
                alt="Snapmed"
                class="question-login-password__icon"
            />
            <span class="question-login-password__error">{{
                $t('page.check.otp_error')
            }}</span>
        </div>
        <input
            class="question-login-password__input"
            :placeholder="$t('questions.login.placeholder.otp')"
            ref="key"
            v-model="key"
            v-on:keyup.enter="set"
        />
        <button
            class="question-login-password__button button button--light button--tight"
            @click="set"
            :disabled="isWaitingForAnswer"
            v-if="key"
        >
            <span class="loader-image" v-if="isWaitingForAnswer"
                ><svg-loader
            /></span>
            <span v-else>{{ $t('btn.next') }}</span>
        </button>
    </div>
</template>

<script>
// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import moment from 'moment';

const TIMER_TRESH_SEC = 30;

export default {
    name: 'QuestionLoginKey',
    props: {
        data: {
            type: Object,
            required: true
        },
        flow: {
            type: String,
            default: ''
        }
    },
    data: function () {
        return {
            key: null,
            isWaitingForAnswer: false,
            emptyError: false,
            timerStopped: false,
            showResendOtp: false
        };
    },
    mounted: function () {
        this.startTimer();
        if (this.answers[this.data.id]) {
            this.key = this.answers[this.data.id];
        }
    },
    destroyed: function () {
        this.stopTimer();
    },
    computed: {
        ...mapState(['loggedin', 'examination', 'region']),
        ...mapGetters({
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE,
            isPartnerCovered: GETTERS.IS_PARTNER_COVERED,
            answers: GETTERS.ANSWERS,
            error: GETTERS.OTP_ERROR
        })
    },
    methods: {
        ...mapActions({
            setNumber: ACTIONS.SET_PHONE_NUMBER,
            setKey: ACTIONS.UPDATE_PAYMENT,
            updatePartner: ACTIONS.UPDATE_PARTNER,
            userSubmitCheck: ACTIONS.USER_SUBMIT_CHECK,
            resetKeyError: ACTIONS.RESET_KEY_ERROR
        }),
        showTimer () {
            this.$refs.timer_div.style.display = 'flex';
        },
        hideTimer () {
            this.$refs.timer_div.style.display = 'none';
        },
        async startTimer (
            startEvt = () => {
                this.showResendOtp = false;
            },
            stopEvt = () => {
                this.showResendOtp = true;
            }
        ) {
            this.$refs.timer_ref && this.showTimer();
            startEvt && startEvt();
            this.timerStopped = false;
            let i = TIMER_TRESH_SEC;
            while (!this.timerStopped) {
                i = i - 1;
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.$refs.timer_ref &&
                    (this.$refs.timer_ref.innerHTML = `${moment({
                        hour: 0,
                        minute: 0,
                        seconds: i
                    }).format('mm:ss')}`);
                if (i < 1) {
                    this.timerStopped = true;
                    this.$refs.timer_ref && this.hideTimer();
                    stopEvt && stopEvt();
                }
            }
        },
        stopTimer () {
            this.timerStopped = true;
        },
        resendOtp () {
            this.startTimer();
            this.setNumber(this.answers.phone);
        },
        set () {
            if (this.key && !this.isWaitingForAnswer) {
                this.isWaitingForAnswer = true;
                if (this.isPartnerFlow) {
                    this.updatePartner({ answer: this.key, id: this.data.id })
                        .then(routeName => {
                            this.isWaitingForAnswer = false;
                            // only submit in /check mode, not /video mode
                            if (this.loggedin && this.flow !== 'video') {
                                this.userSubmitCheck().then(() => {
                                    if (routeName && this.isPartnerCovered) {
                                        // only redirect if we get a routeName and this is a covered user (ie. no payment)
                                        this.$router.replace({
                                            name: routeName
                                        });
                                    }
                                });
                            } else if (
                                this.flow === 'video' &&
                                !this.isPartnerCovered
                            ) {
                                this.$router.push('/video/confirm'); // if this partner is not covered, redirectly to video confirm here
                            }
                        })
                        .catch(reason => {
                            console.log('OTP key failed (partner mode)');
                            this.isWaitingForAnswer = false;
                        });
                } else {
                    this.setKey({ input: this.key, id: this.data.id })
                        .then(() => {
                            this.stopTimer();
                            this.hideTimer();
                            this.isWaitingForAnswer = false;
                            if (this.loggedin && this.flow === 'video') {
                                this.$router.push('/video/confirm');
                            } else if (
                                this.loggedin &&
                                !this.examination &&
                                this.region !== 'uk'
                            ) {
                                // submit examination if we haven't already
                                this.userSubmitCheck();
                            }
                        })
                        .catch(reason => {
                            console.log('OTP key failed');
                            this.isWaitingForAnswer = false;
                        });
                }
            }
        }
    },
    watch: {
        key () {
            this.emptyError = false;
            if (this.key === '' || this.key === null) {
                this.emptyError = true;
                this.setKey({ input: null, id: this.data.id });
            }
            this.resetKeyError();
        }
    }
};
</script>
