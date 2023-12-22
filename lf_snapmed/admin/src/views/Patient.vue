<template>
    <v-container fluid align-center align-content-center text-xs-center class="container--patient">
        <v-layout row wrap justify-center v-if="patient">
            <v-flex xs12 md8>
                <v-card>
                    <v-card-title primary-title>
                        {{ patient.user.firstname}} {{ patient.user.lastname }} <span v-if="ssn">({{ ssn }})</span>
                    </v-card-title>
                    <v-data-table :headers="headers" :items="patient.cases" hide-actions>
                        <template slot="items" slot-scope="props">
                            <tr @click="navigateTo(props.item)" class="view-case">
                                <td class="text-xs-left">{{ $t(`examination.category.${props.item.category}`) }}</td>
                                <td class="text-xs-left">{{ props.item.deadline_time }}</td>
                                <td class="text-xs-left" v-if="props.item.category === 'video'">N/A</td>
                                <td class="text-xs-left" v-else>{{ props.item.age }}</td>
                                <td class="text-xs-left" v-if="props.item.category === 'video'">N/A</td>
                                <td class="text-xs-left" v-else>{{ genderLabel(props.item.gender) }}</td>
                                <td class="text-xs-left">
                                    <v-btn icon class="mx-0">
                                        <v-icon v-if="props.item.can_edit">edit</v-icon>
                                        <v-icon v-else>remove_red_eye</v-icon>
                                    </v-btn>
                                </td>
                            </tr>
                        </template>
                    </v-data-table>
                </v-card>
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

export default {
    name: 'patient',
    props: {
        uuid: {
            type: String
        },
        patientData: {
            type: Object
        }
    },
    data () {
        return {
            ssn: null,
            loading: true,
            patient: null,
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            },
            headers: [
                {
                    text: this.$t('assignment.category'),
                    value: 'category',
                    sortable: false
                },
                {
                    text: this.$t('examination.deadline.label'),
                    value: 'deadline_time',
                    sortable: false
                },
                {
                    text: this.$t('examination.age.label'),
                    value: 'age',
                    sortable: false
                },
                {
                    text: this.$t('examination.gender.label'),
                    value: 'gender',
                    sortable: false
                },
                {
                    text: this.$t('patient.gotocase'),
                    sortable: false
                }
            ]
        };
    },
    methods: {
        async navigateTo (examination) {
            const lockResponse = await Vue.axios.post('/lock', {
                examination: examination.uuid
            });
            if (lockResponse.data &&
                (lockResponse.data.status === 'ok' || lockResponse.data.status === 'alreadylocked')) {
                this.$router.push({ name: 'case', params: { examination: examination, ssn: this.ssn } });
            }
        },
        async init () {
            const response = await Vue.axios.get('/patient/' + this.$route.params.uuid);
            if (response) {
                this.patient = response.data;
            } else {
                this.patient = null;
            }
        },
        genderLabel (gender) {
            if (gender !== '') {
                return this.$t('examination.gender.' + gender);
            } else {
                return '';
            }
        }
    },
    async mounted () {
        this.ssn = sessionStorage.getItem('ssn');
        if (this.patientData) {
            this.patient = this.patientData;
        } else {
            await this.init();
        }
        this.loading = false;
    }
};
</script>
