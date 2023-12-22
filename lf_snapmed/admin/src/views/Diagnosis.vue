<template>
    <v-container fluid>
        <v-layout row wrap>
            <v-flex xs12 md6 pa-1>
                <examination-card v-model="examination" :phonenumber="client" :ssn="clientSsn" v-if="examination.uuid" @requestNewImages="requestNewImages"></examination-card>
            </v-flex>
            <v-flex xs12 md6 pa-1>
                <diagnosis-card
                    v-model="diagnosis"
                    :is-case-interesting="isCaseInteresting"
                    :second-opinion="secondOpinion"
                    :second-opinion-reason="secondOpinionReason"
                    @submit="submitDiagnosis"
                    @updatePrivate="updatePrivateDescription"
                    @toggleCaseAsInteresting="toggleCaseAsInteresting">
                </diagnosis-card>
            </v-flex>
        </v-layout>
        <v-snackbar :timeout="snackbar.timeout" top multi-line v-model="snackbar.show">
            {{ snackbar.text }}
            <v-btn flat @click.native="snackbar.show = false">Close</v-btn>
        </v-snackbar>
    </v-container>
</template>

<script>
import Vue from 'vue';
import { isEmpty, head } from 'lodash';

import ExaminationCard from '@/components/ExaminationCard';
import DiagnosisCard from '@/components/DiagnosisCard';

export default {
    async beforeRouteEnter (to, from, next) {
        try {
            const response = await Vue.axios.get('/next');
            if (response.data && response.data.examination) {
                const interestResponse = await Vue.axios.get(`/interesting/${response.data.examination.uuid}`);
                next(vm => {
                    vm.client = response.data.client;
                    vm.clientSsn = response.data.client_ssn;
                    vm.examination = response.data.examination;
                    vm.isCaseInteresting = !!interestResponse.data;
                });
            } else {
                window.alert('Unable to get the next case for you!');
                next(false);
            }
        } catch (error) {
            if (error && error.response && error.response.status === 401) {
                window.alert('Your login has timed out. Please log out and log in again to renew your session.');
            } else {
                window.alert('Unable to get the next case for you!');
            }
            console.log(error.response);
            next(false);
        }
    },
    async beforeRouteUpdate (to, from, next) {
        try {
            const response = await Vue.axios.get('/next');
            this.client = response.data.client;
            this.clientSsn = response.data.client_ssn;
            this.examination = response.data.examination;
            if (this.examination) {
                next();
            } else {
                window.alert('Unable to get the next case for you!');
                next(false);
            }
        } catch (error) {
            window.alert('Unable to get the next case for you!');
            console.log(error);
            next('/');
        }
    },
    async beforeRouteLeave (to, from, next) {
        try {
            await Vue.axios.put(`/examination/${this.examination.uuid}/unlock`);
        } catch (error) {
            window.alert('Unable to unlock case');
            console.log(error);
        } finally {
            next();
        }
    },
    data () {
        return {
            client: '',
            clientSsn: '',
            examination: {},
            diagnosis: {},
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            },
            isCaseInteresting: false
        };
    },
    computed: {
        secondOpinion () {
            return this.examination.diagnoses && !isEmpty(this.examination.diagnoses);
        },
        secondOpinionReason () {
            if (this.secondOpinion) {
                return head(this.examination.diagnoses).second_opinion_reason;
            }
            return '';
        }
    },
    methods: {
        async updatePrivateDescription (value) {
            try {
                await this.axios.post(`/diagnosis/update-private-description/${this.diagnosis.uuid}`, { icd_codes: JSON.stringify(value) });
                this.snackbar.text = this.$t('diagnosis.description_private_upated');
                this.snackbar.show = true;
            } catch (error) {
                this.snackbar.text = error.message;
                this.snackbar.show = true;
            }
        },
        async submitDiagnosis () {
            try {
                if ((this.diagnosis.description === undefined || this.diagnosis.description.length === 0) &&
                    (this.diagnosis.icd_codes === undefined || this.diagnosis.icd_codes.length === 0)) {
                    this.snackbar.text = 'Both apreciation and private description cannot be blank.';
                    this.snackbar.show = true;
                    return;
                }
                let formData = new FormData();
                formData.append('description', this.diagnosis.description);
                formData.append('icd_codes', JSON.stringify(this.diagnosis.icd_codes));
                formData.append('is_prescribed', !!this.diagnosis.is_prescribed);

                if (this.diagnosis.referrals) {
                    if (this.diagnosis.referrals.length > 3) {
                        this.snackbar.text = 'Maximum three files allowed.';
                        this.snackbar.show = true;
                        return;
                    }
                    // Check if file is greater then 10 MB individually.
                    for (var i = 0; i < this.diagnosis.referrals.length; i++) {
                        let file = this.diagnosis.referrals[i];
                        if (file.size / 1024 / 1024 > 10) {
                            this.snackbar.text = 'File should not be greater then 10 MB.';
                            this.snackbar.show = true;
                            return;
                        }
                        formData.append('referrals[]', file);
                    }
                }
                await this.axios.post(`/diagnosis/${this.examination.uuid}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
                this.$router.push('/');
            } catch (error) {
                this.snackbar.text = error.message;
                this.snackbar.show = true;
            }
        },
        async requestNewImages (reason) {
            try {
                console.log(reason);
                await this.axios.post(`/examination/${this.examination.uuid}/newimages`, { reason: reason });
                this.$router.push('/');
            } catch (error) {
                this.snackbar.text = error.message;
                this.snackbar.show = true;
            }
        },
        async toggleCaseAsInteresting () {
            try {
                this.isCaseInteresting = !this.isCaseInteresting;
                await this.axios.post(`/interesting/${this.examination.uuid}`, { has_interest: this.isCaseInteresting });
            } catch (error) {
                this.snackbar.text = error.message;
                this.snackbar.show = true;
            }
        }
    },
    components: {
        ExaminationCard,
        DiagnosisCard
    }
};
</script>

<style lang="scss">
</style>
