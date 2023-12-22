<template>
    <section class="update-password">
        <template v-if="!showMsg">
            <header class="update-password__header">
                <div class="update-password__container">
                    <div class="update-password__header-content">
                        <h1 class="update-password__header-heading">{{$t('page.response.password.update.title')}}</h1>
                        <img
                            src="@assets/illustrations/woman-with-documents-from-right.svg"
                            class="update-password__header-image"
                        />
                    </div>
                </div>
            </header>
            <div class="update-password__input">
                <div class="password-input__input">
                    <input :placeholder="$t('page.response.password.new')" v-model="password" type="password" />
                </div>
                <div class="password-input__input">
                    <input :placeholder="$t('page.response.password.confirm')" v-model="confirmPassword" type="password" />
                </div>
                <transition name="fade">
                    <span v-if="showError" class="login__error">{{$t('page.response.password.' + errorCode)}}</span>
                </transition>
            </div>
            <footer class="update-password__center">
                <transition name="fade">
                    <div v-if="processing" class="result__processing"><svg-loader /></div>
                </transition>
                <button v-if="!processing" @click="updatePassword" class="button button--large button--dark">
                    {{$t('page.response.password.update.btn')}}
                </button>
            </footer>
        </template>
        <template v-else>
            <transition name="fade">
                    <div class="update-password__center" v-html="$t('page.response.password.' + errorCode, [loginRedirectUrl])"> </div>
                </transition>
        </template>
    </section>
</template>>

<script>
import { mapActions, mapState } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'update-password',
    metaInfo () {
        return {
            title: 'Update Password'
        };
    },
    data () {
        return {
            password: '',
            confirmPassword: '',
            showError: false,
            errorCode: '',
            showMsg: false,
            processing: false,
            loginRedirectUrl: ''
        };
    },
    computed: {
        ...mapState({
            region: state => state.region
        })
    },
    methods: {
        ...mapActions({
            updatePass: ACTIONS.UPDATE_PASSWORD
        }),
        async updatePassword () {
            if (this.password.length < 6 || this.confirmPassword.length < 6) {
                this.errorCode = 'length_err';
                this.showError = true;
                return;
            }
            if (this.password !== this.confirmPassword) {
                this.errorCode = 'confirm_err';
                this.showError = true;
                return;
            }
            this.processing = true;
            let response = await this.updatePass({ 'token': this.$route.params.token, 'password': this.password, 'password_confirmation': this.confirmPassword, 'region': this.region });
            this.processing = false;
            this.errorCode = response.code;
            this.showMsg = true;
            if (response.status) {
                this.loginRedirectUrl = response.login_redirect_url;
            }
            this.clearText();
        },
        clearText () {
            this.password = this.confirmPassword = '';
        }
    }
};
</script>

<style lang="scss">
.update-password {
    width: 100%;
    &__container {
        max-width: $partner-max-width;
        margin: 0 auto;
        padding: 0 $partner-outer-spacing;
    }
    &__header {
        margin-bottom: spacing(3);
        &-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: solid 1px color(mid-purple);
        }
        &-heading {
            font-family: $serif-font-family;
            font-size: 145%;
            font-weight: $WEIGHT_BOLD;
            padding-right: spacing(2);
        }
        &-logout{
            margin-left: auto;
        }
    }
    &__thanks-header{
        padding: spacing(4);
        text-align: center;
        &-img{
            margin-bottom: spacing(4);
        }
    }
    &__input {
        max-width: 500px;
        margin: 0 auto;
        padding: 0 $partner-outer-spacing;
        margin-bottom: spacing(10);
    }
    &__center {
        text-align: center;
    }
    &__text {
        color: color(default-text);
        font-size: 112.5%;
        font-weight: $WEIGHT_LIGHT;
        line-height: 1.666;
        max-width: 320px;
        margin: 0 auto;
        margin-bottom: spacing(8);
    }
}
</style>
