<template>
    <v-container fluid>
        <v-layout row wrap justify-center>
            <v-flex xs12 md6 pa-1>
                <h3 v-if="count!=0"> Remaining count: {{count}} </h3>
                <examination-card v-model="examination" v-if="examination.uuid" view-only></examination-card>
            </v-flex>
            <v-flex xs12 md6 pa-1>
                <diagnosis-card
                    v-if="diagnosis.uuid"
                    v-model="diagnosis"
                    @updatePrivate="updateAndFetchIcd">
                </diagnosis-card>
            </v-flex>
            <v-flex xs12 md6 pa-1 v-if="!diagnosis.uuid">
                <v-card>
                    <v-card-title primary-title>
                        No blank ICD codes found!
                    </v-card-title>
                </v-card>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import store, { SET_SNACKBAR_MESSAGE } from '@/store';

import ExaminationCard from '@/components/ExaminationCard';
import DiagnosisCard from '@/components/DiagnosisCard';

export default {
    data () {
        return {
            examination: {},
            diagnosis: {},
            count: 0
        };
    },
    methods: {
        async updateAndFetchIcd (value) {
            await this.updateIcd(value);
            await this.getFirstNullIcd();
        },
        async getFirstNullIcd () {
            try {
                const response = await this.axios.get('/icd-code');
                if (response.data && response.data.diagnose) {
                    this.examination = response.data.diagnose.examination;
                    this.diagnosis = response.data.diagnose;
                    this.count = response.data.count;
                }
            } catch (error) {
                this.examination = {};
                this.diagnosis = {};
                this.count = 0;
            }
        },
        async updateIcd (value) {
            await this.axios.post(`/diagnosis/update-private-description/${this.diagnosis.uuid}`, { icd_codes: JSON.stringify(value) });
            store.commit(SET_SNACKBAR_MESSAGE, 'Record updated successfully!');
        }
    },
    async mounted () {
        await this.getFirstNullIcd();
    },
    components: {
        ExaminationCard,
        DiagnosisCard
    }
};
</script>

<style lang="scss">
</style>
