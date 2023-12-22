<template>
    <div>
        <span v-html="questionOutput" class="question-ask"></span>
    </div>
</template>

<script>
import { has, get } from 'lodash';
import { formatNumber } from 'libphonenumber-js';

import { mapGetters, mapState } from 'vuex';
import { GETTERS } from '@/store';

export default {
    name: 'QuestionAsk',
    props: {
        who: {
            required: true
        },
        age: {
            required: true
        },
        phoneNumber: {
            default: false
        },
        question: {
            type: Object,
            required: true
        }
    },
    computed: {
        questionOutput () {
            // Setup who and age with default options.
            const who = this.who === null ? 'me' : this.who;
            const age = this.age === null ? 'minor' : this.age;
            // Get the question object or string

            let questionTemplate = '';

            if (this.region === 'uk' && this.question.id === 'password' && this.newUser) {
                questionTemplate = this.$t('questions.' + this.question.newUserQuestion);
            } else {
                questionTemplate = this.$t('questions.' + this.question.question);
            }

            if (typeof questionTemplate === 'object' && who && has(questionTemplate, who)) {
                // Check who
                questionTemplate = get(questionTemplate, who);
            }
            if (typeof questionTemplate === 'object' && age && has(questionTemplate, age)) {
                // Check age
                questionTemplate = get(questionTemplate, age);
            }

            if (this.question.type === 'QuestionPartnerPhoneVerify' && this.phoneNumber) {
                let formattedPhone = formatNumber(this.phoneNumber, 'NO', 'International'); // TODO for now we assume all partner phone numbers should be +47
                questionTemplate += ` <b>${formattedPhone}</b>`;
            }
            // Return question
            return typeof questionTemplate === 'string' ? questionTemplate : '-';
        },
        ...mapGetters({
            newUser: GETTERS.NEW_USER,
            region: GETTERS.region
        }),
        ...mapState({
            region: state => state.region
        })
    }
};
</script>
