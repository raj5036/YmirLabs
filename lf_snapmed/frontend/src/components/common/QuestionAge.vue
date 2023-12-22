<template>
    <div class="question-age">
        <div class="question-age__number">
            <input
                inputmode="numeric"
                class="question-age__input"
                :placeholder="agePlaceholder"
                v-model.number="age"
                @input="set">
            <span class="question-age__append">{{$tc('questions.age.append.year', age)}}</span>
        </div>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';

// Assets
import { isFinite, isUndefined } from 'lodash';

const ageLimits = {
    min: 0,
    max: 100
};

export default {
    name: 'QuestionAge',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            age: null
        };
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        }),
        state () {
            // True false if valid
            return !isUndefined(this.age) && isFinite(this.age) && this.age >= ageLimits.min && this.age <= ageLimits.max;
        },
        agePlaceholder () {
            if (this.answers.who === 'other') {
                return 10;
            }
            return 30;
        }
    },
    mounted () {
        // Set answer to age
        this.age = this.answers.age;
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER
        }),
        set () {
            // Set the answer
            if (this.state) {
                this.setAnswer({'answer': this.age, 'id': this.data.id});
            }
        }
    }
};
</script>

<style lang="scss">
.question-age {
    display: flex;
    flex-direction: column;
    &__next {
        // Next btn
        @include fadeInOut(true);
        @include chat-btn;
        align-self: flex-end;
        margin-top: spacing();
    }
    &__number {
        display: flex;
        flex-direction: row;
        flex-flow: nowrap;
        padding: 11px 30px;
        border-radius: 10px;
        background: color(very-light-pink);
        position: relative;
        @include breakpoint(small only) {
            padding: 5px 16px;
        }
    }
    &__input {
        outline: none;
        border: none;
        font-size: $font-medium;
        color: color(blueberry);
        background: transparent;
        width: 40px;
    }
    &__append {
        line-height: 1.6;
        font-size: $font-medium;
    }
}
</style>
