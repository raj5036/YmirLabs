<template>
    <div class="question-partner-phone-verify">
        <PhoneNumber v-show="this.requestChange" class="question-login-phone__input" :placeholder="$t('questions.login.placeholder.phone')" ref="number" v-model="number" :value="number" :focus="true" v-on:enter="set"></PhoneNumber>
        <div class="button-group">
            <span v-if="!this.requestChange" class="question-partner-phone-verify__button button button--light button--tight" @click="toggleRequestChange">{{$t('questions.partner.phone.change')}}</span>
            <button class="question-partner-phone-verify__button button button--tight" @click="set"  :disabled="loading" v-if="number">
                <span class="loader-image" v-if="loading"><svg-loader /></span>
                <span v-else>{{$t('questions.partner.phone.confirm')}}</span>
            </button>
        </div>
    </div>
</template>

<script>
// Vuex and store
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import { formatNumber } from 'libphonenumber-js';

// Assets
import PhoneNumber from './PhoneNumber';

export default {
    name: 'QuestionPartnerPhoneVerify',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data: function () {
        return {
            requestChange: false,
            number: null,
            loading: false
        };
    },
    computed: {
        ...mapGetters({
            partnerPhoneNumber: [GETTERS.PARTNER_PHONE_NUMBER]
        })
    },
    methods: {
        ...mapActions({
            setNumber: ACTIONS.SET_PHONE_NUMBER,
            updatePartner: ACTIONS.UPDATE_PARTNER
        }),
        set () {
            this.loading = true;
            if (this.number) {
                this.updatePartner({'answer': this.number, 'id': this.data.id});
                this.setNumber(this.number).then(() => {
                    this.loading = false;
                }).catch(() => {
                    this.loading = false;
                });
            }
        },
        toggleRequestChange () {
            this.requestChange = !this.requestChange;
        }
    },
    mounted () {
        // @todo just format as NO since partner users are norwegian for now
        this.number = formatNumber(this.partnerPhoneNumber, 'NO', 'International');
    },
    components: {
        PhoneNumber
    }
};
</script>

<style lang="scss">
.question-partner-phone-verify {
    display: flex;
    flex-direction: column;
    &__input {
        // Chat input
        @include chat-input();
    }
    &__button {
        // Next btn
        @include fadeInOut(true);
        @include chat-btn;
        align-self: flex-end;
        margin-top: spacing();
    }

    .button-group {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        .button {
            margin-left: spacing();
        }
    }
}
</style>
