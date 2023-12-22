<template>
    <div style="width:100%">
    <div class="flow-header">
        <div @click="previous" :class="questions && questions.length > 0 && question.id !== questions[0].id ? 'page-checkup__button-text button-back' : 'page-checkup__hide-button button-back'">
            <span>{{ $t('btn.go_back') }}</span>
        </div>
        <progress-bar
            :options="options"
            :value="getStepCount"
        />
        <div class="page-checkup__button-text">
            <span> <close-modal/> </span>
        </div>
    </div>
    <section class='page-checkup' v-if="questions && questions.length > 0">
        <div
            v-if="question && question.question"
            :key="question.id"
        >
            <div class="page-checkup__heading">
                <div v-if="flowType === 'video' && questions && questions.length > 0 && question.id !== questions[0].id">
                    <show-date/>
                </div>
               <span>{{ $t(question.title) }}</span>
            </div>
            <ask-question
                class='chat-question'
                :who="who"
                :question="question"
                :age="age"
                :phoneNumber="partnerPhone"
            />
            <div
                class='chat-question chat-question__answer-container'>
                <component
                    v-bind:is="question.type"
                    :data="question"
                    :age="age"
                    v-on:addSelectedDuration="addSelectedDuration"
                />
            </div>
            <transition name="fade">
                <span
                    class="phonenumber-error"
                    v-if="showPhoneErrorFor(question)"
                >
                    {{ $t('page.check.phone_error') }}
                </span>
            </transition>
            <transition name="fade">
                <span class="otp-error" v-if="showVerifyErrorFor(question)">
                    {{ $t('page.check.otp_error') }}<br />{{
                        $t('page.check.otp_retry')
                    }}
                </span>
            </transition>
        </div>
        <transition name="fade">
            <div v-if="processing" class="page-checkup__processing">
                <svg-loader />
            </div>
        </transition>
    </section>
    <section v-else class="page-checkup--finished" ref="complete">
        <div>
            {{ this.$router.push('/thank-you?flow=picture') }}
        </div>
    </section>
    </div>
</template>

<script>
// Vuex and store
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

// Assets
import { has } from 'lodash';

// Ask
import AskQuestion from '@common/AskQuestion';

import Stepper from '@/components/uk/Stepper';
import ProgressBar from 'vuejs-progress-bar';

// Questions
import QuestionOptions from '@common/QuestionOptions';
import QuestionImage from '@common/QuestionImage';
import QuestionImageUK from '@/components/uk/QuestionImageUK';
import QuestionText from '@common/QuestionText';
import QuestionShortText from '@common/QuestionShortText';
import QuestionLargeText from '@common/QuestionLargeText';
import QuestionDropdown from '@common/QuestionDropdown';
import QuestionCalendar from '@common/QuestionCalendar';
import QuestionLogin from '@common/QuestionLogin';
import QuestionLoginPhone from '@common/QuestionLoginPhone';
import QuestionLoginPassword from '@common/QuestionLoginPassword';
import QuestionLoginKey from '@common/QuestionLoginKey';
import LoginKeyUK from '@/components/uk/LoginKeyUK';
import DateInput from '@/components/uk/DateInput';
import QuestionLoginEmail from '@common/QuestionLoginEmail';
import QuestionAge from '@common/QuestionAge';
import QuestionPayment from '@common/QuestionPayment';
import QuestionPaymentUK from '@/components/uk/QuestionPaymentUK';
import QuestionPaymentNew from '@/components/uk/QuestionPaymentNew';
import PersonalizedText from '@/components/uk/PersonalizedText';
import MultiSelect from '@/components/uk/MultiSelect';
import PersonalDetailsEditor from '@/components/uk/PersonalDetailsEditor';
import MedicalDetailsEditor from '@/components/uk/MedicalDetailsEditor';
import SelectSkinColour from '@/components/uk/SelectSkinColour';
import SingleSelect from '@/components/uk/SingleSelect';
import Search from '@/components/uk/Search';
import QuestionPrice from '@common/QuestionPrice';
import QuestionBankID from '@common/QuestionBankID';
import QuestionPartnerPhoneVerify from '@common/QuestionPartnerPhoneVerify';
import QuestionChildSsn from '@common/QuestionChildSsn';
import AutoFocus from '@common/AutoFocus';
import moment from 'moment';
import CloseModal from '@/components/common/CloseModal.vue';
import VideoBook from '@/components/common/VideoBook';
import ShowDate from '../components/common/ShowDate.vue';

// Check
export default {
    name: 'flow',
    data: function () {
        return {
            question: {},
            questionsElements: [],
            processing: false,
            selectedDuration: '',
            stepCount: 0,
            options: {
                text: {
                    hideText: true
                },
                progress: {
                    color: '#AC94CE',
                    backgroundColor: 'rgba(0, 0, 0, 0.32)',
                    inverted: false,
                    width: 0
                },
                layout: {
                    height: 8,
                    width: 400,
                    zeroOffset: 0,
                    progressPadding: 0,
                    type: 'line'
                }
            }
        };
    },
    metaInfo () {
        return {
            title: this.$t('title.check')
        };
    },
    computed: {
        ...mapGetters({
            loggedIn: GETTERS.LOGGEDIN,
            questions: GETTERS.QUESTIONS,
            answers: GETTERS.ANSWERS,
            paymentError: GETTERS.PAYMENT_ERROR,
            isQuestionsComplete: GETTERS.CHECK_REQUIRED_COMPLETE,
            needEmail: GETTERS.NEED_EMAIL,
            finish: GETTERS.CHECK_FINISH,
            partnerPhone: GETTERS.PARTNER_PHONE_NUMBER,
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE,
            partnerError: GETTERS.PARTNER_ERROR,
            isPartnerOtpFlow: GETTERS.IS_PARTNER_OTP_FLOW_STATE,
            isOsloFlow: GETTERS.IS_OSLO_FLOW,
            userUuid: GETTERS.USER_UUID,
            newUser: GETTERS.NEW_USER,
            flowType: GETTERS.GET_FLOW_TYPE
        }),
        ...mapState({
            locale: state => state.locale,
            region: state => state.region
        }),
        who () {
            return this.answers.who;
        },
        age () {
            if (this.answers.date_of_birth) {
                const dob = moment(this.answers.date_of_birth, 'YYYY-MM-DD');
                let age = moment().diff(dob, 'years', false);
                return age && age >= 18
                    ? 'adult'
                    : 'minor';
            } else {
                return 'adult';
            }
        },
        getStepCount () {
            return this.stepCount;
        }
    },
    methods: {
        ...mapActions({
            submitCheck: ACTIONS.SUBMIT_CHECK,
            continueToNextStep: ACTIONS.CONTINUE_TO_NEXT_STEP,
            partnerContinue: ACTIONS.PARTNER_CONTINUE,
            partnerOsloSubmit: ACTIONS.PARTNER_OSLO_CHECK,
            reset: ACTIONS.RESET,
            restoreState: ACTIONS.RESTORE_STATE,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            showStepper: ACTIONS.SHOW_STEPPER,
            removeLandingPage: ACTIONS.REMOVE_LANDING_PAGE,
            setFlow: ACTIONS.SET_FLOW
        }),
        showVerifyErrorFor (question) {
            return (
                this.currentError() === 'VERIFY' &&
                (question.id === 'password' ||
                    (this.isPartnerFlow && question.id === 'partnerphone'))
            );
        },
        showPhoneErrorFor (question) {
            return (
                this.currentError() === 'INVALID_PHONE' &&
                question.id === 'phone'
            );
        },
        showMinorErrorFor (question) {
            return question.id === 'who' && this.who === 'other';
        },
        currentError () {
            if (this.isPartnerFlow) {
                return this.partnerError;
            } else {
                return this.paymentError;
            }
        },
        adjustQuestionElement () {
            if (has(this, '$refs.questions') && this.$refs['questions']) {
                this.questionsElements = this.$refs['questions'].children;
            }
        },
        async imageCaseSubmit () {
            this.processing = true;
            const success = await this.partnerOsloSubmit();
            this.processing = false;
            if (success) {
                this.$router.push({ name: 'partner-oslo-thanks' });
            }
        },
        addSelectedDuration (value) {
            this.selectedDuration = value;
        },
        async previous () {
            // this case arises when user re-authenticates themselve, question sets undefined
            if (!this.question) {
                this.question = await this.questions[this.questions.length - 1];
            }
            this.questions.forEach((ques, id) => {
                if (ques.id === this.question.id) {
                    this.question = this.questions[id - 1];
                }
            });
            this.stepCount -= 5;
        },
        getFlow () {
            let flow = this.$route.query.flow;
            this.setFlow(flow);
            this.flow = flow;
        }
    },
    async mounted () {
        await this.getFlow();
        if (document.location.href === localStorage.getItem('website_url')) {
            this.restoreState();
        }
        this.showPageHeader(true);
        this.showStepper();
        this.removeLandingPage();
        localStorage.setItem('website_url', document.location.href);
        this.question = this.questions[this.questions.length - 1];
        this.stepCount = (this.questions.length * 100) / 20;
    },
    watch: {
        questions () {
            var currentId = null;
            this.questions.find((ques, id) => {
                if (ques.id === this.question.id && this.question.id !== 'phone' && this.question.id !== 'key') {
                    currentId = id + 1;
                    return true;
                }
                currentId = id;
                return false;
            });
            this.question = this.questions[currentId];
            this.stepCount += 5;
        }
    },
    components: {
        AskQuestion,
        QuestionChildSsn,
        QuestionOptions,
        QuestionImage,
        QuestionText,
        QuestionShortText,
        QuestionLargeText,
        QuestionDropdown,
        QuestionCalendar,
        QuestionLogin,
        QuestionLoginPhone,
        QuestionLoginPassword,
        QuestionLoginKey,
        QuestionLoginEmail,
        QuestionAge,
        QuestionPayment,
        QuestionPaymentUK,
        QuestionPrice,
        QuestionBankID,
        QuestionPartnerPhoneVerify,
        Stepper,
        MultiSelect,
        SelectSkinColour,
        SingleSelect,
        LoginKeyUK,
        DateInput,
        QuestionImageUK,
        Search,
        PersonalDetailsEditor,
        MedicalDetailsEditor,
        AutoFocus,
        PersonalizedText,
        QuestionPaymentNew,
        ProgressBar,
        CloseModal,
        VideoBook,
        ShowDate
    }
};
</script>
