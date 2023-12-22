<template>
    <v-container fluid>
        <v-layout v-if="!canEdit" row wrap>
            <v-flex xs12>
                <v-alert :value="true" type="info">
                    Denne saken er låst av <strong>{{ examination.locked.display_name }}</strong> og kan derfor ikke redigeres.
                </v-alert>
            </v-flex>
        </v-layout>
        <v-layout row wrap>
            <v-flex xs12 md6 pa-1>
                <examination-card v-model="examination" :phonenumber="phonenumber" :ssn="ssn" view-only></examination-card>
            </v-flex>
            <v-flex xs12 md6 pa-1>
                <diagnosis-card
                    v-if="canEdit"
                    v-model="diagnosis"
                    :referrals="referrals"
                    :phonenumber="phonenumber"
                    :is-case-interesting="isCaseInteresting"
                    :second-opinion="secondOpinion"
                    :second-opinion-reason="secondOpinionReason"
                    @submit="submitDiagnosis"
                    @updatePrivate="updatePrivateDescription"
                    @toggleCaseAsInteresting="toggleCaseAsInteresting">
                </diagnosis-card>
                <diagnosis-card
                    v-else
                    v-model="diagnosis"
                    :referrals="referrals"
                    :phonenumber="phonenumber"
                    :ssn="ssn"
                    :is-case-interesting="isCaseInteresting"
                    :second-opinion="secondOpinion"
                    :second-opinion-reason="secondOpinionReason"
                    @toggleCaseAsInteresting="toggleCaseAsInteresting"
                    view-only>
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
import { isEmpty, head, size } from 'lodash';
import { mapState } from 'vuex';

import ExaminationCard from '@/components/ExaminationCard';
import DiagnosisCard from '@/components/DiagnosisCard';

export default {
    props: {
        examination: {
            type: Object,
            required: true
        },
        ssn: {
            type: String
        }
    },
    async beforeRouteLeave (to, from, next) {
        try {
            await this.axios.put(`/examination/${this.examination.uuid}/unlock`);
        } catch (error) {
            window.alert('Unable to unlock case');
            console.log(error);
        } finally {
            next();
        }
    },
    data () {
        return {
            isCaseInteresting: true,
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            }
        };
    },
    computed: {
        ...mapState({ currentUserUUID: state => state.currentUserUUID }),
        phonenumber () {
            return this.examination.client ? this.examination.client.phonenumber : false;
        },
        canEdit () {
            return this.examination.can_edit;
        },
        referrals () {
            return this.examination.referrals;
        },
        diagnosis () {
            if (this.examination.diagnoses && !isEmpty(this.examination.diagnoses)) {
                return head(this.examination.diagnoses);
            }
            return {};
        },
        secondOpinion () {
            return this.examination.diagnoses && size(this.examination.diagnoses) > 1;
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
        async toggleCaseAsInteresting () {
            try {
                this.isCaseInteresting = !this.isCaseInteresting;
                await this.axios.post(`/interesting/${this.examination.uuid}`, { has_interest: this.isCaseInteresting });
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
