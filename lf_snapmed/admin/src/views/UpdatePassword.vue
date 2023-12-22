<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md6>
                <transition name="fade" mode="out-in">
                    <v-card class="card--login elevation-5" v-if="!showMessage && !showErrorMessage">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form ref="password" name="password" autocomplete="off">
                                <v-text-field prepend-icon="password" name="password" :label="$t('label.new_password')" v-model="password" type="password" :rules="[rules.required, rules.password]" autofocus></v-text-field>
                                <v-text-field prepend-icon="password" name="conf-password" :label="$t('label.conf_password')" v-model="confPassword" type="password" :rules="[rules.required, rules.password]" ></v-text-field>
                            </v-form>
                            <span v-if="showError" class="login--error">{{$t('rules.password.same')}}</span>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                @click.native.stop="setPassword"
                                color="primary">
                                {{$t('action.next')}}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                    <v-card class="card--login elevation-5" v-else-if="!showErrorMessage">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text class="card--text">
                            <div>{{$t('statements.password-reset')}}</div>
                            <br>
                            <div><span>{{$t('statements.click-here')}}</span><span class="log--in" @click="redirect">{{$t('statements.log-in')}}</span></div>
                        </v-card-text>
                    </v-card>
                    <v-card class="card--login elevation-5" v-else>
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('title.forgot_password')}}</h2>
                        </v-card-title>
                        <v-card-text class="card--text">
                            <div>{{$t('statements.password-reset-error')}}</div>
                            <br>
                            <div><span>{{$t('statements.click-here')}}</span><span class="log--in" @click="resetAgain">{{$t('statements.reset-again')}}</span></div>
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
    name: 'updatePassword',
    data () {
        return {
            password: '',
            confPassword: '',
            showError: false,
            showMessage: false,
            showErrorMessage: false,
            rules: {
                required: value => !!value || this.$t('rules.password.required'),
                password: value => value.length >= 6 || this.$t('rules.password.minLength')
            }
        };
    },
    methods: {
        ...mapActions({
            updatePass: ACTIONS.UPDATE_PASSWORD
        }),
        async setPassword () {
            if (this.password !== this.confPassword) {
                this.showError = true;
            } else {
                this.showError = false;
                let response = await this.updatePass({ 'token': this.$route.params.token, 'password': this.password });
                if (response) {
                    this.showMessage = true;
                } else {
                    this.showErrorMessage = true;
                }
            }
        },
        redirect () {
            this.$router.push('/');
        },
        resetAgain () {
            this.$router.push('/forgot-password');
        }
    }
};
</script>

<style scoped>
    .login--error{
        color: #F44335;
        font-size: 14px;
        margin-left: 30px;
    }
    .card--text{
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .log--in{
        text-decoration: underline;
        cursor: pointer;
    }
</style>
