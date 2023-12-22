<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md4>
                <v-card class="card--invite elevation-5" v-if="invite" key="invite">
                    <v-card-title class="primary justify-center">
                        <h2 class="card--invite text-xs-center">{{$t('title.add_doctor')}}</h2>
                    </v-card-title>
                    <v-card-text>
                        <v-form ref="form" autocomplete="off" v-model="valid" @keypress.enter.native.prevent="inviteUser">
                            <v-layout justify-space-between row wrap>
                                <v-flex xs6>
                                    <v-checkbox color="primary" v-model="isDoctor" label="Doctor" />
                                </v-flex>
                                <v-flex xs6>
                                    <v-checkbox color="primary" v-model="isAdmin" label="Admin" />
                                </v-flex>
                            </v-layout>
                            <v-text-field prepend-icon="person" label="Name of doctor" v-model="name" type="text" :rules="[rules.required('Name')]"></v-text-field>
                            <v-text-field prepend-icon="email" label="Email Address" v-model="email" type="text" :rules="[rules.email]"></v-text-field>
                            <v-combobox
                                prepend-icon="location_on"
                                v-model="country"
                                :items="countries"
                                label="Country"
                                :return-object="false"
                                :rules="[rules.required('Country')]">
                            </v-combobox>
                            <v-combobox
                                prepend-icon="add_location_alt"
                                v-model="servableRegions"
                                :items="regions"
                                label="Service Regions"
                                :return-object="false"
                                :rules="[rules.required('Servive Regions')]"
                                multiple>
                            </v-combobox>
                        </v-form>
                    </v-card-text>
                    <v-card-actions class="justify-center">
                        <v-btn class="btn-width"
                            :disabled="isLoading"
                            :loading="isLoading"
                            @click.native.stop="inviteUser"
                            color="primary">
                            {{$t('action.invite')}}
                        </v-btn>
                    </v-card-actions>
                </v-card>
                <v-card style="height:450px;" class="card--invite elevation-5" v-else key="success">
                    <v-card-title class="primary justify-center">
                        <h2 class="card--invite text-xs-center">{{$t('title.add_doctor')}}</h2>
                    </v-card-title>
                    <v-card-text class="primary--text">
                        <div style="margin-top:10%" class="text-xs-center">
                            <img src="../assets/check-badge.svg" class="badge" />
                            <h3 style="padding: 5% 10% 0 10%;"> Your invite has been sent successfully! </h3>
                        </div>
                    </v-card-text>
                    <v-card-actions class="justify-center">
                        <v-btn
                            @click.native.stop="inviteNewUser"
                            color="primary">
                            {{$t('action.add_new')}}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import store, { SET_SNACKBAR_MESSAGE } from '@/store';

export default {
    data () {
        return {
            valid: false,
            isLoading: false,
            invite: true,
            isDoctor: false,
            isAdmin: false,
            name: '',
            email: '',
            country: '',
            servableRegions: [],
            rules: {
                required (field) {
                    return value => {
                        if (value != null && value.length > 0) {
                            return true;
                        }
                        return field + ' cannot be empty.';
                    };
                },
                email: value => {
                    const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return pattern.test(value) || 'Invalid e-mail.';
                }
            },
            countries: [
                {
                    text: 'Norway',
                    value: 'no'
                }, {
                    text: 'Sweden',
                    value: 'se'
                }, {
                    text: 'United Kingdom',
                    value: 'uk'
                }, {
                    text: 'Germany',
                    value: 'de'
                }
            ],
            regions: [
                {
                    text: 'International',
                    value: 'co'
                }, {
                    text: 'Norway',
                    value: 'no'
                }, {
                    text: 'Sweden',
                    value: 'se'
                }, {
                    text: 'United Kingdom',
                    value: 'uk'
                }, {
                    text: 'Germany',
                    value: 'de'
                }
            ]
        };
    },
    methods: {
        async inviteUser () {
            try {
                if (!this.isDoctor && !this.isAdmin) {
                    store.commit(SET_SNACKBAR_MESSAGE, 'Select atleast one role.');
                    return;
                }
                this.$refs.form.validate();
                if (!this.valid) {
                    return;
                }
                this.isLoading = true;
                let data = {
                    name: this.name,
                    email: this.email,
                    country: this.country,
                    servable_regions: this.servableRegions,
                    is_doctor: this.isDoctor,
                    is_superadmin: this.isAdmin
                };
                let response = await this.axios.post(`invite-user`, data);
                if (response.data.status === 'success') {
                    this.invite = false;
                    return;
                }
                store.commit(SET_SNACKBAR_MESSAGE, this.$t('alert.' + response.data.status));
            } catch (error) {
                store.commit(SET_SNACKBAR_MESSAGE, 'Something went wrong!');
            } finally {
                this.isLoading = false;
            }
        },
        inviteNewUser () {
            this.invite = true;
        }
    }
};
</script>

<style lang="scss" scoped>
.v-card.card--invite {
    .v-card__title {
        color: white;
    }
}
.btn-width {
    width: 230px;
}
</style>
