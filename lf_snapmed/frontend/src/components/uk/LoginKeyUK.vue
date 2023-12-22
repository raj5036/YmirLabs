<template>
    <div :class="region === 'uk'? 'question-login-password-uk' : 'question-login-password-no'">
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
        <div class="otp-array">
            <OtpInput
                ref="key"
                input-classes="otp-input"
                separator=""
                :num-inputs="4"
                :is-input-num="true"
                @on-complete="setKeyValue"
            />
        </div>
        <div class="timer-div">
            <div ref="timer_div" class="timer-text">
                <span>{{ $t('btn.resend_otp_in') }}</span
                ><span ref="timer_ref" />
            </div>
            <span class="link" @click="resendOtp" v-if="showResendOtp">
                {{ $t('btn.resend_otp') }}
            </span>
        </div>
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
        <AutoFocus :disabled="(answers[data.id] !== null || region !== 'uk')"/>
    </div>
</template>

<script>
// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import { ACTIONS as VIDEO_ACTIONS } from '@/store/video';
import moment from 'moment';
import OtpInput from '@bachdgvn/vue-otp-input';
import AutoFocus from '@common/AutoFocus';

const TIMER_TRESH_SEC = 30;

export default {
    name: 'LoginKeyUK',
    props: {
        data: {
            type: Object,
            required: true
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
        if (this.answers[this.data.id]) {
            this.key = this.answers[this.data.id];
            this.hideTimer();
        } else {
            this.startTimer();
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
            error: GETTERS.OTP_ERROR,
            flowType: GETTERS.GET_FLOW_TYPE
        })
    },
    components: {
        OtpInput,
        AutoFocus
    },
    methods: {
        ...mapActions({
            setNumber: ACTIONS.USER_LOGIN,
            setKey: ACTIONS.UPDATE_PAYMENT,
            updatePartner: ACTIONS.UPDATE_PARTNER,
            userSubmitCheck: ACTIONS.USER_SUBMIT_CHECK,
            resetKeyError: ACTIONS.RESET_KEY_ERROR,
            bookVideo: VIDEO_ACTIONS.PARTNER_BOOK_VIDEO,
            completeVideo: ACTIONS.SET_PARTNER_VIDEO_COMPLETE
        }),
        showTimer () {
            this.$refs.timer_div.style.display = 'flex';
        },
        hideTimer () {
            this.$refs.timer_div && (this.$refs.timer_div.style.display = 'none');
        },
        clearInput () {
            this.$refs.otpInput.clearInput();
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
        setKeyValue (value) {
            this.key = value;
        },
        set () {
            if (this.key && !this.isWaitingForAnswer) {
                this.isWaitingForAnswer = true;
                this.setKey({ input: this.key, id: this.data.id })
                    .then(() => {
                        this.stopTimer();
                        this.hideTimer();
                        this.isWaitingForAnswer = false;
                        if (
                            this.loggedin &&
                            !this.examination &&
                            this.flowType !== 'video'
                        ) {
                            // submit examination if we haven't already
                            this.userSubmitCheck();
                        } else if (this.loggedin && this.flowType === 'video' && this.isPartnerFlow) {
                            this.bookVideo(this.answers.description)
                                .then(() => {
                                    this.completeVideo();
                                });
                        }
                    })
                    .catch(reason => {
                        console.log('OTP key failed', reason);
                        this.isWaitingForAnswer = false;
                    });
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
        },
        error () {
            if (this.error) {
                this.$refs.key.clearInput();
                this.showResendOtp = true;
            }
        }
    }
};
</script>
