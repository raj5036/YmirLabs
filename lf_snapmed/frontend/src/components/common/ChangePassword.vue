<template>
    <section>
        <template>
            <div class="page-respons__result change-password__input">
                <h1 class="page-respons__title">{{$t('page.response.password.title')}}</h1>
                <div class="password-input__input">
                    <input :placeholder="$t('page.response.password.current')" v-model="password" type="password" />
                </div>
                <div class="password-input__input">
                    <input :placeholder="$t('page.response.password.new')" v-model="newPassword" type="password" />
                </div>
                <div class="password-input__input">
                    <input :placeholder="$t('page.response.password.confirm')" v-model="confirmPassword" type="password" />
                </div>
                <transition name="fade">
                    <span v-if="showMsg" class="login__error">{{$t('page.response.password.' + code)}}</span>
                </transition>
                <div class="change-password__footer">
                    <a @click.prevent="goBack" href="#"> {{$t('page.response.password.back')}} </a>
                    <button @click="changePassword" class="button button--large button--dark">
                        {{$t('page.response.password.continue')}}
                    </button>
                </div>
            </div>
        </template>
    </section>
</template>

<script>
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'change-password',
    metaInfo () {
        return {
            title: 'Change Password'
        };
    },
    data () {
        return {
            password: '',
            newPassword: '',
            confirmPassword: '',
            showMsg: false,
            code: ''
        };
    },
    methods: {
        ...mapActions({
            changePass: ACTIONS.CHANGE_PASSWORD,
            logout: ACTIONS.LOGOUT
        }),
        async changePassword () {
            if (this.password.length < 1 || this.newPassword.length < 1 || this.confirmPassword.length < 1) {
                this.code = 'empty_err';
                this.showMsg = true;
                return;
            }
            if (this.newPassword.length < 6) {
                this.code = 'length_err';
                this.showMsg = true;
                return;
            }
            if (this.newPassword !== this.confirmPassword) {
                this.code = 'confirm_err';
                this.showMsg = true;
                return;
            }
            let response = await this.changePass({ 'current_password': this.password, 'new_password': this.newPassword, 'new_password_confirmation': this.confirmPassword });
            this.code = response.code;
            this.showMsg = true;
            this.clearText();
        },
        goBack () {
            this.$emit('displayResult');
        },
        clearText () {
            this.password = this.newPassword = this.confirmPassword = '';
        }

    }
};
</script>

<style lang="scss">
.change-password {
    &__input {
        max-width: 500px;
        margin: 0 auto;
        padding: 0 $partner-outer-spacing;
        margin-bottom: spacing(10);
    }
    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: spacing(5);
    }
}
</style>
