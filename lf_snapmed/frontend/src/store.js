import Vue from 'vue';
import Vuex from 'vuex';
import {
    has,
    forIn,
    merge,
    find,
    isUndefined,
    isNull,
    mapValues
} from 'lodash';

import {
    questions,
    questionFlow,
    videoQuestionFlow,
    auroraVideoFlow
} from '@/store/questions';
import video from '@/store/video';
import moment from 'moment';
import createPersistedState from 'vuex-persistedstate';
import SecureLS from 'secure-ls';
var ls = new SecureLS({ isCompression: false });

Vue.use(Vuex);

let currentLocale = process.env.VUE_APP_I18N_LOCALE || 'nb';

const answersTpl = {
    name: null,
    who: null,
    child_ssn: null,
    address: null,
    age: null,
    ethnicity: null,
    date_of_birth: null,
    gender: null,
    pregnant: null,
    breastfeeding: null,
    description: null,
    body_part: null,
    duration: null,
    medication: null,
    medication_description: null,
    allergy: null,
    allergy_description: null,
    treatment: null,
    treatment_description: null,
    family_history: null,
    family_history_description: null,
    closeup: null,
    overview: null,
    bankid: null,
    phone: null,
    key: null,
    email: null,
    deadline: null,
    token: null,
    password: null,
    generalPractitioner: null,
    durationDesc: null,
    confirmPassword: null,
    idProof: null,
    mde: 1,
    pde: 1,
    headerText: 1,
    userPasswordAlreadySet: false,
    videoSlot: null
};

const initialState = {
    stickyHeader: false,
    check: {
        finish: {
            state: false,
            time: null
        },
        uuid: false,
        answers: merge({}, answersTpl)
    },
    payment: {
        error: false,
        response: false,
        state: {
            submit: false,
            verify: false,
            charged: false,
            confirm: false // ready to confirm payment
        }
    },
    partner: {
        error: null,
        phonenumber: false, // unverified phonenumber from partner
        uuid: null, // uuid of currently logged in partner
        name: '', // name of the currently logged in partner
        policynumber: '', // stb users policynumber, can be empty if the user doesn't have an insurance policy (if so they should pay)
        claim_uuid: '', // stb users claim uuid, can be empty if the user doesn't have an active insurance policy (if so they should pay)
        active: false, // true if the current user came via a partner
        isCovered: false, // true if the partner is covered by an insurance or similar, false if not covered (false = needs to pay)
        otpFlow: false, // user currently in otp verification flow?
        otpVerified: false, // true once phone number has been verified
        examinationSubmitConfirmed: false, // final confirmation of examination submit = ok?
        intro: false, // make this true if the intro page is fired for partner
        brand: '' // dkv, spp, stb
    },
    bankid: {
        operationUrl: null,
        operationId: null
    },
    user: {
        name: null,
        uuid: null,
        email: null,
        email_verified: false
    },
    showPageHeader: false,
    redirectUrl: '', // redirects to snapmed website on click of logo
    showHeader: false, // we want to display a header when coming via a partner
    showHeaderBackButton: false, // display the back button in the header
    currency: process.env.VUE_APP_CURRENCY,
    locale: currentLocale,
    region: process.env.VUE_APP_SITE,
    totalAmount: 0.0,
    promoCode: '',
    needemail: false,
    token: null,
    loggedin: !!sessionStorage.getItem('token'),
    examination: null,
    diagnoses: null,
    isOsloFlow: false,
    caseCode: null,
    isAuroraFlow: false,
    flowType: null,
    passwordError: false,
    otpError: false,
    justAnswered: true,
    newUser: true,
    landingPage: false,
    thankYouPage: false,
    subscriptionSuccessful: false,
    goingBack: false,
    showStepper: false,
    osiAmount: '',
    tagrid: null
};

export const MUTATIONS = {
    PARTNER_CHECK_COMPLETE: 'PARTNER_CHECK_COMPLETE',
    PARTNER_VIDEO_COMPLETE: 'PARTNER_VIDEO_COMPLETE',
    UPDATE_PAYMENT_RESPONSE: 'UPDATE_PAYMENT_RESPONSE',
    UPDATE_ANSWER: 'UPDATE_ANSWER',
    UPDATE_PAYMENT: 'UPDATE_PAYMENT',
    UPDATE_PARTNER: 'UPDATE_PARTNER',
    TOTAL_AMOUNT_POST_PROMO: 'TOTAL_AMOUNT_POST_PROMO',
    UPDATE_LOGIN: 'UPDATE_LOGIN',
    UPDATE_IMAGE: 'UPDATE_IMAGE',
    FINISH_CHECK: 'FINISH_CHECK',
    RESET: 'RESET',
    ERROR: 'ERROR',
    SUCCESS: 'SUCCESS',
    LOGOUT: 'LOGOUT',
    SHOW_HEADER: 'SHOW_HEADER',
    HIDE_HEADER: 'HIDE_HEADER',
    SET_REDIRECT_URL: 'SET_REDIRECT_URL',
    SET_PHONE_NUMBER: 'SET_PHONE_NUMBER',
    SET_EXAMINATION: 'SET_EXAMINATION',
    SET_SHOW_HEADER_BACK_BUTTON: 'SET_SHOW_HEADER_BACK_BUTTON',
    SET_PARTNER_FLOW_STATE: 'SET_PARTNER_FLOW_STATE',
    PARTNER_ERROR: 'PARTNER_ERROR',
    PARTNER_SUCCESS: 'PARTNER_SUCCESS',
    SET_PARTNER_OTP_FLOW_STATE: 'SET_PARTNER_OTP_FLOW_STATE',
    SET_BANKID_DETAILS: 'SET_BANKID_DETAILS',
    RESET_BANKID_LOGIN: 'RESET_BANKID_LOGIN',
    SET_STICKY_HEADER: 'SET_STICKY_HEADER',
    CONFIRM_PARTNER_EXAMINATION: 'CONFIRM_PARTNER_EXAMINATION',
    SET_OSLO_PARTNER: 'SET_OSLO_PARTNER',
    SET_CASE_CODE: 'SET_CASE_CODE',
    SET_AURORA_PARTNER: 'SET_AURORA_PARTNER',
    SET_UUID: 'SET_UUID',
    SET_PHONE: 'SET_PHONE',
    SET_INTRO_PAGE: 'SET_INTRO_PAGE',
    RESTORE_STATE: 'RESTORE_STATE',
    SET_EMAIL: 'SET_EMAIL',
    CONTINUE_TO_NEXT_STEP: 'CONTINUE_TO_NEXT_STEP',
    SET_FLOW: 'SET_FLOW',
    RESET_PASSWORD_ERROR: 'RESET_PASSWORD_ERROR',
    RESET_KEY_ERROR: 'RESET_KEY_ERROR',
    SET_LANDING_PAGE: 'SET_LANDING_PAGE',
    SUBSCRIPTION_SUCCESSFUL: 'SUBSCRIPTION_SUCCESSFUL',
    GO_TO_WEBSITE: 'GO_TO_WEBSITE',
    SHOW_PAGE_HEADER: 'SHOW_PAGE_HEADER',
    SET_NEW_USER: 'SET_NEW_USER',
    SET_THANK_YOU_PAGE: 'SET_THANK_YOU_PAGE',
    SHOW_STEPPER: 'SHOW_STEPPER',
    INCREASE_STEPPER: 'INCREASE_STEPPER',
    SET_OSI_AMOUNT: 'SET_OSI_AMOUNT',
    SET_BRAND: 'SET_BRAND',
    SET_PROMOCODE: 'SET_PROMOCODE',
    REMOVE_LANDING_PAGE: 'REMOVE_LANDING_PAGE',
    SET_TAGRID: 'SET_TAGRID',
    RESET_STORE: 'RESET_STORE',
    CHECK_EXAMINATION: 'CHECK_EXAMINATION'
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Public constants

export const GETTERS = {
    LOGGEDIN: 'LOGGEDIN',
    CHECK_REQUIRED_COMPLETE: 'CHECK_REQUIRED_COMPLETE',
    PAYMENT_STATE: 'PAYMENT_STATE',
    PAYMENT_ERROR: 'PAYMENT_ERROR',
    IN_PAYMENT_FLOW: 'IN_PAYMENT_FLOW',
    ANSWERS: 'ANSWERS',
    QUESTIONS: 'QUESTIONS',
    NEED_EMAIL: 'NEED_EMAIL',
    CHECK_FINISH: 'CHECK_FINISH',
    CURRENCY: 'CURRENCY',
    AMOUNT: 'AMOUNT',
    PARTNER_PHONE_NUMBER: 'PARTNER_PHONE_NUMBER',
    IS_PARTNER_COVERED: 'IS_PARTNER_COVERED',
    IS_PARTNER_FLOW_ACTIVE: 'IS_PARTNER_FLOW_ACTIVE',
    IS_PARTNER_CHECK_COMPLETE: 'IS_PARTNER_CHECK_COMPLETE',
    IS_PARTNER_VIDEO_COMPLETE: 'IS_PARTNER_VIDEO_COMPLETE',
    PARTNER_ERROR: 'PARTNER_ERROR',
    IS_PARTNER_OTP_FLOW_STATE: 'IS_PARTNER_OTP_FLOW_STATE',
    QUESTIONS_VIDEO_LOGIN_FLOW: 'QUESTIONS_VIDEO_LOGIN_FLOW',
    PHONE_NUMBER: 'PHONE_NUMBER',
    IS_OSLO_FLOW: 'IS_OSLO_FLOW',
    GET_CASE_CODE: 'GET_CASE_CODE',
    IS_AURORA_FLOW: 'IS_AURORA_FLOW',
    GET_DESCRIPTION: 'GET_DESCRIPTION',
    IS_PARTNER_INTRO: 'IS_PARTNER_INTRO',
    USER_EMAIL: 'USER_EMAIL',
    USER_UUID: 'USER_UUID',
    UK_STEP_NAME: 'UK_STEP_NAME',
    UK_STEP_NUMBER: 'UK_STEP_NUMBER',
    PASSWORD_ERROR: 'PASSWORD_ERROR',
    OTP_ERROR: 'OTP_ERROR',
    JUST_ANSWERED: 'JUST_ANSWERED',
    NEW_USER: 'NEW_USER',
    LANDING_PAGE: 'LANDING_PAGE',
    THANK_YOU_PAGE: 'THANK_YOU_PAGE',
    BRAND: 'BRAND',
    PROMOCODE: 'PROMOCODE',
    GET_COOKIE: 'GET_COOKIE',
    GET_UK_AUTHENTICATION: 'GET_UK_AUTHENTICATION',
    TOKEN_EXPIRED: 'TOKEN_EXPIRED',
    SET_PASSWORD: 'SET_PASSWORD',
    GET_FLOW_TYPE: 'GET_FLOW_TYPE'
};

export const ACTIONS = {
    SET_FLOW: 'SET_FLOW',
    PARTNER_OSLO_CHECK: 'PARTNER_OSLO_CHECK',
    SUBMIT_CHECK: 'SUBMIT_CHECK',
    USER_SUBMIT_CHECK: 'USER_SUBMIT_CHECK',
    CHECK_EXAMINATION: 'CHECK_EXAMINATION',
    USER_UPDATE: 'USER_UPDATE',
    EXAMINATION: 'EXAMINATION',
    EXAMINATION_UPDATE: 'EXAMINATION_UPDATE',
    USER_ADDITIONAL_DATA: 'USER_ADDITIONAL_DATA',
    USER_SUBMIT_CHECK_UK: 'USER_SUBMIT_CHECK_UK',
    UPLOAD_IMAGE: 'UPLOAD_IMAGE',
    UPDATE_IMAGES: 'UPDATE_IMAGES',
    SECOND_OPINION: 'SECOND_OPINION',
    SET_PAYMENT: 'SET_PAYMENT',
    LOGIN: 'LOGIN',
    PHONE_LOGIN_OR_CREATE: 'PHONE_LOGIN_OR_CREATE',
    PARTNER_LOGIN: 'PARTNER_LOGIN',
    CHANGE_PASSWORD: 'CHANGE_PASSWORD',
    UPDATE_PASSWORD: 'UPDATE_PASSWORD',
    LOGIN_STOREBRAND: 'LOGIN_STOREBRAND',
    LOGOUT: 'LOGOUT',
    VERIFY: 'VERIFY',
    LOGIN_VERIFY: 'LOGIN_VERIFY',
    CHECK_PROMOCODE: 'CHECK_PROMOCODE',
    CHARGE_CARD: 'CHARGE_CARD',
    UPDATE_ANSWER: 'UPDATE_ANSWER',
    UPDATE_PAYMENT: 'UPDATE_PAYMENT',
    UPDATE_PARTNER: 'UPDATE_PARTNER',
    CONFIRM_QUESTION_PAYMENT: 'CONFIRM_QUESTION_PAYMENT',
    RESET: 'RESET',
    GET_DIAGNOSES: 'GET_DIAGNOSES',
    SET_PHONE_NUMBER: 'SET_PHONE_NUMBER',
    SET_PARTNER_FLOW_STATE_VIDEO: 'SET_PARTNER_FLOW_STATE_VIDEO',
    SET_PARTNER_FLOW_STATE_CHECK: 'SET_PARTNER_FLOW_STATE_CHECK',
    SET_PARTNER_VIDEO_COMPLETE: 'SET_PARTNER_VIDEO_COMPLETE',
    INIT_BANKID_LOGIN: 'INIT_BANKID_LOGIN',
    RESET_BANKID_LOGIN: 'RESET_BANKID_LOGIN',
    LOGIN_BANKID: 'LOGIN_BANKID',
    NAVIGATE_BACK_TO_PARTNER: 'NAVIGATE_BACK_TO_PARTNER',
    NAVIGATE_BACK_TO_AURORA: 'NAVIGATE_BACK_TO_AURORA',
    REFERRAL: 'REFERRAL',
    STICKY_HEADER: 'STICKY_HEADER',
    SHOW_HEADER: 'SHOW_HEADER',
    SET_REDIRECT_URL: 'SET_REDIRECT_URL',
    CONFIRM_PARTNER_EXAMINATION: 'CONFIRM_PARTNER_EXAMINATION',
    PARTNER_AURORA_CHECK: 'PARTNER_AURORA_CHECK',
    LOGIN_AURORA: 'LOGIN_AURORA',
    INTRO_PAGE: 'INTRO_PAGE',
    RESTORE_STATE: 'RESTORE_STATE',
    SET_EMAIL: 'SET_EMAIL',
    VERIFY_EMAIL: 'VERIFY_EMAIL',
    USER_EMAIL_VERIFIED: 'USER_EMAIL_VERIFIED',
    CONTINUE_TO_NEXT_STEP: 'CONTINUE_TO_NEXT_STEP',
    USER_LOGIN: 'USER_LOGIN',
    VERIFY_OTP: 'VERIFY_OTP',
    VERIFY_PASSWORD: 'VERIFY_PASSWORD',
    RESET_PASSWORD_ERROR: 'RESET_PASSWORD_ERROR',
    RESET_KEY_ERROR: 'RESET_KEY_ERROR',
    CHANGE_PAGE: 'CHANGE_PAGE',
    FORGOT_PASSWORD: 'FORGOT_PASSWORD',
    SET_LANDING_PAGE: 'SET_LANDING_PAGE',
    SET_SUBSCRIPTION_EMAIL: 'SET_SUBSCRIPTION_EMAIL',
    GO_TO_WEBSITE: 'GO_TO_WEBSITE',
    SHOW_PAGE_HEADER: 'SHOW_PAGE_HEADER',
    SET_THANK_YOU_PAGE: 'SET_THANK_YOU_PAGE',
    SHOW_STEPPER: 'SHOW_STEPPER',
    INCREASE_STEPPER: 'INCREASE_STEPPER',
    SET_BRAND: 'SET_BRAND',
    GET_PROMOCODE: 'GET_PROMOCODE',
    SET_PROMOCODE: 'SET_PROMOCODE',
    GET_DOCTORS: 'GET_DOCTORS',
    REMOVE_LANDING_PAGE: 'REMOVE_LANDING_PAGE',
    SET_TAGRID: 'SET_TAGRID',
    RESET_STORE: 'RESET_STORE',
    SUBMIT_FEEDBACK: 'SUBMIT_FEEDBACK'
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

export const store = new Vuex.Store({
    modules: {
        video
    },
    plugins: [createPersistedState({
        key: 'data',
        storage: {
            getItem: key => ls.get(key),
            setItem: (key, value) => ls.set(key, value),
            removeItem: key => ls.remove(key)
        }
    })],
    state: initialState,
    mutations: {
        [MUTATIONS.CHECK_EXAMINATION] (state, data) {
            Object.keys(data).forEach(function (key) { state.check.answers[key] = data[key]; });
            try {
                if (state.check.answers['body_part']) {
                    state.check.answers['body_part'] = JSON.parse(
                        state.check.answers['body_part']
                    );
                }
            } catch (err) {
                state.check.answers['body_part'] = [
                    { custom: state.check.answers['body_part'] }
                ];
            }
            state.examination = data.examination;
            state.userPasswordAlreadySet = data.userPasswordAlreadySet;
        },
        [MUTATIONS.RESET_STORE] (state) {
            this.replaceState(
                Object.assign(
                    state,
                    initialState)
            );
        },
        [MUTATIONS.SET_TAGRID] (state, tagrid) {
            state.tagrid = tagrid;
        },
        [MUTATIONS.SET_PROMOCODE] (state, promocode) {
            state.promoCode = promocode;
        },
        [MUTATIONS.INCREASE_STEPPER] (state) {
            state.uk.stepper.checkStepper += 1;
        },
        [MUTATIONS.SET_BRAND] (state, brand) {
            state.partner.brand = brand;
        },
        [MUTATIONS.SET_THANK_YOU_PAGE] (state) {
            state.thankYouPage = true;
        },
        [MUTATIONS.SET_NEW_USER] (state, data) {
            state.newUser = data;
        },
        [MUTATIONS.GO_TO_WEBSITE] () {
            window.localStorage.clear();
            window.location.replace(process.env.VUE_APP_PARENT_URL);
        },
        [MUTATIONS.SUBSCRIPTION_SUCCESSFUL] (state) {
            state.subscriptionSuccessful = true;
        },
        [MUTATIONS.SET_LANDING_PAGE] (state) {
            state.landingPage = true;
        },
        [MUTATIONS.RESET_PASSWORD_ERROR] (state) {
            state.passwordError = false;
        },
        [MUTATIONS.RESET_KEY_ERROR] (state) {
            state.otpError = false;
        },
        [MUTATIONS.SET_EMAIL] (state, email) {
            state.check.answers.email = email.email;
            state.user.email = email.email;
        },
        [MUTATIONS.SET_VIDEO_FLOW] (state) {
            state.videoFlow = !state.videoFlow;
        },
        async [MUTATIONS.RESTORE_STATE] (state) {
            if (localStorage.getItem('store')) {
                localStorage.removeItem('store');
            }
            await this.replaceState(
                Object.assign(
                    state,
                    JSON.parse(localStorage.getItem('data'))
                )
            );
        },
        [MUTATIONS.SET_INTRO_PAGE] (state, status) {
            state.partner.intro = status;
        },
        [MUTATIONS.SET_BANKID_DETAILS] (state, details) {
            state.bankid.operationUrl = details.operationUrl;
            state.bankid.operationId = details.operationId;
        },
        [MUTATIONS.RESET_BANKID_LOGIN] (state) {
            state.bankid.operationUrl = null;
            state.bankid.operationId = null;
        },
        [MUTATIONS.SET_CASE_CODE] (state, caseCode) {
            state.caseCode = caseCode;
        },
        [MUTATIONS.SET_PARTNER_FLOW_STATE] (state, stateName) {
            // stateName = check or video
            if (stateName === 'check') {
                state.partner.checkFlow = true;
                state.partner.videoFlow = false;
            } else if (stateName === 'video') {
                state.partner.checkFlow = false;
                state.partner.videoFlow = true;
            } else {
                throw new Error(
                    'SET_PARTNER_FLOW_STATE - unknown state: ' + stateName
                );
            }
        },
        [MUTATIONS.PARTNER_ERROR] (state, error) {
            state.partner.error = error;
            if (error === 'VERIFY') {
                state.partner.answers.key = null;
                state.partner.answers.partnerphone = null;
            }
        },
        [MUTATIONS.PARTNER_SUCCESS] (state) {
            state.partner.error = null;
        },
        [MUTATIONS.CONFIRM_PARTNER_EXAMINATION] (state) {
            state.partner.examinationSubmitConfirmed = true;
        },
        [MUTATIONS.SET_SHOW_HEADER_BACK_BUTTON] (state, bool) {
            state.showHeaderBackButton = bool;
        },
        [MUTATIONS.SET_PHONE_NUMBER] (state, number) {
            state.check.answers.phone = number;
        },
        [MUTATIONS.SET_EXAMINATION] (state, examinationUid) {
            state.examination = examinationUid;
        },
        [MUTATIONS.SHOW_HEADER] (state) {
            state.showHeader = true;
        },
        [MUTATIONS.HIDE_HEADER] (state) {
            state.showHeader = false;
        },
        [MUTATIONS.SET_REDIRECT_URL] (state, redirectUrl) {
            state.redirectUrl = redirectUrl;
        },
        [MUTATIONS.SET_PARTNER_OTP_FLOW_STATE] (state, inOtpFlow) {
            state.partner.otpFlow = inOtpFlow;
        },
        [MUTATIONS.UPDATE_PAYMENT_RESPONSE] (state, data) {
            Vue.set(state.payment.response, 'response', data);
            state.payment.state = {
                submit: false,
                verify: false,
                charged: false,
                confirm: true
            };
        },
        [MUTATIONS.SET_STICKY_HEADER] (state, sticky) {
            state.stickyHeader = sticky;
        },
        [MUTATIONS.UPDATE_ANSWER] (state, data) {
            Vue.set(state.check.answers, data.question, data.answer);
            if (data.question === 'gender') {
                if (data.answer === 'man') {
                    state.check.answers.pregnant = null;
                    state.check.answers.breastfeeding = null;
                }
            } else if (data.question === 'category') {
                const updated = questions.examinationAnswerTpl(
                    state.check.answers
                );
                state.check.answers = mapValues(updated, (value, key) => {
                    return state.check.answers[key] || null;
                });
            }
        },
        [MUTATIONS.UPDATE_PAYMENT] (state, data) {
            if (state.partner.active && !state.partner.isCovered) {
                Vue.set(state.partner.answers, data.key, data.value);
            }
        },
        [MUTATIONS.PARTNER_CHECK_COMPLETE] (state) {
            state.partner.checkComplete = true;
        },
        [MUTATIONS.PARTNER_VIDEO_COMPLETE] (state) {
            state.partner.videoComplete = true;
        },
        [MUTATIONS.UPDATE_PARTNER] (state, data) {
            if (state.isAuroraFlow) {
                Vue.set(state.check.answers, data.question, data.answer);
            } else {
                Vue.set(state.partner.answers, data.question, data.answer);
            }
        },
        [MUTATIONS.TOTAL_AMOUNT_POST_PROMO] (state, data) {
            state.totalAmount = data.totalAmount;
            state.promoCode = data.promoCode;
        },
        [MUTATIONS.RESET] (state) {
            state.check.answers = merge({}, answersTpl);
            state.check.finish = { state: false, time: null };
            state.payment.state = {
                submit: false,
                verify: false,
                charged: false,
                confirm: false
            };
            state.token = null;
            state.examination = null;
            state.diagnoses = null;
        },
        [MUTATIONS.UPDATE_LOGIN] (state, data) {
            if (data.token && data.bankid) {
                state.user.name = data.name;
                state.user.uuid = data.uuid;
                state.token = data.token;
                state.loggedin = true;
                state.check.answers.bankid = true;
            } else if (data.token && data.partner) {
                state.token = data.token;
                state.loggedin = true;
                state.partner.active = true;
                state.partner.phonenumber = data.phonenumber;
                state.partner.policynumber = data.policynumber;
                state.partner.claim_uuid = data.claim_uuid;
                state.partner.isCovered =
                    (data.policynumber !== '' &&
                        data.policynumber !== undefined) ||
                    (data.claim_uuid !== '' && data.claim_uuid !== undefined); // we assume the parnter is covered from either having a policy number or a claim number
                state.partner.uuid = data.uuid;
                state.partner.name = data.name;
                state.needemail = false;
            } else if (data.token && data.auroraPartner) {
                state.partner.active = true;
                state.isAuroraFlow = true;
                state.partner.isCovered = true;
                state.loggedin = true;
                state.token = data.token;
                state.partner.phonenumber = data.phonenumber;
                state.check.answers.email = data.email;
                state.user.email = data.email;
                state.needemail = false;
            } else if (data.token && data.examination) {
                state.token = data.token;
                state.loggedin = true;
                state.examination = data.examination;
                state.user.uuid = data.uuid;
                if (data.email) {
                    state.needemail = false;
                    state.user.email = data.email;
                }
            } else if (data.status) {
                if (data.status === 'ok') {
                    state.loggedin = true;
                    if (data.email) {
                        state.needemail = false;
                        state.user.email = data.email;
                        state.check.answers.email = data.email;
                        state.newUser = false;
                    }
                } else {
                    state.token = null;
                    sessionStorage.removeItem('token');
                    state.examination = null;
                    state.check.answers.phone = null;
                    state.check.answers.password = null;
                    state.check.answers.key = null;
                }
            } else if (data.token) {
                state.loggedin = true;
            }
        },
        [MUTATIONS.UPDATE_IMAGE] (state, result) {
            if (has(state.check.answers, result.id)) {
                state.check.answers[result.id] = result.uuid;
                return result;
            }
            return false;
        },
        [MUTATIONS.FINISH_CHECK] (state) {
            state.check.finish.state = true;
            state.check.finish.time = state.check.answers.deadline;
            forIn(state.check.answers, p => {
                p = null;
            });
            forIn(state.check.answers, q => {
                q = null;
            });
        },
        [MUTATIONS.ERROR] (state, error) {
            state.payment.error = error;
            if (error === 'SUBMIT_CHECK') {
                state.check.answers.phone = null;
                state.check.answers.password = null;
                state.check.answers.token = null;
                state.payment.state.submit = false;
            } else if (error === 'PASSWORD') {
                state.check.answers.password = null;
                state.payment.state.verify = false;
                state.passwordError = true;
            } else if (error === 'VERIFY') {
                state.check.answers.password = null;
                state.check.answers.key = null;
                state.payment.state.verify = false;
                state.payment.state.submit = false;
                state.otpError = true;
            } else if (error === 'CHARGE_CARD') {
                state.check.answers.token = null;
                state.payment.state.charged = false;
            } else if (error === 'INVALID_PHONE') {
                state.check.answers.phone = null;
            } else if (error === 'SUBMIT_OSLO_CHECK') {
                state.check.answers.closeup = null;
                state.check.answers.overview = null;
            }
        },
        [MUTATIONS.SUCCESS] (state, success) {
            state.payment.error = false;
            if (success === 'SUBMIT_CHECK') {
                state.payment.state.submit = true;
            } else if (success === 'VERIFY') {
                if (state.partner.active) {
                    // If we verified OTP in partner flow
                    state.partner.otpVerified = true;
                } else {
                    state.payment.state.verify = true;
                }
            } else if (success === 'CHARGE_CARD') {
                state.payment.state.charged = true;
            }
        },
        [MUTATIONS.LOGOUT] (state) {
            state.loggedin = false;
            state.bankid.operationId = null;
            state.bankid.operationUrl = null;
            state.token = null;
        },
        [MUTATIONS.CONTINUE_TO_NEXT_STEP] (state) {
            if (state.newUser) {
                if (!state.uk.patientDetails) {
                    state.uk.patientDetails = true;
                } else if (state.uk.patientDetails && !state.uk.medicalIssue) {
                    state.uk.medicalIssue = true;
                } else if (
                    state.uk.patientDetails &&
                    state.uk.medicalIssue &&
                    !state.uk.medicalHistory
                ) {
                    state.uk.medicalHistory = true;
                } else if (
                    state.uk.patientDetails &&
                    state.uk.medicalIssue &&
                    state.uk.medicalHistory &&
                    !state.uk.userAuthentication
                ) {
                    state.uk.userAuthentication = true;
                } else if (
                    state.uk.patientDetails &&
                    state.uk.medicalIssue &&
                    state.uk.medicalHistory &&
                    state.uk.userAuthentication &&
                    !state.uk.payment
                ) {
                    state.uk.payment = true;
                    state.thankYouPage = true;
                }
            } else {
                if (!state.uk.medicalIssue) {
                    state.uk.medicalIssue = true;
                } else if (state.uk.medicalIssue && !state.uk.userHistory) {
                    state.uk.userHistory = true;
                } else if (
                    state.uk.medicalIssue &&
                    state.uk.userHistory &&
                    !state.uk.payment
                ) {
                    state.uk.payment = true;
                }
            }
            window.scrollTo(0, 0);
        },
        [MUTATIONS.SET_OSLO_PARTNER] (state) {
            state.isOsloFlow = true;
        },
        [MUTATIONS.SET_UUID] (state, uuid) {
            state.user.uuid = uuid;
        },
        [MUTATIONS.SET_PHONE] (state, phone) {
            state.check.answers.phone = phone;
        },
        [MUTATIONS.SHOW_PAGE_HEADER] (state, data) {
            state.showPageHeader = data;
        },
        [MUTATIONS.SHOW_STEPPER] (state) {
            state.showStepper = true;
        },
        [MUTATIONS.SET_OSI_AMOUNT] (state, amount) {
            state.osiAmount = amount;
        },
        [MUTATIONS.REMOVE_LANDING_PAGE] (state) {
            state.landingPage = false;
        },
        [MUTATIONS.SET_FLOW] (state, data) {
            state.flowType = data;
        }
    },
    getters: {
        [GETTERS.GET_FLOW_TYPE]: state => state.flowType,
        [GETTERS.SET_PASSWORD]: state => state.check.answers.userPasswordAlreadySet,
        [GETTERS.TOKEN_EXPIRED]: state => state.uk.tokenExpired,
        [GETTERS.PROMOCODE]: state => state.promoCode,
        [GETTERS.BRAND]: state => state.partner.brand,
        [GETTERS.LANDING_PAGE]: state => state.landingPage,
        [GETTERS.THANK_YOU_PAGE]: state => state.thankYouPage,
        [GETTERS.NEW_USER]: state => state.newUser,
        [GETTERS.JUST_ANSWERED]: state => state.justAnswered,
        [GETTERS.PASSWORD_ERROR]: state => state.passwordError,
        [GETTERS.OTP_ERROR]: state => state.otpError,
        [GETTERS.USER_EMAIL]: state => state.user.email,
        [GETTERS.USER_UUID]: state => state.user.uuid,
        [GETTERS.IS_PARTNER_INTRO]: state => state.partner.intro,
        [GETTERS.IS_OSLO_FLOW]: state => state.isOsloFlow,
        [GETTERS.IS_AURORA_FLOW]: state => state.isAuroraFlow,
        [GETTERS.GET_DESCRIPTION]: state => {
            if (state.isAuroraFlow) {
                return state.check.answers.description;
            } else {
                return state.partner.answers.description;
            }
        },
        [GETTERS.GET_CASE_CODE]: state => state.caseCode,
        [GETTERS.PHONE_NUMBER]: state => {
            return state.check.answers.phone;
        },
        [GETTERS.IS_PARTNER_COVERED]: state => state.partner.isCovered,
        [GETTERS.IS_PARTNER_OTP_FLOW_STATE]: state => state.partner.otpFlow,
        [GETTERS.IS_PARTNER_CHECK_COMPLETE]: state =>
            state.partner.checkComplete,
        [GETTERS.IS_PARTNER_VIDEO_COMPLETE]: state =>
            state.partner.videoComplete,
        [GETTERS.IS_PARTNER_FLOW_ACTIVE]: state => state.partner.active,
        [GETTERS.PARTNER_PHONE_NUMBER]: state => state.partner.phonenumber,
        [GETTERS.UPLOAD_PROGRESS]: state => state.uploadProgress,
        [GETTERS.LOGGEDIN]: state => state.loggedin,
        [GETTERS.PAYMENT_STATE]: state => state.payment.state,
        [GETTERS.PAYMENT_ERROR]: state => state.payment.error,
        [GETTERS.PARTNER_ERROR]: state => state.partner.error,
        [GETTERS.ANSWERS]: state => state.check.answers,
        [GETTERS.NEED_EMAIL]: state => state.needemail,
        [GETTERS.CHECK_FINISH]: state => state.check.finish,
        [GETTERS.DIAGNOSES]: state => state.diagnoses,
        [GETTERS.USER_UUID]: state => state.user.uuid,
        [GETTERS.CHECK_REQUIRED_COMPLETE]: state => {
            if (state.flowType !== 'video') {
                return questions.examinationIsValid(state.check.answers);
            } else {
                if (state.isAuroraFlow) {
                    return questions.examinationIsValid(
                        state.check.answers,
                        auroraVideoFlow
                    );
                } else {
                    return questions.examinationIsValid(
                        state.check.answers,
                        videoQuestionFlow
                    );
                }
            }
        },
        [GETTERS.QUESTIONS]: state => {
            if (state.payment.state.charged || state.partner.examinationSubmitConfirmed) {
                // Yes, then lets just return an empty question array.
                return [];
            } else if (state.flowType === 'video') {
                return questions.examination(
                    videoQuestionFlow,
                    state.check.answers,
                    state.partner.active,
                    state.partner.isCovered,
                    state.isAuroraFlow
                );
            } else if (!questions.examinationIsValid(state.check.answers)) {
                // Do we have examination or payment questions that has not been answered?
                // Yes, we have examination questions that are unanswered.
                return questions.examination(
                    questionFlow,
                    state.check.answers,
                    state.partner.active,
                    state.partner.isCovered,
                    state.isAuroraFlow
                );
            }
        },
        [GETTERS.IN_PAYMENT_FLOW]: state => {
            if (
                state.payment.state.charged ||
                !questions.examinationIsValid(state.check.answers)
            ) {
                return false;
            }
            if (!isUndefined(find(state.check.answers, isNull))) {
                return true;
            }
            return false;
        },
        [GETTERS.CURRENCY]: state => currency => {
            // TODO: Implement check if currency is available if passed in.
            return currency || state.currency || process.env.VUE_APP_CURRENCY;
        },
        [GETTERS.AMOUNT]: (state, getters) => currency => {
            const CURRENCY = getters.CURRENCY(currency);
            const CURRENCY_LOCALE = `VUE_APP_CURRENCY_${CURRENCY}_LOCALE`;
            return { CURRENCY_LOCALE: process.env[CURRENCY_LOCALE] };
        },
        [GETTERS.GET_COOKIE]: () => name => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) {
                return parts
                    .pop()
                    .split(';')
                    .shift();
            }
        }
    },
    actions: {
        [ACTIONS.SET_FLOW] ({ commit }, flow) {
            commit(MUTATIONS.SET_FLOW, flow);
        },
        [ACTIONS.RESET_STORE] ({ commit }) {
            commit(MUTATIONS.RESET_STORE);
        },
        [ACTIONS.SET_TAGRID] ({ commit }, tagrid) {
            commit(MUTATIONS.SET_TAGRID, tagrid);
        },
        [ACTIONS.REMOVE_LANDING_PAGE] ({ commit }) {
            commit(MUTATIONS.REMOVE_LANDING_PAGE);
        },
        [ACTIONS.SET_PROMOCODE] ({ commit }, promoCode) {
            commit(MUTATIONS.SET_PROMOCODE, promoCode);
        },
        [ACTIONS.INCREASE_STEPPER] ({ commit }) {
            commit(MUTATIONS.INCREASE_STEPPER);
        },
        [ACTIONS.SET_BRAND] ({ commit }, brand) {
            commit(MUTATIONS.SET_BRAND, brand);
        },
        [ACTIONS.SET_THANK_YOU_PAGE] ({ commit }) {
            commit(MUTATIONS.SET_THANK_YOU_PAGE);
        },
        [ACTIONS.SHOW_STEPPER] ({ commit }) {
            commit(MUTATIONS.SHOW_STEPPER);
        },
        [ACTIONS.SHOW_PAGE_HEADER] ({ commit }, data) {
            commit(MUTATIONS.SHOW_PAGE_HEADER, data);
        },
        [ACTIONS.GO_TO_WEBSITE] ({ commit }) {
            commit(MUTATIONS.GO_TO_WEBSITE);
        },
        [ACTIONS.SET_LANDING_PAGE] ({ commit }) {
            commit(MUTATIONS.SET_LANDING_PAGE);
        },
        [ACTIONS.CHANGE_PAGE] ({ commit }) {
            commit(MUTATIONS.CHANGE_PAGE);
        },
        [ACTIONS.RESTORE_STATE] ({ commit }) {
            commit(MUTATIONS.RESTORE_STATE);
        },
        [ACTIONS.RESET_PASSWORD_ERROR] ({ commit }) {
            commit(MUTATIONS.RESET_PASSWORD_ERROR);
        },
        [ACTIONS.RESET_KEY_ERROR] ({ commit }) {
            commit(MUTATIONS.RESET_KEY_ERROR);
        },
        [ACTIONS.INTRO_PAGE] ({ commit }, status) {
            commit(MUTATIONS.SET_INTRO_PAGE, status);
        },
        [ACTIONS.STICKY_HEADER] ({ commit }, sticky) {
            commit(MUTATIONS.SET_STICKY_HEADER, sticky);
        },
        [ACTIONS.SHOW_HEADER] ({ commit }, show) {
            if (show) {
                commit(MUTATIONS.SHOW_HEADER);
            } else {
                commit(MUTATIONS.HIDE_HEADER);
            }
        },
        [ACTIONS.SET_REDIRECT_URL] ({ commit }, redirectUrl) {
            commit(MUTATIONS.SET_REDIRECT_URL, redirectUrl);
        },
        async [ACTIONS.GET_DOCTORS] ({ state }) {
            return Vue.axios
                .post('/doctors', { region: state.region })
                .then(response => {
                    return response.data.doctors;
                });
        },
        async [ACTIONS.USER_EMAIL_VERIFIED] ({ commit }) {
            return Vue.axios.get('/user-email-verified').then(response => {
                return response.data;
            });
        },
        async [ACTIONS.REPORT_BUG] ({ state }, bugJson) {
            let requestBody = {
                bug_json: JSON.stringify(bugJson)
            };
            state.user.uuid && (requestBody['user_uuid'] = state.user.uuid);
            return Vue.axios.post('/submit-bug', requestBody).then(response => {
                return response.data;
            });
        },
        async SET_SUBSCRIPTION_EMAIL ({ commit, state }, email) {
            let data = { uuid: state.user.uuid, subscription_email: email };
            return Vue.axios
                .post('/email-subscription', data)
                .then(response => {
                    commit(MUTATIONS.SUBSCRIPTION_SUCCESSFUL);
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        async [ACTIONS.VERIFY_EMAIL] ({ commit, state }, data) {
            data['region'] = state.region;
            return Vue.axios
                .post('/verify-email', data)
                .then(response => {
                    return response.data;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        async [ACTIONS.SET_EMAIL] ({ commit, state }, email) {
            if (email !== state.user.email) {
                return Vue.axios
                    .post('/set-email', { email: email, region: state.region })
                    .then(response => {
                        if (response.data.status) {
                            commit(MUTATIONS.SET_EMAIL, { email: email });
                            return true;
                        } else {
                            if (response.data.code === 'Email Already Exists') {
                                commit(MUTATIONS.SET_EMAIL, { email: email });
                                return 'alreadyExists';
                            }
                        }
                    })
                    .catch(reason => {
                        return {
                            error: true,
                            message: 'error.message',
                            reason: reason
                        };
                    });
            } else {
                commit(MUTATIONS.SET_EMAIL, { email: email });
                return true;
            }
        },
        [ACTIONS.CONFIRM_PARTNER_EXAMINATION] (
            { commit },
            { examinationId, claimUUID }
        ) {
            return Vue.axios
                .post('/confirm-partner-examination', {
                    examination: examinationId,
                    claim_uuid: claimUUID
                })
                .then(response => {
                    if (response.data.status === 'ok') {
                        commit(MUTATIONS.CONFIRM_PARTNER_EXAMINATION);
                    }
                    return true;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        async [ACTIONS.SET_PHONE_NUMBER] ({ commit }, number) {
            return Vue.axios
                .post('/user-set-phone', { phonenumber: number })
                .then(response => {
                    if (response.data.status === 'phone.failure') {
                        commit(MUTATIONS.ERROR, 'INVALID_PHONE');
                    } else {
                        commit(MUTATIONS.SET_PHONE_NUMBER, number);
                    }

                    return true;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        async [ACTIONS.INIT_BANKID_LOGIN] ({ commit, state }, provider) {
            try {
                const response = await Vue.axios.post('/init-bankid', {
                    provider: provider,
                    region: state.region,
                    requestFrom: 'flow'
                });
                if (response && response.data) {
                    commit(MUTATIONS.SET_BANKID_DETAILS, response.data);
                    return true;
                }
            } catch (error) {
                return {
                    error: true,
                    message: 'error.message',
                    reason: error
                };
            }
        },
        async [ACTIONS.RESET_BANKID_LOGIN] ({ commit }) {
            commit(MUTATIONS.RESET_BANKID_LOGIN);
        },
        async [ACTIONS.LOGIN_BANKID] ({ commit, state }, register = false) {
            let data = {
                locale: state.locale,
                region: state.region,
                operationId: state.bankid.operationId,
                register: register
            };
            if (state.isAuroraFlow) {
                data['email'] = state.partner.email;
                data['register'] = false;
            }
            return Vue.axios
                .post('/login-bankid', data)
                .then(response => {
                    if (response.data.state === 'STATE_COMPLETED') {
                        let loginObject = {
                            status: 'ok',
                            bankid: true,
                            token: response.data.token,
                            name: response.data.name,
                            uuid: response.data.uuid,
                            email_verified: response.data.email_verified
                        };
                        commit(MUTATIONS.UPDATE_LOGIN, loginObject);
                    }
                    return {
                        state: response.data.state
                    };
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.NAVIGATE_BACK_TO_PARTNER] () {
            window.localStorage.clear();
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(
                    'blifrisk/snapmed/finish'
                );
            } else {
                window.alert('Klikk X oppe i hjørnet for å lukke Snapmed.');
            }
        },
        [ACTIONS.NAVIGATE_BACK_TO_AURORA] () {
            window.localStorage.clear();
            window.location.replace(process.env.VUE_APP_AURORA_URL);
        },
        [ACTIONS.SET_PARTNER_VIDEO_COMPLETE] ({ commit }) {
            commit(MUTATIONS.CONFIRM_PARTNER_EXAMINATION);
        },
        [ACTIONS.SET_PARTNER_FLOW_STATE_VIDEO] ({ commit }) {
            commit(MUTATIONS.SET_PARTNER_FLOW_STATE, 'video');
        },
        [ACTIONS.SET_PARTNER_FLOW_STATE_CHECK] ({ commit }) {
            commit(MUTATIONS.SET_PARTNER_FLOW_STATE, 'check');
        },
        [ACTIONS.SUBMIT_CHECK] ({ commit, state }, password) {
            // Perform axios request instead of just resolving directly
            state.check.answers['password'] = password;
            let data = {};
            let url = '/check';
            //
            if (!state.examination) {
                data = merge(
                    { locale: state.locale, region: state.region },
                    state.check.answers
                );
            } else {
                data = merge({ phonenumber: state.check.answers['phone'], password: state.check.answers['password'], examination: state.examination });
                url = '/recheck';
            }
            return Vue.axios
                .post(url, data)
                .then(response => {
                    commit(MUTATIONS.SUCCESS, 'SUBMIT_CHECK');
                    commit(MUTATIONS.UPDATE_LOGIN, response.data);
                    return response.data;
                })
                .catch(reason => {
                    commit(MUTATIONS.ERROR, 'SUBMIT_CHECK');
                    throw reason;
                });
        },
        [ACTIONS.SUBMIT_FEEDBACK] ({ commit, state }, data) {
            data['user'] = state.user.uuid;
            return Vue.axios
                .post('/feedback', data)
                .then(() => {
                    commit(MUTATIONS.GO_TO_WEBSITE);
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.CHECK_EXAMINATION] ({ commit, state }) {
            if (state.user.uuid && state.token) {
                return Vue.axios
                    .get('/check-examination')
                    .then(response => {
                        commit(MUTATIONS.CHECK_EXAMINATION, response.data);
                    })
                    .catch(reason => {
                        throw reason;
                    });
            }
        },
        [ACTIONS.USER_UPDATE] ({ commit, state }) {
            let data = state.check.answers;
            if (!state.newUser) {
                data['examination'] = state.examination;
            }
            return Vue.axios
                .post('/user-update', data)
                .then(response => {
                    if (state.newUser) {
                        commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                    }
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.EXAMINATION] ({ commit, state }) {
            let data = state.check.answers;
            let age = moment().diff(
                state.check.answers.date_of_birth,
                'years',
                false
            );
            state.check.answers.age = age;
            if (state.tagrid) {
                data = merge({ affiliate_partner: 'tagserve' }, data);
            }
            return Vue.axios
                .post('/examination', data)
                .then(response => {
                    commit(
                        MUTATIONS.SET_EXAMINATION,
                        response.data.examination
                    );
                    commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.EXAMINATION_UPDATE] ({ commit, state }) {
            let data = state.check.answers;
            data['examination'] = state.examination;
            return Vue.axios
                .post('/examination-update', data)
                .then(() => {
                    if (state.newUser) {
                        commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                    }
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.USER_ADDITIONAL_DATA] ({ commit, state }) {
            let data = {'address': state.check.answers.address, 'idProof': state.check.answers.idProof};
            return Vue.axios
                .post('/user-additional-data', data)
                .then(() => {
                    commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.USER_SUBMIT_CHECK_UK] ({ commit, state }) {
            if (!state.examination) {
                let data = state.check.answers;
                let age = moment().diff(
                    state.check.answers.date_of_birth,
                    'years',
                    false
                );
                state.check.answers.age = age;
                if (state.tagrid) {
                    data = merge({ affiliate_partner: 'tagserve' }, data);
                }
                return Vue.axios
                    .post('/user-submit-check-uk', data)
                    .then(response => {
                        commit(
                            MUTATIONS.SET_EXAMINATION,
                            response.data.examination
                        );
                        commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                    })
                    .catch(reason => {
                        throw reason;
                    });
            } else {
                commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
            }
        },
        [ACTIONS.USER_SUBMIT_CHECK] ({ commit, dispatch, state }) {
            let data = {};
            let url = '/user-submit-check';
            //
            if (!state.examination) {
                data = state.check.answers;
            } else {
                data = { examination: state.examination };
                url = '/recheck';
            }
            return Vue.axios
                .post(url, data)
                .then(async response => {
                    if (response.data.examination) {
                        // if we're in partner flow, we set some flags
                        if (state.partner.active) {
                            if (state.partner.isCovered) {
                                await gtag('event', 'booking_complete', {
                                    event_category: 'booking',
                                    event_label:
                                        'booking_complete_without_payment'
                                });
                                await dispatch(ACTIONS.CONFIRM_PARTNER_EXAMINATION, {
                                    examinationId: response.data.examination,
                                    claimUUID: state.partner.claim_uuid
                                }); // confirm partner submit if this parnter is covered by insurance
                            }
                        }
                        commit(
                            MUTATIONS.SET_EXAMINATION,
                            response.data.examination
                        );
                        commit(MUTATIONS.SUCCESS, 'SUBMIT_CHECK');
                    } else {
                        commit(MUTATIONS.ERROR, 'SUBMIT_CHECK');
                    }
                    return response.data;
                })
                .catch(reason => {
                    commit(MUTATIONS.ERROR, 'SUBMIT_CHECK');
                    throw reason;
                });
        },
        [ACTIONS.FORGOT_PASSWORD] ({ state }) {
            return Vue.axios
                .post('/forgot-password', { region: state.region })
                .then(response => {
                    console.log(response);
                })
                .catch(reason => {
                    throw reason;
                });
        },
        [ACTIONS.VERIFY] ({ commit, state }, key) {
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/verify/' + key, {})
                .then(response => {
                    commit(MUTATIONS.SUCCESS, 'VERIFY');
                    // do not update login if verify check fails for partner users (these should remain logged in)
                    return commit(MUTATIONS.UPDATE_LOGIN, response.data);
                })
                .catch(reason => {
                    if (state.partner.active) {
                        commit(MUTATIONS.PARTNER_ERROR, 'VERIFY');
                    } else {
                        commit(MUTATIONS.ERROR, 'VERIFY');
                    }
                    throw reason;
                });
        },
        [ACTIONS.VERIFY_OTP] ({ commit, state }, key) {
            let data = merge({
                phonenumber: state.check.answers.phone,
                uuid: state.user.uuid,
                otp: key
            });
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/verify-otp', data)
                .then(response => {
                    commit(MUTATIONS.SUCCESS, 'VERIFY');
                    // do not update login if verify check fails for partner users (these should remain logged in)
                    return commit(MUTATIONS.UPDATE_LOGIN, response.data);
                })
                .catch(reason => {
                    if (state.partner.active) {
                        commit(MUTATIONS.PARTNER_ERROR, 'VERIFY');
                    } else {
                        commit(MUTATIONS.ERROR, 'VERIFY');
                    }
                    throw reason;
                });
        },
        [ACTIONS.VERIFY_PASSWORD] ({ commit, state }, key) {
            let data = merge({ password: key });
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/verify-password', data)
                .then(response => {
                    if (response.data.data) {
                        commit(MUTATIONS.SUCCESS, 'PASSWORD');
                    }
                })
                .catch(reason => {
                    commit(MUTATIONS.ERROR, 'PASSWORD');
                    throw reason;
                });
        },
        [ACTIONS.LOGIN_VERIFY] ({ commit, state }, { key, passwordReset }) {
            // Perform axios request instead of just resolving directly
            let data = {};
            if (passwordReset) {
                data = {
                    passwordReset: passwordReset,
                    region: state.region
                };
            }
            return Vue.axios
                .post('/verify/' + key, data)
                .then(response => {
                    commit(MUTATIONS.UPDATE_LOGIN, response.data);
                    return response.data;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        [ACTIONS.GET_PROMOCODE] ({ commit, getters, state }) {
            if (!state.partner.active) {
                const data = {
                    currency: getters.CURRENCY()
                };
                return Vue.axios
                    .post('/getpromo', data)
                    .then(response => {
                        commit(MUTATIONS.SET_PROMOCODE, response.data);
                    })
                    .catch(reason => {
                        return false;
                    });
            }
        },
        [ACTIONS.CHECK_PROMOCODE] (
            { commit, getters },
            { promoCode, type, currency }
        ) {
            // Perform axios request instead of just resolving directly
            const data = {
                promoCode: promoCode,
                type: type,
                currency: getters.CURRENCY()
            };
            return Vue.axios
                .post('/checkpromo', data)
                .then(response => {
                    // commit(MUTATIONS.UPDATE_LOGIN, response.data);
                    return response.data;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        [ACTIONS.UPLOAD_IMAGE] ({ commit }, data) {
            gtag('event', 'upload_image', { event_category: 'engagement' });
            if (!this.state.check.answers[data.id] || data.file.has('image')) {
                const uploadConfig = {
                    onUploadProgress: function (progressEvent) {
                        const percentCompleted = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        data.callback(percentCompleted);
                    }
                };
                // Perform axios request instead of just resolving directly
                return Vue.axios
                    .post('/upload', data.file, uploadConfig)
                    .then(response => {
                        const result = {
                            uuid: response.data.image,
                            id: data.id
                        };
                        commit(MUTATIONS.UPDATE_IMAGE, result);
                        return result;
                    })
                    .catch(reason => {
                        return {
                            error: true,
                            message: 'error.message',
                            reason: reason
                        };
                    });
            } else {
                const res = this.state.check.answers[data.id];
                const result = {
                    uuid: res,
                    id: data.id
                };
                commit(MUTATIONS.UPDATE_IMAGE, {uuid: null, id: data.id});
                commit(MUTATIONS.UPDATE_IMAGE, result);
            }
        },
        [ACTIONS.UPDATE_IMAGES] (context, data) {
            return Vue.axios
                .post('/images', data)
                .then(response => {
                    // Do not see the need to update the store at this point. The backend returns the updated
                    // examination, but for now we do nothing with it.
                    return response.data;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        [ACTIONS.SECOND_OPINION] (context, data) {
            return Vue.axios
                .post('/second', data)
                .then(response => {
                    // Do not see the need to update the store at this point. The backend returns the updated
                    // examination, but for now we do nothing with it.
                    return response.data;
                })
                .catch(reason => {
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        [ACTIONS.CHARGE_CARD] ({ commit, state, getters }, data) {
            commit(MUTATIONS.SET_OSI_AMOUNT, data.amount);
            const requestData = {
                deadline: data.deadline,
                amountKey: data.amountKey,
                currency: getters.CURRENCY(),
                amount: data.amount,
                promoCode: data.promoCode,
                examination: state.examination,
                payment_method_id: data.paymentMethod
            };
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/charge', requestData)
                .then(response => {
                    let responseData = response.data;
                    if (responseData.success) {
                        commit(MUTATIONS.SUCCESS, 'CHARGE_CARD');
                        commit(MUTATIONS.FINISH_CHECK);
                        commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                        gtag('event', 'charge_complete', {
                            event_category: 'booking',
                            event_label: 'charge_complete'
                        });
                    }
                    return responseData;
                })
                .catch(reason => {
                    if (reason.response && reason.response.data) {
                        commit(MUTATIONS.ERROR, reason.response.data);
                    } else {
                        commit(MUTATIONS.ERROR, 'CHARGE_CARD');
                    }
                    gtag('event', 'charge_error', {
                        event_category: 'booking',
                        event_label: 'charge_error'
                    });
                    console.log('Request error:', reason.response);
                    throw reason;
                });
        },
        [ACTIONS.RESET] ({ commit }) {
            commit(MUTATIONS.RESET);
            commit(MUTATIONS.LOGOUT);
        },
        [ACTIONS.CONTINUE_TO_NEXT_STEP] ({ commit, dispatch, state }) {
            if (state.newUser) {
                if (!state.uk.patientDetails && !state.uk.medicalIssue && !state.uk.medicalHistory && !state.uk.userAuthentication && !state.uk.payment) {
                    dispatch(ACTIONS.USER_UPDATE);
                } else if (state.uk.patientDetails && !state.uk.medicalIssue && !state.uk.medicalHistory && !state.uk.userAuthentication && !state.uk.payment) {
                    dispatch(ACTIONS.EXAMINATION);
                } else if (state.uk.patientDetails && state.uk.medicalIssue && !state.uk.medicalHistory && !state.uk.userAuthentication && !state.uk.payment) {
                    dispatch(ACTIONS.EXAMINATION_UPDATE);
                } else if (
                    state.uk.patientDetails && state.uk.medicalIssue && state.uk.medicalHistory && !state.uk.userAuthentication && !state.uk.payment) {
                    dispatch(ACTIONS.USER_ADDITIONAL_DATA);
                } else {
                    commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                }
            } else {
                if (!state.uk.medicalIssue && !state.uk.userHistory) {
                    dispatch(ACTIONS.EXAMINATION);
                } else if (state.uk.medicalIssue && !state.uk.userHistory) {
                    dispatch(ACTIONS.EXAMINATION_UPDATE);
                    commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                } else {
                    commit(MUTATIONS.CONTINUE_TO_NEXT_STEP);
                }
            }
        },
        [ACTIONS.PARTNER_OSLO_CHECK] ({ commit, state }) {
            let data = {
                closeup: state.check.answers.closeup,
                overview: state.check.answers.overview
            };
            return Vue.axios
                .post('/user-submit-check', data)
                .then(response => {
                    commit(MUTATIONS.SET_CASE_CODE, response.data.caseCode);
                    return true;
                })
                .catch(reason => {
                    commit(MUTATIONS.ERROR, 'SUBMIT_OSLO_CHECK');
                    throw reason;
                });
        },
        [ACTIONS.LOGIN] ({ state }, data) {
            data['region'] = state.region;
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/login', data)
                .then(() => {
                    return true;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return false;
                });
        },
        async [ACTIONS.PARTNER_LOGIN] ({ commit }, data) {
            return Vue.axios
                .post('/partner-login', data)
                .then(response => {
                    commit(MUTATIONS.UPDATE_LOGIN, response.data);
                    commit(MUTATIONS.SET_OSLO_PARTNER);
                    return true;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return false;
                });
        },
        async [ACTIONS.CHANGE_PASSWORD] (context, data) {
            return Vue.axios
                .post('/change-password', data)
                .then(response => {
                    return response.data;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return {
                        status: false,
                        code: 'error'
                    };
                });
        },
        async [ACTIONS.UPDATE_PASSWORD] (context, data) {
            return Vue.axios
                .post('/update-password', data)
                .then(response => {
                    return response.data;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return {
                        status: false,
                        code: 'error'
                    };
                });
        },
        async [ACTIONS.PHONE_LOGIN_OR_CREATE] ({ commit, state }, data) {
            data = merge({ locale: state.locale, region: state.region }, data);
            return Vue.axios
                .post('/login-or-create', data)
                .then(response => {
                    commit(MUTATIONS.SET_UUID, response.data.uuid);
                    commit(MUTATIONS.SET_PHONE, data.phonenumber);
                    return true;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return false;
                });
        },
        async [ACTIONS.USER_LOGIN] ({ commit, state }, data) {
            data = merge({
                phonenumber: data,
                locale: state.locale,
                region: state.region
            });
            return Vue.axios
                .post('/login-phone', data)
                .then(response => {
                    commit(MUTATIONS.SET_UUID, response.data.uuid);
                    commit(MUTATIONS.SET_PHONE, response.data.phonenumber);
                    commit(MUTATIONS.SET_NEW_USER, response.data.newUser);
                    return true;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return false;
                });
        },
        [ACTIONS.LOGIN_STOREBRAND] ({ commit, state }, data) {
            data = merge({ locale: state.locale, region: state.region }, data);
            return Vue.axios.post('/login-storebrand', data).then(response => {
                let loginObject = {
                    partner: true,
                    token: response.data.token,
                    phonenumber: response.data.phonenumber,
                    policynumber: response.data.policynumber,
                    claim_uuid: response.data.claim_uuid,
                    uuid: response.data.uuid,
                    name: response.data.name
                };
                commit(MUTATIONS.UPDATE_LOGIN, loginObject);
                commit(MUTATIONS.UPDATE_ANSWER, {
                    question: 'who',
                    answer: response.data.child_ssn !== null ? 'other' : 'me'
                });
                commit(MUTATIONS.UPDATE_ANSWER, {
                    question: 'child_ssn',
                    answer: response.data.child_ssn
                });
                commit(MUTATIONS.SHOW_HEADER);
                commit(MUTATIONS.SET_SHOW_HEADER_BACK_BUTTON, true);
                return true;
            });
        },
        [ACTIONS.LOGIN_AURORA] ({ commit, state }, data) {
            data = merge({ locale: state.locale, region: state.region }, data);
            return Vue.axios.post('/login-aurora', data).then(response => {
                if (response.data.phonenumber) {
                    var phonenumber = response.data.phonenumber;
                }
                let loginObject = {
                    auroraPartner: true,
                    token: response.data.token,
                    phonenumber: phonenumber,
                    email: response.data.email
                };
                commit(MUTATIONS.UPDATE_LOGIN, loginObject);
                commit(MUTATIONS.UPDATE_ANSWER, {
                    question: 'who',
                    answer: 'me'
                });
                commit(MUTATIONS.SHOW_HEADER);
                commit(MUTATIONS.SET_SHOW_HEADER_BACK_BUTTON, true);
                return true;
            });
        },
        [ACTIONS.GET_DIAGNOSES] ({ state }) {
            let data = {
                operationId: state.bankid.operationId,
                region: state.region
            };
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/diagnoses', data)
                .then(response => {
                    return response.data;
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return {
                        error: true,
                        message: 'error.message',
                        reason: reason
                    };
                });
        },
        [ACTIONS.UPDATE_ANSWER] ({ commit, state }, data) {
            if (!isUndefined(data.answer)) {
                // Lets commit the answer that was sent to the store.
                commit(MUTATIONS.UPDATE_ANSWER, {
                    question: data.id,
                    answer: null
                });
                commit(MUTATIONS.UPDATE_ANSWER, {
                    question: data.id,
                    answer: data.answer
                });
                if (
                    state.check.answers.medication !== null &&
                    state.check.answers.treatment !== null
                ) {
                    state.justAnswered = true;
                }
            }
        },
        async [ACTIONS.UPDATE_PARTNER] ({ commit, state, dispatch }, data) {
            if (
                !isUndefined(data.answer) &&
                state.partner.answers[data.id] !== data.answer
            ) {
                // we get the OTP key here
                if (data.id === 'key') {
                    let verificationSuccess = await dispatch(
                        ACTIONS.VERIFY,
                        data.answer
                    );
                    if (state.partner.checkFlow) {
                        commit(MUTATIONS.PARTNER_CHECK_COMPLETE);
                    } else {
                        return false; // do not not redirect yet
                    }
                    // if successfully verfied
                    if (verificationSuccess) {
                        commit(MUTATIONS.UPDATE_PARTNER, {
                            question: data.id,
                            answer: data.answer
                        });
                    }
                } else {
                    commit(MUTATIONS.UPDATE_PARTNER, {
                        question: data.id,
                        answer: data.answer
                    });
                }
            }
        },
        async [ACTIONS.UPDATE_PAYMENT] ({ commit, dispatch, state }, data) {
            try {
                const payment = { question: data.id, answer: data.input };
                if (
                    data.input &&
                    has(state.check.answers, data.id) &&
                    state.check.answers[data.id] !== data.input
                ) {
                    // Is the user trying to login by submitting the password?
                    if (data.id === 'password' || data.id === 'confirmPassword') {
                        // Yes, then lets submit the check (which includes username and password)
                        await dispatch(ACTIONS.SUBMIT_CHECK, payment.answer);
                    } else if (data.id === 'key') {
                        await dispatch(ACTIONS.VERIFY, payment.answer);
                    } else if (data.id === 'phone') {
                        await dispatch(
                            ACTIONS.SET_PHONE_NUMBER,
                            payment.answer
                        );
                    }
                    commit(MUTATIONS.UPDATE_ANSWER, payment);
                    commit(MUTATIONS.TOTAL_AMOUNT_POST_PROMO, {
                        totalAmount: data.totalAmount,
                        promoCode: data.promoCode
                    });
                } else {
                    commit(MUTATIONS.UPDATE_ANSWER, { question: data.id, answer: '' });
                    commit(MUTATIONS.UPDATE_ANSWER, payment);
                }
            } catch (error) {
                console.error(error);
                // We do not handle the errors here - we just ignore it..
            }
        },
        [ACTIONS.CONFIRM_QUESTION_PAYMENT] ({ commit, state, getters }, data) {
            const requestData = {
                payment_intent_id: data.payment_intent_id
            };
            // Perform axios request instead of just resolving directly
            return Vue.axios
                .post('/confirm-question-payment', requestData)
                .then(response => {
                    let responseData = response.data;
                    if (responseData.status === 'succeeded') {
                        commit(MUTATIONS.SUCCESS, 'CHARGE_CARD');
                        commit(MUTATIONS.FINISH_CHECK);
                        gtag('event', 'charge_complete', {
                            event_category: 'booking',
                            event_label: 'charge_complete'
                        });
                    }
                    return responseData;
                })
                .catch(reason => {
                    if (reason.response && reason.response.data) {
                        commit(MUTATIONS.ERROR, reason.response.data);
                    } else {
                        commit(MUTATIONS.ERROR, 'CHARGE_CARD');
                    }
                    gtag('event', 'charge_error', {
                        event_category: 'booking',
                        event_label: 'charge_error'
                    });
                    console.log('Request error:', reason);
                    throw reason;
                });
        },
        [ACTIONS.LOGOUT] ({ commit, state }) {
            commit(MUTATIONS.LOGOUT);
        },
        [ACTIONS.PARTNER_AURORA_CHECK] ({ commit, dispatch }, data) {
            return Vue.axios
                .get(
                    process.env.VUE_APP_AURORA_ENDPOINT +
                        '/api/auth/validate-user-token',
                    { headers: { Authorization: data.token } }
                )
                .then(response => {
                    if (response && response.data.email) {
                        commit(MUTATIONS.UPDATE_ANSWER, {
                            question: 'name',
                            answer: response.data.firstName + ' ' + response.data.lastName
                        });
                        dispatch(ACTIONS.LOGIN_AURORA, {
                            email: response.data.email
                        });
                        return response.data;
                    } else {
                        return false;
                    }
                })
                .catch(reason => {
                    console.log('Request error:', reason);
                    return false;
                });
        }
    }
});
