<template>
    <div class="page-video__content--book">
        <transition name="fade" appear>
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
                @selected="onAppointmentSelected"
                @gotoNextWeek="onGotoNextWeek"
                @gotoPrevWeek="onGotoPrevWeek">
            </select-appointment>
        </transition>
    </div>
</template>

<script>
import { mapActions, mapState, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store/video';
import { ACTIONS as STORE_ACTIONS } from '@/store';
import SelectAppointment from '@common/SelectAppointment';

export default {
    name: 'VideoBook',
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
            isFirstWeek: GETTERS.IS_FIRST_WEEK_SELECTED
        })
    },
    mounted () {
        this.getAvailableAppointments('hii', 'hi');
    },
    methods: {
        ...mapActions({
            getAvailableAppointments: ACTIONS.GET_AVAILABLE_APPOINTMENTS,
            selectAppointment: ACTIONS.SELECT_APPOINTMENT,
            onGotoNextWeek: ACTIONS.GOTO_NEXT_WEEK,
            onGotoPrevWeek: ACTIONS.GOTO_PREV_WEEK,
            partnerContinue: STORE_ACTIONS.PARTNER_CONTINUE
        }),
        onAppointmentSelected (appointment) {
            this.selectedTime = appointment.selected;
            this.selectAppointment(appointment);
            if (appointment.selected) {
                if (this.isPartnerFlowActive) {
                    this.partnerContinue();
                    this.$router.push('/video/confirm-partner');
                } else {
                    if (this.region === 'no' || this.region === 'se') {
                        this.$router.push({ path: '/video/login', query: { loginType: 'bankid' } }); // we want to do BankID login for Norwegian and swedish users
                    } else if (this.region === 'uk' || this.region === 'de') {
                        this.$router.push({ path: '/video/login', query: { loginType: 'phone' } }); // Login via phone for UK and German users
                    } else {
                        this.$router.push('/video/confirm');
                    }
                }
            }
        }
    },
    components: {
        SelectAppointment
    }
};
</script>
