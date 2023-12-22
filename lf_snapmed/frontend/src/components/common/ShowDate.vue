<template>
    <div class="date-show">
        {{ date }}
        <button v-on:click="clearDate" class="edit-button">Edit</button>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import moment from 'moment';

moment.locale('no', {
    months: ['januar',
        'februar',
        'mars',
        'april',
        'mai',
        'juni',
        'juli',
        'august',
        'september',
        'oktober',
        'november',
        'desember'
    ]
});

export default {
    name: 'show-date',
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        }),
        ...mapState({
            isPartnerVideoFlow: state => ((state.partner && state.partner.videoFlow) || false),
            region: state => state.region
        })
    },
    data () {
        return {
            date: null };
    },
    mounted () {
        // Set text to answer
        moment.locale(this.region);
        this.date = moment(this.answers['videoSlot']).format('DD.MMMM | [kl] HH.mm');
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        clearDate () {
            this.setAnswer({'answer': null, 'id': 'videoSlot'});
        }
    }

};
</script>
