<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md6>
                <transition name="fade" mode="out-in">
                    <v-card class="card--login elevation-5" v-if="!otpSent && !otpVerfied">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form ref="login" name="login" autocomplete="off">
                                <v-text-field prepend-icon="phone" name="login" :label="$t('label.registered_mobile_no')" :rules="[rules.required]" v-model.number="phonenumber" type="number" autofocus></v-text-field>
                            </v-form>
                        </v-card-text>
                        <transition name="fade">
                            <span v-if="showError" class="login__error">{{$t('statements.' + code)}}</span>
                        </transition>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                @click.native.stop="sendOTP"
                                color="primary">
                                {{$t('action.next')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                    <v-card class="card--login elevation-5" v-else-if="!otpVerfied" key="second">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form autocomplete="off">
                                <v-text-field prepend-icon="vpn_key" name="otp" :label="$t('label.otp')" :rules="[rules.otp]" v-model="otp" required autofocus></v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                :disabled="verifying"
                                :loading="verifying"
                                @click.native.stop="verifyOtp"
                                color="primary">
                                {{$t('action.next')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                     <v-card class="card--login elevation-5" v-else key="thrid">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text class="card--text">
                            <v-icon large color=#4a148c>check_circle</v-icon>
                            <br>
                            <br>
                            <div>Your phone number has been verified.</div>
                            <br>
                            <div>To complete the password reset, please click the secure link in the email we just sent to your registered email address.</div>
                        </v-card-text>
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
    name: 'forgotPassword',
    data () {
        return {
            phonenumber: '',
            otp: '',
            verifying: false,
            otpSent: false,
            showError: false,
            code: '',
            otpVerfied: false,
            rules: {
                required: value => !!value || this.$t('rules.number.required'),
                otp: value => value.length === 4 || this.$t('rules.otp.length')
            }
        };
    },
    methods: {
        ...mapActions({
            sendOtp: ACTIONS.SEND_OTP,
            verify: ACTIONS.VERIFY
        }),
        async sendOTP () {
            let response = await this.sendOtp({ phonenumber: this.phonenumber });
            this.otpSent = response.status;
            if (!this.otpSent) {
                this.code = response.code;
                this.showError = true;
            }
        },
        async verifyOtp () {
            this.verifying = true;
            this.otpVerfied = await this.verify({ otp: this.otp, passwordReset: true });
            this.verifying = false;
        }
    }
};
</script>

<style>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
.card--text{
    text-align: center;
}
</style>
