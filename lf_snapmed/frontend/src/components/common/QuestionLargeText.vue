<template>
    <div>
        <textarea
            :placeholder="$t(`questions.${data.placeholder}`)"
            v-model="text"
            :aria-invalid="error"
            class="dd-input-large-text"/>
        <div v-if="error" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.empty_error')}}</span>
        </div>
        <button @click="set" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'QuestionLargeText',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    computed: {
        ...mapState(['region']),
        ...mapGetters({
            answers: GETTERS.ANSWERS
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
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        set () {
            if (this.text !== null && this.text !== '') {
                this.error = false;
                this.setAnswer({'answer': this.text, 'id': this.data.id});
            } else {
                this.error = true;
                this.setAnswer({'answer': null, 'id': this.data.id});
            }
        }
    }
};
</script>
