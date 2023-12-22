<template>
    <div :class="region === 'uk'? 'question-login-email-uk' : 'question-login-email'">
        <div
            v-if="showInvalidError"
            class="question-login-password__error-container question-large-text__error"
        >
            <img
                src="@assets/icons/error.svg"
                title="Snapmed"
                alt="Snapmed"
                class="question-login-password__icon"
            />
            <span class="question-login-password__error">
                {{ $t('page.check.email_error') }}</span
            >
        </div>
        <div
            v-if="showAlreadyExistsError"
            class="question-login-password__error-container question-large-text__error"
        >
            <img
                src="@assets/icons/error.svg"
                title="Snapmed"
                alt="Snapmed"
                class="question-login-password__icon"
            />
            <span class="question-login-password__error">
                {{ $t('page.check.email_already_exists') }}</span
            >
        </div>
        <input
            class="question-login-email__input"
            :placeholder="$t('questions.login.placeholder.email')"
            ref="key"
            type="email"
            v-on:input="enableButton"
            v-model="key"
            v-on:keyup.enter="set"
            :disabled="this.disableInput"
            :style="
                this.disableInput
                    ? { background: 'rgba(233, 227, 232, 0.6)' }
                    : null
            "
            autofocus
        />
        <button
            class="question-login-email__button button button--light button--tight"
            @click="set"
            :disabled="disableButton"
        >
            <span class="loader-image" v-if="isWaitingForAnswer"
                ><svg-loader
            /></span>
            <span v-else>{{ $t('btn.next') }}</span>
        </button>
    </div>
</template>

<script>
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'QuestionLoginEmail',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            key: null,
            submittedEmail: null,
            isWaitingForAnswer: false,
            showInvalidError: false,
            showAlreadyExistsError: false,
            disableInput: false,
            disableButton: false
        };
    },
    methods: {
        ...mapActions({
            setEmail: ACTIONS.SET_EMAIL
        }),
        enableButton () {
            this.disableButton && (this.disableButton = false);
        },
        set () {
            // set errors to false
            this.disableButton = true;
            this.showInvalidError = false;
            this.showAlreadyExistsError = false;
            this.isWaitingForAnswer = true;

            // validate email
            if (!this.validateEmail(this.key)) {
                this.showInvalidError = true;
                this.disableButton = true;
            } else {
                // set email
                if (this.key) {
                    this.submittedEmail = this.key;
                    this.setEmail(this.key).then(response => {
                        this.isWaitingForAnswer = false;
                        this.disableButton = false;
                        if (response === 'alreadyExists') {
                            this.showAlreadyExistsError = true;
                        }
                    });
                }
            }
        },
        validateEmail (email) {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    },
    computed: {
        ...mapGetters({
            userEmail: GETTERS.USER_EMAIL
        }),
        ...mapState({
            region: state => state.region
        })
    },
    async mounted () {
        // Set email if provided
        if (this.userEmail !== null && this.userEmail !== '') {
            this.key = await this.userEmail;
            this.disableInput = true;
        }
    }
};
</script>
