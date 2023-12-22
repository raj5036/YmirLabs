import Vue from 'vue';
import _ from 'lodash';
import moment from 'moment';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Private constants
const SET_PAYMENT_SUBMITTED = 'SET_PAYMENT_SUBMITTED';
const SET_PAYMENT_CHARGED = 'SET_PAYMENT_CHARGED';
const SET_PAYMENT_ERROR = 'SET_PAYMENT_ERROR';

const GOTO_NEXT_WEEK = 'GOTO_NEXT_WEEK';
const GOTO_PREV_WEEK = 'GOTO_PREV_WEEK';

const SET_AVAILABLE_APPOINTMENTS = 'SET_AVAILABLE_APPOINTMENTS';
const GET_AVAILABLE_APPOINTMENTS = 'GET_AVAILABLE_APPOINTMENTS';
const SELECT_APPOINTMENT = 'SELECT_APPOINTMENT';
const GET_SELECTED_APPOINTMENT = 'GET_SELECTED_APPOINTMENT';
const GET_PRICE = 'GET_PRICE';
const GET_CURRENCY = 'GET_CURRENCY';
const GET_LOCALE = 'GET_LOCALE';
const GET_REGION = 'GET_REGION';
const IS_FIRST_WEEK_SELECTED = 'IS_FIRST_WEEK_SELECTED';
const HAS_PAYMENT_ERROR = 'HAS_PAYMENT_ERROR';
const CONFIRM_APPOINTMENT_AND_CHARGE = 'CONFIRM_APPOINTMENT_AND_CHARGE';
const CONFIRM_PAYMENT = 'CONFIRM_PAYMENT';

const CHECK_PROMOCODE = 'CHECK_PROMOCODE';

const PARTNER_BOOK_VIDEO = 'PARTNER_BOOK_VIDEO';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
const state = () => {
    return {
        selectedWeek: false,
        selectedYear: false,
        payment: {
            error: false,
            state: false
        },
        weeks: [],
        currency: process.env.VUE_APP_CURRENCY,
        locale: process.env.VUE_APP_I18N_LOCALE,
        region: process.env.VUE_APP_SITE
    };
};
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
const getters = {
    [GET_SELECTED_APPOINTMENT] (state) {
        return _.chain(state.weeks)
            .flatMap(week => week.days)
            .flatMap(day => day.slots)
            .find({selected: true})
            .value() || false;
    },
    [GET_CURRENCY] (state) {
        return state.currency;
    },
    [GET_LOCALE] (state) {
        return state.locale;
    },
    [GET_REGION] (state) {
        return state.region;
    },
    [GET_PRICE] (state, getters) {
        const CURRENCY = getters.GET_CURRENCY;
        const VIDEO_AMOUNT_KEY = `VUE_APP_AMOUNT_VIDEO_${CURRENCY}`;
        return process.env[VIDEO_AMOUNT_KEY];
    },
    [HAS_PAYMENT_ERROR] (state) {
        return (state.payment && state.payment.error) || false;
    },
    [IS_FIRST_WEEK_SELECTED] (state) {
        return _.findIndex(state.weeks, {week: state.selectedWeek}) === 0;
    }
};
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
const mutations = {
    [SET_AVAILABLE_APPOINTMENTS] (state, payload) {
        if (!_.find(state.weeks, {week: payload.week})) {
            const weekSlots = _.chain(payload.slots)
                .groupBy(item => moment(item.slot.timestamp).format('YYYY.MM.DD'))
                .mapValues(slots => {
                    return _.map(slots, value => {
                        return _.merge({}, value.slot, {
                            id: value.slot.timestamp,
                            startTime: moment(value.slot.timestamp).format('HH:mm'),
                            endTime: moment(value.slot.timestamp_end).format('HH:mm')
                        });
                    });
                })
                .value();
            let activeDate = moment(payload.from_date);
            const toDate = moment(payload.to_date);
            let days = [];
            while (activeDate.isSameOrBefore(toDate)) {
                const key = activeDate.format('YYYY.MM.DD');
                days.push({
                    date: key,
                    month: activeDate.format('MMM'),
                    dayOfMonth: activeDate.date(),
                    dayOfWeek: activeDate.format('dddd'),
                    slots: weekSlots[key] || []
                });
                activeDate.add(1, 'days');
            }
            // Do we already have an instance of that week?
            if (_.findIndex(state.weeks, {week: +payload.week}) === -1) {
                // Add the new week to the current array of weeks in our list.
                state.weeks.push({
                    week: +payload.week,
                    year: +payload.year,
                    fromDate: payload.from_date,
                    toDate: payload.to_date,
                    days
                });
            }
        }
    },
    [SELECT_APPOINTMENT] (state, appointment) {
        state.weeks = _.map(state.weeks, week => {
            return {
                week: week.week,
                year: week.year,
                fromDate: week.from_date,
                toDate: week.to_date,
                days: _.map(week.days, day => {
                    day.slots = _.map(day.slots, slot => {
                        slot.selected = (appointment.id === slot.id && appointment.selected);
                        return slot;
                    });
                    return day;
                })
            };
        });
    },
    SET_PAYMENT_SUBMITTED (state) {
        Vue.set(state.payment, 'state', 'SUBMITTED');
        Vue.set(state.payment, 'error', false);
    },
    SET_PAYMENT_CHARGED (state) {
        Vue.set(state.payment, 'state', 'CHARGED');
        Vue.set(state.payment, 'error', false);
    },
    SET_PAYMENT_ERROR (state) {
        console.log('SET_PAYMENT_ERROR called', state);
        Vue.set(state.payment, 'error', true);
        Vue.set(state.payment, 'state', 'ERROR');
    },
    GOTO_NEXT_WEEK (state) {
        // Is there selected a week yet?
        if (!state.selectedWeek) {
            // No, then lets go to the first week we find.
            const week = _.head(state.weeks);
            if (week) {
                state.selectedWeek = week.week;
                state.selectedYear = week.year;
            }
        } else {
            // Yes, then lets find that week and move forward one week.
            let newWeekIdx = _.findIndex(state.weeks, {week: state.selectedWeek}) + 1;
            if (newWeekIdx > 0) {
                if (newWeekIdx >= _.size(state.weeks)) {
                    newWeekIdx = _.size(state.weeks) - 1;
                }
                state.selectedWeek = state.weeks[newWeekIdx].week;
                state.selectedYear = state.weeks[newWeekIdx].year;
            }
        }
    },
    GOTO_PREV_WEEK (state) {
        // There must already be selected an existing week before doing anything with this.
        if (state.selectedWeek) {
            const weekIdx = _.findIndex(state.weeks, {week: state.selectedWeek});
            if (weekIdx > 0) {
                state.selectedWeek = state.weeks[(weekIdx - 1)].week;
                state.selectedYear = state.weeks[(weekIdx - 1)].year;
            }
        }
    }
};
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
const actions = {
    async [GET_AVAILABLE_APPOINTMENTS] ({dispatch, commit, state}, payload) {
        try {
            const {year, week} = payload || {week: '', year: ''};
            const params = /\d{1,2}/.test(week) ? `/${year}/${week}` : '';
            const response = await Vue.axios.get('/video/slots' + params, {
                params: {
                    region: process.env.VUE_APP_SITE
                }
            });
            commit(SET_AVAILABLE_APPOINTMENTS, response.data);
            // If we have not set the selected week yet, lets set it to the first week.
            if (!state.selectedWeek) {
                dispatch(GOTO_NEXT_WEEK);
            }
            return true;
        } catch (reason) {
            console.log('Request error:', reason);
            throw reason;
        };
    },
    [SELECT_APPOINTMENT] ({commit, state, dispatch}, payload) {
        commit(SELECT_APPOINTMENT, payload);
        return Promise.resolve(true);
    },
    [CONFIRM_PAYMENT] ({commit}, payload) {
        // Perform axios request to confirm charge
        commit(SET_PAYMENT_SUBMITTED);
        return Vue.axios.post('/video/confirm-payment', payload)
            .then(response => {
                commit(SET_PAYMENT_CHARGED);
                gtag('event', 'confirm_video_payment', {'event_category': 'booking_video', 'event_label': 'confirm_video_payment'});
                return response.data;
            })
            .catch(reason => {
                commit(SET_PAYMENT_ERROR, (reason.response && reason.response.data) || false);
                gtag('event', 'charge_error_video', {'event_category': 'booking_video', 'event_label': 'charge_error_video'});
                throw reason;
            });
    },
    [PARTNER_BOOK_VIDEO] ({getters, state, rootState}, description) {
        const booking = {
            appointment: getters.GET_SELECTED_APPOINTMENT,
            description: description,
            claim_uuid: rootState.partner.claim_uuid,
            region: getters.GET_REGION
        };
        // Perform axios request to book video session
        return Vue.axios.post('/user-book-video', booking)
            .then(response => {
                gtag('event', 'confirm_video_booking', {'event_category': 'booking_video', 'event_label': 'confirm_video_booking'});
                return response.data;
            })
            .catch(reason => {
                console.log('Request error:', reason);
                gtag('event', 'charge_error_video', {'event_category': 'booking_video', 'event_label': 'charge_error_video'});
                throw reason;
            });
    },
    [CONFIRM_APPOINTMENT_AND_CHARGE] ({commit, getters}, payload) {
        const booking = {
            appointment: getters.GET_SELECTED_APPOINTMENT,
            client: payload,
            region: getters.GET_REGION
        };
        // Perform axios request to charge for video appointment
        commit(SET_PAYMENT_SUBMITTED);
        return Vue.axios.post('/video/confirm-charge', booking)
            .then(response => {
                commit(SET_PAYMENT_CHARGED);
                gtag('event', 'charge_complete_video', {'event_category': 'booking_video', 'event_label': 'charge_complete_video'});
                return response.data;
            })
            .catch(reason => {
                commit(SET_PAYMENT_ERROR, (reason.response && reason.response.data) || false);
                gtag('event', 'charge_error_video', {'event_category': 'booking_video', 'event_label': 'charge_error_video'});
                console.log('Request error:', reason);
                throw reason;
            });
    },
    [GOTO_NEXT_WEEK] ({commit, dispatch, state}) {
        commit(GOTO_NEXT_WEEK);
        const nextWeek = moment(state.selectedYear + '-' + state.selectedWeek, 'YYYY-w').add(1, 'weeks');
        // Do we already have one instance of the week?
        if (_.findIndex(state.weeks, {week: nextWeek.week()}) === -1) {
            // No, so lets go get the week..
            dispatch(GET_AVAILABLE_APPOINTMENTS, {week: nextWeek.week(), year: nextWeek.year()});
        }
        return Promise.resolve(true);
    },
    [GOTO_PREV_WEEK] ({commit}) {
        commit(GOTO_PREV_WEEK);
        return Promise.resolve(true);
    },
    [CHECK_PROMOCODE] ({commit}, {promoCode, type, currency}) {
        // Perform axios request instead of just resolving directly
        const data = {
            'promoCode': promoCode,
            'type': type,
            'currency': currency
        };
        return Vue.axios.post('/video/checkpromo', data)
            .then(response => {
                // commit(MUTATIONS.UPDATE_LOGIN, response.data);
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
    }
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Exports

export const GETTERS = {
    [GET_SELECTED_APPOINTMENT]: `video/${GET_SELECTED_APPOINTMENT}`,
    [GET_PRICE]: `video/${GET_PRICE}`,
    [GET_CURRENCY]: `video/${GET_CURRENCY}`,
    [GET_LOCALE]: `video/${GET_LOCALE}`,
    [GET_REGION]: `video/${GET_REGION}`,
    [IS_FIRST_WEEK_SELECTED]: `video/${IS_FIRST_WEEK_SELECTED}`,
    [HAS_PAYMENT_ERROR]: `video/${HAS_PAYMENT_ERROR}`
};

export const ACTIONS = {
    [GET_AVAILABLE_APPOINTMENTS]: `video/${GET_AVAILABLE_APPOINTMENTS}`,
    [SELECT_APPOINTMENT]: `video/${SELECT_APPOINTMENT}`,
    [CONFIRM_APPOINTMENT_AND_CHARGE]: `video/${CONFIRM_APPOINTMENT_AND_CHARGE}`,
    [CONFIRM_PAYMENT]: `video/${CONFIRM_PAYMENT}`,
    [GOTO_NEXT_WEEK]: `video/${GOTO_NEXT_WEEK}`,
    [GOTO_PREV_WEEK]: `video/${GOTO_PREV_WEEK}`,
    [CHECK_PROMOCODE]: `video/${CHECK_PROMOCODE}`,
    [PARTNER_BOOK_VIDEO]: `video/${PARTNER_BOOK_VIDEO}`
};
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
