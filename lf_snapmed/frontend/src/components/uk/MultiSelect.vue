<template>
    <div :class="region === 'uk'? `question-multi-text-uk` : `question-multi-text`">
        <section class="options-list">
            <button
                v-for="(option, index) in options"
                v-on:click="set"
                :data-key="option.value"
                :class="selected.includes(String(option.value)) ? 'selected-button' : ''"
                :key="index"
                :style="'--order:' + index + ';'"
            >
                <div class="tick" /> <span>{{option.text}}</span>
            </button>
             <button
                v-on:click="invertCustomSelected"
                data-key="other"
                :class="customSelected ? 'selected-button' : ''"
                key="other"
                :style="'--order:' + options.length + ';'"
            >
                <div class="tick" ></div> <span>{{customText ? $t(`questions.${customText}`) : "Others"}}</span>
            </button>
        </section>
        <textarea @input="setCustom" v-model="custom" class="others-input" :placeholder="$t(`questions.${data.placeholder}`)" v-if="customSelected" type="text" />
        <span v-if="error" class="dd-error-text">{{error}}</span>

        <button v-if="showProceed" @click="callAPI" class="dd-button proceed-button">{{$t(`btn.next`)}}</button>
        <AutoFocus :disabled="((answers[data.id] !== null)||(region !== 'uk'))"/>
    </div>
</template>
<script>
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'MultiSelect',
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
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        // Set text to answer
        this.selected = this.answers[this.data.id] || [];
        this.showProceed = (this.region !== 'uk');

        if (this.selected.length !== null) {
            const customIndex = this.getObjectIndex(this.selected);
            const customExists = (customIndex !== null && customIndex !== undefined);
            customExists && (this.customSelected = true);
            customExists && (this.custom = this.selected[customIndex].custom);
        }
        this.prepareOptions();
    },
    data: function () {
        return {
            options: null,
            selected: [],
            customSelected: false,
            custom: null,
            showProceed: false,
            customText: this.data.customText,
            error: null
        };
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        setError: function (err) {
            this.error = err;
        },
        prepareOptions: function () {
            const givenOptions = this.data.options || [];
            this.options = givenOptions.map(option => {
                let returnObject = {};
                returnObject.text = this.$t(`questions.${option.text}`);
                if (option.value === null || option.value === undefined) {
                    returnObject.value = this.$t(`questions.${option.text}`);
                } else {
                    returnObject.value = this.$t(`questions.${option.value}`);
                }
                return returnObject;
            });
        },
        invertCustomSelected: function (e) {
            this.customSelected = !this.customSelected;
            // this.setCustom();
        },
        set: function (e) {
            this.setError(null);
            if (this.selected.includes(e.target.dataset.key)) {
                this.selected = this.selected.filter(
                    value => value !== e.target.dataset.key
                );
            } else {
                this.selected = [e.target.dataset.key, ...this.selected];
            }
            if (this.region === 'uk') {
                this.callAPI();
            }
        },
        callAPI: function () {
            if (this.selected.length < 1) {
                this.setError(this.$t('errors.min-one-selected'));
                return;
            }
            if (this.customSelected) {
                if (this.custom === null || this.custom === '') {
                    this.setError(this.$t('errors.min-one-selected'));
                    return;
                }
            }
            this.setAnswer({'answer': this.selected, 'id': this.data.id});
        },
        isLiteralObject: function (a) {
            return (!!a) && (a.constructor === Object);
        },
        getObjectIndex (arr) {
            let index = null;
            arr.forEach((element, key) => {
                if (this.isLiteralObject(element)) {
                    index = key;
                }
            });
            return index;
        },
        setCustom: function () {
            if (this.custom === null || this.custom === '') this.customSelected = false;
            const removeCustomObject = () => {
                const customIndex = this.getObjectIndex(this.selected);
                if (customIndex) {
                    this.selected = this.selected.filter((ele, key) => key !== customIndex);
                }
            };
            if (this.customSelected) {
                removeCustomObject();
                this.selected = [...this.selected, {custom: this.custom}];
            } else {
                removeCustomObject();
            }
            this.region === 'uk' &&
            this.setAnswer({'answer': this.selected, 'id': this.data.id});
        }
    }
};
</script>
