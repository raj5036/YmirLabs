<template>
    <v-container fluid>
        <v-layout row justify-center>
            <v-flex xs12 sm10 md8>
                <v-card>
                    <v-card-title primary-title>
                        {{$t('title.partner_select')}}
                    </v-card-title>
                    <v-layout row wrap justify-right>
                        <v-radio-group row v-model="partner" class="mx-4">
                            <v-radio
                                label="Storebrand"
                                value="Storebrand">
                            </v-radio>
                            <v-radio
                                label="Oslo"
                                value="Oslo">
                            </v-radio>
                        </v-radio-group>
                    </v-layout>
                    <v-card-title primary-title>
                        {{$t('title.to_from_dates')}}
                    </v-card-title>
                    <v-layout row wrap justify-space-around>
                        <v-flex xs10 sm5 md5>
                            <v-menu
                                ref="from"
                                :close-on-content-click="false"
                                v-model="from"
                                :nudge-right="40"
                                :return-value.sync="fromDate"
                                lazy
                                transition="scale-transition"
                                offset-y
                                full-width
                                min-width="290px"
                                >
                                <v-text-field
                                slot="activator"
                                v-model="fromDate"
                                label="From"
                                prepend-icon="event"
                                readonly
                                ></v-text-field>
                                <v-date-picker v-model="fromDate" @input="$refs.from.save(fromDate)"></v-date-picker>
                            </v-menu>
                        </v-flex>
                        <v-flex xs10 sm5 md5>
                            <v-menu
                                ref="to"
                                :close-on-content-click="false"
                                v-model="to"
                                :nudge-right="40"
                                :return-value.sync="toDate"
                                lazy
                                transition="scale-transition"
                                offset-y
                                full-width
                                min-width="290px"
                                >
                                <v-text-field
                                slot="activator"
                                v-model="toDate"
                                label="To"
                                prepend-icon="event"
                                readonly
                                ></v-text-field>
                                <v-date-picker v-model="toDate" @input="$refs.to.save(toDate)" :min="minDate"></v-date-picker>
                            </v-menu>
                        </v-flex>
                    </v-layout>
                    <v-card-actions class="justify-center">
                        <v-btn
                            :disabled="loading"
                            :loading="loading"
                            @click.native.stop="download"
                            color="primary">
                            {{$t('action.download')}}
                        </v-btn>
                    </v-card-actions>
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
    name: 'partners',
    data () {
        return {
            partner: 'Storebrand',
            from: false,
            to: false,
            fromDate: null,
            toDate: null,
            loading: false,
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            }
        };
    },
    computed: {
        minDate () {
            return this.fromDate;
        }
    },
    methods: {
        async download () {
            if (!this.fromDate || !this.toDate) {
                this.snackbar.show = true;
                this.snackbar.text = 'Please select From date and To date';
                return;
            }

            this.loading = true;

            let data = {
                partner: this.partner,
                from: this.fromDate,
                to: this.toDate
            };
            await Vue.axios.post('/examination/export', data, { responseType: 'arraybuffer' }).then(response => {
                if (response) {
                    this.snackbar.show = true;
                    this.snackbar.text = 'Exported Successfully!';

                    // It is necessary to create a new blob object with mime-type explicitly set
                    // otherwise only Chrome works like it should
                    let newBlob = new Blob([response.data], { type: response.headers['content-type'] });

                    // IE doesn't allow using a blob object directly as link href
                    // instead it is necessary to use msSaveOrOpenBlob
                    if (window.navigator && window.navigator.msSaveOrOpenBlob) {
                        window.navigator.msSaveOrOpenBlob(newBlob);
                        return;
                    }

                    // For other browsers:
                    // Create a link pointing to the ObjectURL containing the blob.
                    const data = window.URL.createObjectURL(newBlob);
                    let link = document.createElement('a');
                    link.href = data;
                    link.download = 'export.xlsx';
                    link.click();
                    setTimeout(function () {
                    // For Firefox it is necessary to delay revoking the ObjectURL
                        window.URL.revokeObjectURL(data);
                    }, 100);
                } else {
                    this.snackbar.show = true;
                    this.snackbar.text = 'Something went wrong.';
                }
            }).catch(reason => {
                this.snackbar.show = true;
                this.snackbar.text = 'Could not download report.';
                console.log(reason);
            });

            this.loading = false;
        }
    }
};
</script>

<style lang="scss">
.v-date-picker-table .v-btn {
    font-size: 12px !important;
    font-weight: 500 !important;
    height: 32px !important;
    border-radius: 50% !important;
    text-transform: uppercase !important;
}

</style>
