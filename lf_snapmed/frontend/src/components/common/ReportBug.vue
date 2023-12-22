<template>
    <div class="bug-report">
        <button @click="openModal">Report</button>
        <div v-if="modalOpen" class="overlay">
            <div class="modal">
            <span class="cross-button" @click="closeModal">x</span>
                <div v-if="returnedUuid">
                    <span>uuid: </span> <span>{{ returnedUuid }}</span>
                </div>

                <div v-else>
                <div class="label-input">
                <label>Tell us what you liked, report a problem, or make suggestion, we are listening</label>
                <input placeholder="add details"  v-model="details" type="text"/>
                </div>
                <button @click="set">Send</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'ReportBug',
    data () {
        return {
            modalOpen: false,
            returnedUuid: null,
            details: null
        };
    },
    mounted () {
        // Set text to answer
        this.text = this.answers[this.data.id];
    },
    methods: {
        ...mapActions({
            reportBug: ACTIONS.REPORT_BUG
        }),
        async set () {
            try {
                let bugReport = {};
                this.details && (bugReport['details'] = this.details);
                const response = await this.reportBug(bugReport);
                this.returnedUuid = response.uuid;
                // eslint-disable-next-line no-undef
                this.returnedUuid && posthog.identify(this.returnedUuid);
            } catch (err) {
                console.log('err', err);
            }
        },
        openModal () {
            this.modalOpen = true;
        },
        closeModal () {
            this.modalOpen = false;

            this.returnedUuid = null;
            this.details = null;
        }
    }
};
</script>
