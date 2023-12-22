<template>
    <div :class="region === 'uk'? 'question-short-text-uk' : 'question-short-text'">
        <input
            :placeholder="$t(`questions.${data.placeholder}`)"
            v-model="text"
            @input="set"
            class="question-short-text__area"/>
         <div v-if="error" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.empty_error')}}</span>
        </div>
        <AutoFocus :disabled="(region !== 'uk')||(answers[data.id] !== null)"/>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import AutoFocus from '@common/AutoFocus';
import _ from 'lodash';

const isMobile = ('ontouchstart' in document.documentElement && navigator.userAgent.match(/Mobi/));

export default {
    name: 'QuestionShortText',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    components: {
        AutoFocus
    },
    computed: {
        ...mapState(['region']),
        ...mapGetters({
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE,
            answers: GETTERS.ANSWERS
        }),
        ...mapState({
            isPartnerVideoFlow: state => ((state.partner && state.partner.videoFlow) || false)
        })
    },
    data () {
        return {
            text: null,
            error: false
        };
    },
    async mounted () {
        await this.answers;
        // Set text to answer
        if (this.answers && this.answers[this.data.id]) {
            this.text = this.answers[this.data.id];
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER,
            updatePartner: ACTIONS.UPDATE_PARTNER
        }),
        set (e) {
            this.debouncedSet(e);
        },
        debouncedSet: _.debounce(function (e) {
            const text = e.target.value;
            if (text !== null && text !== '') {
                this.error = false;
                this.setAnswer({'answer': text, 'id': this.data.id});
            } else {
                this.error = true;
                this.setAnswer({'answer': null, 'id': this.data.id});
            }
        }, isMobile ? 1000 : 100)
    }
};
</script>
