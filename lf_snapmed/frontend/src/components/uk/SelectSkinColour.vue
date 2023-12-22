<template>
    <div class="w-100">
        <!-- <label>something</label>
        <pre>{{ selected }}</pre> -->
        <section class="skin-options-list">
            <div
                v-for="(option, index) in options"
                v-on:click="click"
                :data-key="String(option.value)"
                :class="selected === String(option.value) ? 'skin-button selected-skin' : 'skin-button'"
                :style="{'background-color': option.colour}"
                :key="index"
            />
        </section>
        <Tooltip text="questions.skin_select.note_detail"/>
        <AutoFocus :disabled="(answers[data.id] !== null)"/>
    </div>
</template>
<script>
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import AutoFocus from '@common/AutoFocus';
import Tooltip from '@/components/uk/Tooltip';

export default {
    name: 'SelectSkinColour',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    components: {
        AutoFocus,
        Tooltip
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        // Set text to answer
        this.selected = this.answers[this.data.id] || null;
        this.prepareOptions();
    },
    data: function () {
        return {
            options: null,
            selected: null
        };
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        prepareOptions: function () {
            this.options = this.data.options.map(option => {
                let returnObject = {};
                returnObject.colour = option.colour;
                if (option.value === null || option.value === undefined) {
                    returnObject.value = option.colour;
                } else {
                    returnObject.value = option.value;
                }
                return returnObject;
            });
        },
        click: function (e) {
            this.selected = e.target.dataset.key;
            this.setAnswer({'answer': this.selected, 'id': this.data.id});
        }
    }
};
</script>
