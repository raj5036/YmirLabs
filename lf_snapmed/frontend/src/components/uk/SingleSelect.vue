<template>
    <div :class="region === 'uk' ? `question-single-text-uk` : `question-single-text`">
        <!-- <label>something</label>
        <pre>{{ selected }}</pre> -->
        <section class="options-list">
            <button
                v-for="(option, index) in options"
                v-on:click="click"
                :data-key="index"
                :class="selected === String(index) ? 'selected-button' : ''"
                :key="index"
                :style="'--order:' + index + ';'"
            > <span>{{$t(`questions.${option.text}`)}}</span> </button>
        </section>
            <textarea :style="'--order:' + options.length + ';'" @input="setCustom" v-model="custom" class="others-input" v-if="customSelected" type="text" />
            <!-- replace -->
            <button v-if="showProceed" @click="handleProceed" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
            <AutoFocus :disabled="(answers[data.id] !== null || region !== 'uk')"/>
    </div>
</template>
<script>
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'SingleSelect',
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
    components: {AutoFocus},
    mounted () {
        // Set text to answer
        const givenOptions = this.data.options || [];
        const answer = this.answers[this.data.id] || null;
        const savedAnswer = answer;
        const custom = this.data.custom || false;
        function prepareOptions (obj) {
            let savedIndex;
            let key;
            let returnArray = [];
            let answerHasCustom = true;
            for (key = 0; key < givenOptions.length; key++) {
                const option = givenOptions[key];
                let returnObject = {};
                returnObject.text = option.text;
                if (option.value === null || option.value === undefined) {
                    returnObject.value = option.text;
                } else {
                    returnObject.value = option.value;
                }
                if (option.descriptionNeeded === null || option.descriptionNeeded === undefined) {
                    returnObject.descriptionNeeded = false;
                } else {
                    // returnObject.descriptionNeeded = option.descriptionNeeded;
                }
                if (obj.$t(`questions.${returnObject.value}`) === savedAnswer) { savedIndex = key; };
                if (savedAnswer === obj.$t(`questions.${returnObject.value}`)) {
                    answerHasCustom = false;
                }
                returnArray.push(returnObject);
            }
            if (custom) {
                returnArray.push({text: 'duration.custom', 'value': 'duration.custom', descriptionNeeded: true});
            }
            if (answerHasCustom) savedIndex = givenOptions.length;
            return [returnArray, savedIndex, {custom: answerHasCustom, value: savedAnswer}];
        }
        let returnArray;
        let savedIndex;
        let customInfo;
        [ returnArray, savedIndex, customInfo ] = prepareOptions(this);
        this.options = returnArray;
        if (answer !== null) {
            this.selected = String(savedIndex);
            if (customInfo.custom) {
                this.customSelected = customInfo.custom;
                this.custom = customInfo.value;
            }
        }
    },
    data: function () {
        return {
            options: null,
            selected: null,
            customSelected: false,
            custom: null,
            showProceed: false
        };
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        click: function (e) {
            this.customSelected = false;
            if (this.selected !== e.target.dataset.key) {
                this.custom = null;
            }
            this.selected = e.target.dataset.key;
            const selectedOption = this.options[this.selected];
            this.setState();
            if (selectedOption.descriptionNeeded) {
                this.customSelected = true;
            }
        },
        setState: function () {
            const answer = this.getAnswerObject();
            if (this.region === 'uk') {
                if (answer.description) {
                    this.setAnswer({'answer': answer.description, 'id': this.data.id});
                } else { this.setAnswer({'answer': answer.value, 'id': this.data.id}); }
            } else {
                if (answer.description) this.showProceed = true;
                else {
                    this.showProceed = false;
                    if (answer.value !== 'Custom') { this.setAnswer({'answer': answer.value, 'id': this.data.id}); }
                }
            }
        },
        getAnswerObject: function () {
            const answer = {};
            var options = JSON.parse(JSON.stringify(this.options));
            this.selected && (answer.value = this.$t(`questions.${options[this.selected].value}`));
            this.custom && (answer.description = this.custom);
            return answer;
        },
        handleProceed: function () {
            const answer = this.getAnswerObject();
            this.setAnswer({'answer': answer.description, 'id': this.data.id});
        },
        setCustom: function (e) {
            this.setState();
        }
    }
};
</script>
