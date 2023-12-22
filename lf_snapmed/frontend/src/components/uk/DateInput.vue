<template>
    <div>
        <input
            type=text
            class="dd-input"
            placeholder="DD-MM-YYYY"
            ref="date_input"
            v-model="text"
            v-on:keyup="setValue"
            :aria-invalid="warning? true: false"
        />
         <div v-if="warning !== null" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{ $t('questions.age.error.' + this.warning)}}</span>
        </div>
        <button v-if="showProceed" @click="set" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
    </div>
</template>

<script>
import moment from 'moment';
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import { isFinite, isUndefined } from 'lodash';
import Inputmask from 'inputmask';

const ageLimits = {
    adult: {
        min: 17,
        max: 100
    },
    minor: {
        min: 0,
        max: 16
    }
};

export default {
    name: 'DateInput',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            disabledDates: {
                from: moment().subtract(16, 'years').toDate()
            },
            warning: null,
            showProceed: false,
            text: '',
            age: ''
        };
    },
    computed: {
        ...mapState(['region']),
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        // Set text to answer
        var im = new Inputmask({mask: '99-99-9999',
            showMaskOnHover: false,
            showMaskOnFocus: false });
        im.mask(this.$refs.date_input);

        if (this.answers[this.data.id]) {
            let newDate = moment(this.answers[this.data.id], 'YYYY-MM-DD').format('DD-MM-YYYY');
            this.text = newDate;
            this.showProceed = true;
        }
    },
    methods: {
        clearWarning () {
            this.warning = null;
        },
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        checkAge (age) {
            if (this.answers['who'] === 'other') {
                if (!isUndefined(age) && isFinite(age) && age >= ageLimits.minor.min && age <= ageLimits.minor.max) {
                    this.showProceed = true;
                    this.clearWarning();
                } else {
                    this.warning = 'minor_age_limit';
                }
            } else {
                if (!isUndefined(age) && isFinite(age) && age >= ageLimits.adult.min && age <= ageLimits.adult.max) {
                    this.showProceed = true;
                    this.clearWarning();
                } else {
                    this.warning = 'major_age_limit';
                }
            }
        },
        setValue (e) {
            this.showProceed = false;
            this.text = e.target.value;
            var dateRegex = /^(0?[1-9]|[12][0-9]|3[01])[/-](0?[1-9]|1[012])[/-]\d{4}$/;
            if (this.text.length === 10 && dateRegex.test(this.text)) {
                const value = moment(this.text, 'DD-MM-YYYY');
                this.age = moment().diff(value, 'years', false);
                this.checkAge(this.age);
            } else if (this.text.replace(/[_-]/g, '').length === 8 && !dateRegex.test(this.text)) {
                this.warning = 'incorrect_format';
            } else {
                this.clearWarning();
            }
        },
        set () {
            const value = moment(this.text, 'DD-MM-YYYY');
            let newDate = moment(value).format('YYYY-MM-DD');
            this.setAnswer({'answer': newDate, 'id': this.data.id});
            this.setAnswer({'answer': this.age, 'id': 'age'});
        }
    }
};
</script>
