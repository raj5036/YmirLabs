<template>
    <v-container fluid align-center align-content-center text-xs-center class="container--search">
        <v-layout row wrap justify-center>
            <v-flex xs12 md8>
                <v-card>
                    <v-card-title primary-title>
                    <v-radio-group row v-model="searchBy">
                        <v-radio
                            v-bind:label="$t('search.user')"
                            value="user">
                        </v-radio>
                        <v-radio
                            v-bind:label="$t('search.case')"
                            value="case">
                        </v-radio>
                    </v-radio-group>
                    </v-card-title>
                    <v-card-text>
                        <v-text-field
                            class="mx-4"
                            v-model="searchTerm"
                            flat
                            hide-details
                            :label="$t('search.placeholder')"
                            @keyup.enter.native="searchForUser"
                            prepend-inner-icon="search"
                        ></v-text-field>
                        <v-btn @click.native.stop="searchForUser">{{$t('search.button')}}</v-btn>
                    </v-card-text>
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
    name: 'search',
    data () {
        return {
            loading: true,
            searchTerm: null,
            searchBy: 'user',
            phoneregex: RegExp('^\\+[1-9]{1}[0-9]{3,14}$'),
            results: null,
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            }
        };
    },
    methods: {
        searchForUser () {
            switch (this.searchBy) {
                case 'user':
                    if (!this.searchTerm) {
                        this.snackbar.show = true;
                        this.snackbar.text = 'Not a valid SSN (11 digits required) or phonenumber (+4712345678)';
                        return;
                    }
                    Vue.axios.post('/search', { search: this.searchTerm }).then(response => {
                        if (response.data && response.data.client.uuid) {
                            if (response.data.client.ssn) {
                                sessionStorage.setItem('ssn', response.data.client.ssn);
                            }
                            this.$router.push({ name: 'patient', params: { uuid: response.data.client.uuid } });
                        } else {
                            this.snackbar.show = true;
                            this.snackbar.text = 'Could not find patient with this SSN or phonenumber';
                        }
                    }).catch(reason => {
                        console.log(reason);
                    });
                    break;
                case 'case':
                    if (this.searchTerm.length !== 6) {
                        this.snackbar.show = true;
                        this.snackbar.text = 'Case code should be of six character';
                    } else {
                        Vue.axios.get('/search-by-case/' + this.searchTerm).then(response => {
                            if (response.data) {
                                this.$router.push({ name: 'patient', params: { patientData: response.data } });
                            } else {
                                this.snackbar.show = true;
                                this.snackbar.text = 'Could not find a case';
                            }
                        }).catch(reason => {
                            this.snackbar.show = true;
                            this.snackbar.text = 'Could not find a case';
                            console.log(reason);
                        });
                    }
                    break;
                default:
                    this.snackbar.show = true;
                    this.snackbar.text = 'Select atleast one radio button';
            }
        }
    }
};
</script>
