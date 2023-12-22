<template>
    <div class="question-options">
        <span v-for="(option, index) in answerOptions" :key="option.value" class="question-options__button">
            <span :style="'--order:' + index + ';'" v-html="option.label" @click="set(option.value)" class="question-options__option" :class="selected(option.value)"></span>
            <span v-if="option.hint" class="option-hint" >{{option.hint}}</span>
        </span>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

import { split, has, get, last, isUndefined, isObject, map } from 'lodash';

export default {
    name: 'QuestionOptions',
    props: {
        data: {
            type: Object,
            required: true,
            validate: object => object.options
        },
        age: {
            type: String,
            default: undefined
        }
    },
    computed: {
        ...mapState({
            justAnswered: state => state.justAnswered,
            region: state => state.region
        }),
        ...mapGetters({
            answers: GETTERS.ANSWERS
        }),
        answerOptions () {
            const vm = this;
            const options = map(vm.data.options, (option, index) => {
                const translated = vm.$t(`questions.${option}`);
                if (!isUndefined(vm.age) && isObject(translated)) {
                    return {label: translated[vm.age], value: `${option}`};
                }
                let returnVal = {label: translated, value: `${option}`};
                if (vm.data.hints) {
                    if (vm.data.hints[index]) { returnVal['hint'] = vm.$t(`questions.${vm.data.hints[index]}`); }
                }
                return returnVal;
            });
            return options;
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        getAnswer (x) {
            return has(this.answers, x) ? get(this.answers, x) : null;
        },
        set (x) {
            this.setAnswer({ 'answer': this.format(x), 'id': this.data.id });
        },
        selected (x) {
            return this.format(x) === this.getAnswer(this.data.id) ? 'question-options__option--selected' : '';
        },
        format (x) {
            return last(split(x, '.'));
        }
    },
    mounted () {
        if (!this.justAnswered && ((this.data.id === 'medication' && this.getAnswer(this.data.id) === 'no') || (this.data.id === 'treatment' && this.getAnswer(this.data.id) === 'no'))) {
            this.setAnswer({ 'answer': null, 'id': this.data.id });
        }
    }
};
</script>
