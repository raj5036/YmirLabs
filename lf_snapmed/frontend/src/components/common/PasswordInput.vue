<template>
    <div class="password-input">
        <transition name="fade" mode="out-in">
            <div
                v-if="resetPassword"
                class="password-input__reset"
                key="reset">
                <label>{{$t('password.reset.label')}}</label>
                <label
                    v-if="showBack"
                    @click="toggleReset"
                    class="password-input__lbl-reset">
                    {{$t('password.reset.back')}}
                </label>
            </div>
            <div
                v-else
                key="password">
                <div class="password-input__input">
                    <input
                        :placeholder="$t('password.placeholder')"
                        :value="password" @input="passwordInput($event.target.value)"
                        @keyup.enter="$listeners.keyup"
                        autocomplete="new-password"
                        type="password">
                </div>
                <label
                    @click="toggleReset"
                    class="password-input__lbl-reset">
                    {{$t('password.reset.action')}}
                </label>
            </div>
        </transition>
    </div>
</template>

<script>
import { createToken } from 'vue-stripe-elements-plus';
import { has } from 'lodash';

export default {
    props: {
        value: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            password: null,
            resetPassword: false,
            showBack: true,
            stripeKey: process.env.VUE_APP_STRIPE_KEY,
            stripeOptions: {
                classes: {
                    base: 'password-payment__input',
                    complete: 'password-payment__input--complete',
                    empty: 'password-payment__input--empty',
                    focus: 'password-payment__input--focus',
                    invalid: 'password-payment__input--invalid'
                },
                style: {
                    base: {
                        fontWeight: '100',
                        fontSize: '16px',
                        color: '#5e4098'
                    }
                }
            }
        };
    },
    methods: {
        toggleReset () {
            this.resetPassword = !this.resetPassword;
            if (!this.resetPassword) {
                this.passwordInput('');
            } else {
                this.passwordInput('tok_');
            }
        },
        onCardChange (eventComplete) {
            if (eventComplete) {
                this.token();
            }
        },
        passwordInput (value) {
            this.password = value;
            this.$emit('input', this.password);
        },
        async token () {
            try {
                this.showBack = false;
                const data = await createToken();
                if (has(data, 'token.id')) {
                    this.$emit('input', data.token.id);
                } else {
                    this.$emit('input', '');
                }
            } catch (error) {
                console.log(error);
            }
        }
    }
};
</script>

<style lang="scss">
.password-input {
    display: flex;
    flex-flow: column;
    justify-content: flex-start;
    align-items: stretch;
    &__input {
        // Inputs
        display: flex;
        align-items: center;
        height: 40px;
        width: 100%;
        border: 1px solid color(blueberry);
        border-radius: 5px;
        padding-left: 50px;
        position: relative;
        color: color(blueberry);
        margin-top: 15px;
        &:before {
            content: '';
            height: 20px;
            width: 20px;
            position: absolute;
            left: 15px;
            top: 10px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            background-image: url('~@/assets/login/eye.svg');
        }
        // Input and placeholder
        ::placeholder {
            color: color(blueberry);
            opacity: 0.5;
        }
        input {
            outline: none;
            border: none;
            width: 100%;
            color: color(blueberry);
        }
    }
    &__lbl-reset {
        float: right;
        margin-top: spacing(1);
    }
    &__reset {
        // padding: 10px 0;
        label{
           display: block;
           margin-top: spacing(2);
           margin-bottom: spacing(2);
        }
    }
}
.password-payment {
    &__input {
        display: flex;
        flex-direction: column;
        margin-left: auto;
        max-width: 100%;
        padding: 10px 10px;
        height: 40px;
        width: 100%;
        border: 1px solid color(blueberry);
        border-radius: 5px;
        position: relative;
        color: color(blueberry);
        margin-top: 15px;
    }
}
</style>
