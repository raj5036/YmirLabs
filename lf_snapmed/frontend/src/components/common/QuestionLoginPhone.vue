<template>
    <div class='question-login-phone'>
        <PhoneNumber
            class="question-login-phone__input"
            :placeholder="$t('questions.login.placeholder.phone')"
            ref="number"
            v-model="number"
            :value="number"
            :focus="true"
            v-on:enter="set"
            @error="showError"
            :disabled="data.disabled || false"
        ></PhoneNumber>
        <div
            v-if="error"
            class="question-login-password__error-container question-large-text__error"
        >
            <img
                src="@assets/icons/error.svg"
                title="Snapmed"
                alt="Snapmed"
                class="question-login-password__icon"
            />
            <span class="question-login-password__error">{{
                $t('page.check.empty_error')
            }}</span>
        </div>
        <button
            class="question-login-phone__button button button--light button--tight"
            @click="set"
            :disabled="loading"
            v-if="number && !data.disabled"
        >
            <span class="loader-image" v-if="loading"><svg-loader /></span>
            <span v-else>{{ $t('btn.phone_text') }}</span>
        </button>
    </div>
</template>

<script>
// Vuex and store
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

// Assets
import PhoneNumber from '@common/PhoneNumber';
import { has, get } from 'lodash';

export default {
    name: 'QuestionLoginPhone',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data: function () {
        return {
            number: null,
            error: false,
            loading: false
        };
    },
    computed: {
        ...mapState(['region']),
        ...mapGetters({
            answers: GETTERS.ANSWERS,
            userUuid: GETTERS.USER_UUID
        })
    },
    methods: {
        ...mapActions({
            setNumber: ACTIONS.UPDATE_PAYMENT
        }),
        set () {
            const setLoadingFalse = () => {
                this.loading = false;
            };
            this.loading = true;
            if (this.number !== null && this.number !== '') {
                this.error = false;
                this.setNumber({ input: this.number, id: this.data.id }).then(() => {
                    setLoadingFalse();
                    if (this.userUuid) {
                        // eslint-disable-next-line no-undef
                        posthog.identify(this.userUuid);
                    }
                }
                );
            } else {
                this.error = true;
                this.setNumber({ input: null, id: this.data.id }).then(
                    setLoadingFalse
                );
            }
        },
        showError () {
            this.error = true;
        }
    },
    mounted: function () {
        this.number = has(this.answers, this.data.id)
            ? get(this.answers, this.data.id)
            : null;
    },
    components: {
        PhoneNumber
    }
};
</script>
