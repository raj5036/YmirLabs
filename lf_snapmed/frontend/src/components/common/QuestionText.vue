<template>
    <div class="question-text">
        <div v-if="error" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.empty_error')}}</span>
        </div>
        <textarea
            :placeholder='$t(`questions.${data.placeholder}`)'
            v-model="text"
            class="question-text__area">
        </textarea>
        <button @click="set" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'QuestionText',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    computed: {
        ...mapGetters({
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE,
            answers: GETTERS.ANSWERS
        }),
        ...mapState({
            isPartnerVideoFlow: state => ((state.partner && state.partner.videoFlow) || false),
            region: state => state.region
        })
    },
    data () {
        return {
            text: null,
            error: false
        };
    },
    mounted () {
        // Set text to answer
        this.text = this.answers[this.data.id];
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER,
            updatePartner: ACTIONS.UPDATE_PARTNER
        }),
        set () {
            // Set the answer
            if (this.isPartnerFlow && this.isPartnerVideoFlow) { // only send 'description' to partner object in store in video flow
                this.updatePartner({'answer': this.text, 'id': this.data.id});
            } else if (this.region === 'uk') {
                if (this.text !== null && this.text !== '') {
                    this.error = false;
                    this.setAnswer({'answer': this.text, 'id': this.data.id});
                } else {
                    this.error = true;
                    this.setAnswer({'answer': null, 'id': this.data.id});
                }
            } else {
                this.setAnswer({'answer': this.text, 'id': this.data.id});
            }
        }
    }
};
</script>
