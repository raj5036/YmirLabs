<template>
    <div class="page-video__content--book">
        <transition name="fade" appear v-if="!loading">
            <div v-if="!availableHours" class="page-content-loader">
                <svg-loader></svg-loader>
                <span v-t="'page.video.book.loading'"></span>
            </div>
            <select-appointment
                v-if="selectedWeek"
                :value="availableHours"
                :selected-week="selectedWeek"
                :selected-year="selectedYear"
                :is-first-week="isFirstWeek"
                :appointment=selectedAppointment
                @selected="onAppointmentSelected"
                @gotoNextWeek="onGotoNextWeek"
                @gotoPrevWeek="onGotoPrevWeek">
            </select-appointment>
        </transition>
        <div v-else>
            <svg-loader/>
        </div>
    <button @click="set" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
    </div>
</template>

<script>
import { mapActions, mapState, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store/video';
import { ACTIONS as STORE_ACTIONS } from '@/store';
import SelectAppointment from '@common/SelectAppointment';
import SvgLoader from './SvgLoader.vue';

export default {
    name: 'VideoBook',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {loading: false};
    },
    computed: {
        ...mapState({
            availableHours: state => state.video.weeks,
            selectedWeek: state => state.video.selectedWeek,
            selectedYear: state => state.video.selectedYear,
            isPartnerFlowActive: state => state.partner.active,
            region: state => state.region
        }),
        ...mapGetters({
            selectedAppointment: GETTERS.GET_SELECTED_APPOINTMENT,
            isFirstWeek: GETTERS.IS_FIRST_WEEK_SELECTED,
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        this.loading = true;
        this.getAvailableAppointments().then(() => { this.loading = false; });
        this.appointment = this.selectedAppointment;
    },
    methods: {
        ...mapActions({
            getAvailableAppointments: ACTIONS.GET_AVAILABLE_APPOINTMENTS,
            selectAppointment: ACTIONS.SELECT_APPOINTMENT,
            onGotoNextWeek: ACTIONS.GOTO_NEXT_WEEK,
            onGotoPrevWeek: ACTIONS.GOTO_PREV_WEEK,
            setAnswer: STORE_ACTIONS.UPDATE_ANSWER
        }),
        onAppointmentSelected (appointment) {
            this.selectedTime = appointment.selected;
            this.appointment = appointment;
            this.selectAppointment(appointment);
        },
        set () {
            this.setAnswer({'answer': this.appointment.timestamp, 'id': this.data.id});
        }
    },
    components: {
        SelectAppointment,
        SvgLoader
    }
};
</script>
