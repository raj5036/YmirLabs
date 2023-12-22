<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md6>
                <transition name="fade" mode="out-in">
                    <v-card class="card--login elevation-5" v-if="!secondFactor" key="login">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.login')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form ref="login" name="login" autocomplete="off" v-model="valid" @keyup.native.enter="valid && authenticate()">
                                <v-text-field name="phonenumber" class="input-group--phonenumber" v-model="phonenumber"></v-text-field>
                                <v-text-field prepend-icon="person" name="login" :label="$t('label.email')" :rules="[rules.required, rules.email]" v-model="email" type="text" autofocus></v-text-field>
                                <v-text-field
                                    id="password"
                                    prepend-icon="lock"
                                    name="password"
                                    :label="$t('label.password')"
                                    type="password"
                                    :rules="[rules.min]"
                                    v-model="password"
                                    autocomplete="new-password">
                                </v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <div class="forgot-password" @click="forgotPassword">{{$t('action.forgot_password')}}</div>
                            <v-spacer></v-spacer>
                            <v-btn
                                :disabled="authenticating"
                                :loading="authenticating"
                                @click.native.stop="authenticate"
                                color="primary">
                                {{$t('action.login')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                    <v-card class="card--login elevation-5" v-else key="second">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.login')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form autocomplete="off" @keypress.enter.native.prevent="sendOtp">
                                <v-text-field
                                    prepend-icon="vpn_key"
                                    name="otp"
                                    :label="$t('label.otp')"
                                    :rules="[rules.otp]"
                                    v-model="otp"
                                    required
                                    autofocus>
                                </v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                :disabled="verifying"
                                :loading="verifying"
                                @click.native.stop="sendOtp"
                                color="primary">
                                {{$t('action.verifyOtp')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </transition>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    data () {
        return {
            valid: true,
            validOtp: true,
            authenticating: false,
            verifying: false,
            secondFactor: false,
            phonenumber: '',
            email: '',
            password: '',
            otp: '',
            rules: {
                required: value => !!value || this.$t('rules.email.required'),
                min: value => value.length >= 6 || this.$t('rules.password.minLength'),
                otp: value => value.length === 4 || this.$t('rules.otp.length'),
                email: value => {
                    const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return pattern.test(value) || 'Invalid e-mail.';
                }
            }
        };
    },
    methods: {
        ...mapActions({
            login: ACTIONS.LOGIN,
            verify: ACTIONS.VERIFY
        }),
        async authenticate () {
            this.authenticating = true;
            this.secondFactor = await this.login({
                phonenumber: this.phonenumber,
                email: this.email,
                password: this.password
            });
            this.authenticating = false;
        },
        async forgotPassword () {
            this.$router.push('forgot-password');
        },
        async sendOtp () {
            this.verifying = true;
            const verified = await this.verify({ otp: this.otp, passwordReset: false });
            this.verifying = false;
            if (verified) {
                this.$router.push(this.$route.query.redirect || '/');
            } else {
                this.authenticating = false;
                this.verifying = false;
                this.secondFactor = false;
                this.phonenumber = '';
                this.email = '';
                this.password = '';
                this.otp = '';
            }
        }
    }
};
</script>

<style lang="scss">
.v-card.card--login {
    .v-card__title {
        color: white;
    }
    .input-group--phonenumber {
        margin-left: -9889px;
        width: 0;
        height: 0;
        overflow: hidden;
        input {
            z-index: -999;
        }
    }
    .forgot-password {
        color: #5e4098;
        margin-left: 10px;
        text-decoration: underline;
        font-weight: 700;
        cursor: pointer;
    }
}
</style>
