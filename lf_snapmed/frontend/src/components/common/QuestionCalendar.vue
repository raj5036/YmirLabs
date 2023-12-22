<template>
    <div class="question-dob-dropdown">
        <datepicker
            placeholder="Select Date"
            :monday-first="true"
            :full-month-name="false"
            v-model="date"
            @selected="set"
            format="dsu MMM yyyy"
            initial-view="year"
            :disabled-dates="this.disabledDates"
        />
    </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';
import moment from 'moment';
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'QuestionCalendar',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    components: {
        Datepicker
    },
    data () {
        return {
            date: null,
            disabledDates: {
                from: moment().subtract(16, 'years').toDate()
            }
        };
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        // Set text to answer
        if (this.answers[this.data.id]) {
            let newDate = new Date(this.answers[this.data.id]);
            this.date = newDate;
            let age = moment().diff(newDate, 'years', false);
            this.setAnswer({'answer': age, 'id': 'age'});
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        set (value) {
            let age = moment().diff(value, 'years', false);
            let newDate = moment(value).format('YYYY-MM-DD');
            this.setAnswer({'answer': newDate, 'id': this.data.id});
            this.setAnswer({'answer': age, 'id': 'age'});
        }
    }
};
</script>
