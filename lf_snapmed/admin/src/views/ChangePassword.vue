<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex xs12 sm8 md6>
                <transition name="fade" mode="out-in">
                    <v-card class="card--login elevation-5">
                        <v-card-title class="primary">
                            <h2 class="text-xs-center">{{$t('action.change_password')}}</h2>
                        </v-card-title>
                        <v-card-text>
                            <v-form ref="login" name="login" autocomplete="off">
                                <v-text-field prepend-icon="lock" name="password" :label="$t('label.current_password')" :rules="[rules.required, rules.password]" v-model="password" type="password" autofocus></v-text-field>
                                <v-text-field prepend-icon="lock" name="new-password" :label="$t('label.new_password')" :rules="[rules.required, rules.password]" v-model="newPassword" type="password"></v-text-field>
                                <v-text-field prepend-icon="lock" name="conf-password" :label="$t('label.conf_password')" :rules="[rules.required, rules.password]" v-model="confPassword" type="password"></v-text-field>
                            </v-form>
                        </v-card-text>
                        <transition name="fade">
                            <span v-if="showMsg" class="login__error">{{$t('statements.' + code)}}</span>
                        </transition>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                @click.native.stop="changePassword"
                                color="primary">
                                {{$t('action.next')}}
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
    name: 'changePassword',
    data () {
        return {
            password: '',
            newPassword: '',
            confPassword: '',
            showMsg: false,
            code: '',
            incomplete: true,
            rules: {
                required: value => !!value || this.$t('rules.password.required'),
                password: value => value.length >= 6 || this.$t('rules.password.minLength')
            }
        };
    },
    methods: {
        ...mapActions({
            chng_password: ACTIONS.CHANGE_PASSWORD
        }),
        async changePassword () {
            if (this.password && this.newPassword && this.confPassword) {
                if (this.newPassword === this.confPassword) {
                    let response = await this.chng_password({ 'current_password': this.password, 'new_password': this.newPassword });
                    this.code = response.code;
                    this.showMsg = true;
                } else {
                    this.code = 'password_not_match';
                    this.showMsg = true;
                }
            }
        }
    }
};
</script>

<style lang="scss">
.login__error{
    color: #F44335;
    padding: 20px;
}
</style>
