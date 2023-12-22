<template>
    <div class="question-dropdown">
        <v-select
            class="style-chooser"
            placeholder="Select group"
            :options="data.options"
            :searchable="false"
            @input="set"
            v-model="selected"
            :clearable="false"
        />
    </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

export default {
    name: 'QuestionDropdown',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            selected: null
        };
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    mounted () {
        // Set text to answer
        if (this.answers[this.data.id]) {
            this.selected = this.answers[this.data.id];
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        set (value) {
            this.setAnswer({'answer': value, 'id': this.data.id});
        }
    }
};
</script>

<style lang="scss">
    .question-dropdown{
        width: 55%;
        @include chat-input();
        padding: 0px;
        @include breakpoint(small only) {
            width: 95%;
        }
    }

    .style-chooser .vs__search{
        padding: 10px 15px !important;
    }

    .style-chooser .vs__selected{
        color: #5E4098 !important;
        padding: 10px 15px !important;
        border-radius: 10px;
    }
    .style-chooser .vs__search::placeholder,
    .style-chooser .vs__dropdown-toggle,
    .style-chooser .vs__dropdown-menu {
        background: #FDF1F1;
        border: none;
        color: rgba(39, 17, 45, 0.5);
        text-transform: capitalize;
        font-size: $font-medium;
        font-weight: 400;
        padding: 0px;
        border-radius: 10px;
    }

    li:not(:last-child) { border-bottom: 1px solid rgba(0, 0, 0, 0.05) }

    .vs__dropdown-option{
        color: #5E4098 !important;
        padding: 15px !important;
        font-size: $font-medium;
    }

    .vs__dropdown-option:hover, .vs__dropdown-option--highlight {
        background: #A38BD1 !important;
        color: white !important;
    }

    .style-chooser .vs__clear,
    .style-chooser .vs__open-indicator {
        fill: #5E4098;
        stroke-width: 1 !important;
        margin-right: 15px;
    }
</style>
