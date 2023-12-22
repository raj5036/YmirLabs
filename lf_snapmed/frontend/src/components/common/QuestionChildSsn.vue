<template>
    <div>
        <textarea
            onkeydown="return (event.keyCode!=13)"
            :placeholder="$t('questions.child_ssn.placeholder')"
            ref="ssn_input"
            v-model="text"
            @input="setValue"
            class="dd-input">
        </textarea>
        <div v-if="error" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="error" alt="error" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('questions.child_ssn.error')}}</span>
        </div>
        <button v-if="showProceed" @click="set" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
    </div>
</template>

<script>

import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import { getCountryTools } from 'verifiera';
import Inputmask from 'inputmask';

export default {
    name: 'QuestionChildSsn',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        }),
        ...mapState({
            locale: state => state.locale
        })
    },
    data () {
        return {
            text: '',
            error: false,
            showProceed: false
        };
    },
    mounted () {
        if (this.locale === 'sv') {
            var im = new Inputmask({mask: '999999-9999',
                showMaskOnHover: false,
                showMaskOnFocus: false });
            im.mask(this.$refs.ssn_input);
        }

        if (this.answers[this.data.id]) {
            this.text = this.answers[this.data.id];
            this.showProceed = true;
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        setValue () {
            const ssn = getCountryTools(this.text);
            if (this.text.length === 11 && ssn.validate()) {
                this.error = false;
                this.showProceed = true;
            } else if (this.text.length === 11 && !ssn.validate()) {
                this.showProceed = false;
                this.error = true;
            } else {
                this.showProceed = false;
            }
        },
        set () {
            const ssn = getCountryTools(this.text);
            if (this.text.length === 11 && ssn.validate()) {
                this.setAnswer({'answer': this.text, 'id': this.data.id});
            }
        }
    }
};
</script>
