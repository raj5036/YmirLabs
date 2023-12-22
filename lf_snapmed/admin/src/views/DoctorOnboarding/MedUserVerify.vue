<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md6>
                <transition name="component-fade" mode="out-in">
                    <v-card class="card--verify elevation-5" v-if="!isPhoneVerified" key="phone">
                        <v-card-title class="primary justify-center">
                            <h2 class="card--verify text-xs-center">{{$t('title.verify_identity')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form autocomplete="off" v-model="isPhoneCardValid" @keypress.enter.native.prevent="sendOtp">
                                <v-text-field
                                    prepend-icon="phone"
                                    :label="$t('label.registered_mobile_no')"
                                    v-model="phonenumber"
                                    :rules="[rules.required, rules.phone]"
                                    autofocus>
                                </v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions class="justify-center">
                            <v-btn class="btn-width"
                                :disabled="isLoading"
                                :loading="isLoading"
                                @click.native.stop="sendOtp"
                                color="primary">
                                {{$t('action.send_otp')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                    <v-card class="card--verify elevation-5" v-else key="otp">
                        <v-card-title class="primary justify-center">
                            <h2 class="card--verify text-xs-center">{{$t('title.create_password')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form ref="form" autocomplete="off" v-model="isPasswordCardValid" @keypress.enter.native.prevent="createPassword">
                                <v-text-field prepend-icon="vpn_key" :label="$t('label.otp')" :rules="[rules.otp]" v-model="otp" type="text" autofocus></v-text-field>
                                <v-text-field prepend-icon="lock" :label="$t('label.new_password')" :rules="[rules.min]" v-model="password" type="password"></v-text-field>
                                <v-text-field prepend-icon="lock" :label="$t('label.conf_password')" :rules="[rules.min, rules.matchPassword]" v-model="confirmPassword" type="password"></v-text-field>
                                <v-checkbox color="primary" v-model="terms" :rules="[rules.agreeTerm]">
                                    <div slot='label' v-html="$t('label.terms', [$t('label.terms_url')])"></div>
                                </v-checkbox>
                            </v-form>
                        </v-card-text>
                        <v-card-actions class="justify-center">
                            <v-btn class="btn-width"
                                :disabled="isLoading"
                                :loading="isLoading"
                                @click.native.stop="createPassword"
                                color="primary">
                                {{$t('action.create')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </transition>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import store, { SET_SNACKBAR_MESSAGE } from '@/store';

export default {
    data () {
        return {
            isPhoneCardValid: false,
            isPasswordCardValid: false,
            isPhoneVerified: (sessionStorage.getItem('isPhoneVerified') === 'true'),
            phonenumber: '',
            otp: '',
            password: '',
            confirmPassword: '',
            terms: false,
            isLoading: false,
            rules: {
                required: value => !!value || this.$t('rules.number.required'),
                min: value => value.length >= 6 || this.$t('rules.password.minLength'),
                otp: value => value.length === 4 || this.$t('rules.otp.length'),
                matchPassword: (value, password) => value === this.password || this.$t('rules.password.same'),
                agreeTerm: value => !!value || this.$t('rules.terms'),
                phone: value => {
                    // eslint-disable-next-line
                    const pattern = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
                    return pattern.test(value) || this.$t('rules.number.required');
                }
            }
        };
    },
    methods: {
        async sendOtp () {
            try {
                if (!this.isPhoneCardValid) {
                    return;
                }
                this.isLoading = true;
                let data = {
                    token: this.$route.params.token,
                    phonenumber: this.phonenumber
                };
                let response = await this.axios.post(`verify-email-token`, data);
                if (response.data.status === 'success') {
                    this.isPhoneVerified = true;
                    sessionStorage.setItem('isPhoneVerified', this.isPhoneVerified);
                    return;
                }
                store.commit(SET_SNACKBAR_MESSAGE, this.$t('alert.' + response.data.status));
            } catch (error) {
                store.commit(SET_SNACKBAR_MESSAGE, 'Something went wrong!');
            } finally {
                this.isLoading = false;
            }
        },
        async createPassword () {
            try {
                this.$refs.form.validate();
                if (!this.isPasswordCardValid) {
                    return;
                }
                this.isLoading = true;
                let data = {
                    token: this.$route.params.token,
                    otp: this.otp,
                    password: this.password,
                    password_confirmation: this.confirmPassword,
                    accepted_terms: this.terms
                };
                let response = await this.axios.post(`set-password`, data);
                if (response.data.status === 'success') {
                    sessionStorage.removeItem('isPhoneVerified');
                    this.$router.push({ name: `meduser-thanks` });
                    return;
                }
                store.commit(SET_SNACKBAR_MESSAGE, this.$t('alert.' + response.data.status));
            } catch (error) {
                store.commit(SET_SNACKBAR_MESSAGE, 'Something went wrong!');
            } finally {
                this.isLoading = false;
            }
        }
    }
};
</script>

<style lang="scss">
.v-card.card--verify {
    .v-card__title {
        color: white;
    }
}
.btn-width {
    width: 125px;
}
.component-fade-enter-active, .component-fade-leave-active {
        transition: opacity .3s ease;
}
.component-fade-enter, .component-fade-leave-to {
    opacity: 0;
}
</style>
