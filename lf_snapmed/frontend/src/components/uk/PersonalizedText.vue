<template>
    <div class="personalised-text">
        <p v-if="name">Hey {{name}},</p>
        <p v-else> Hey there,</p>
        <span>{{$t(`questions.${data.text}`)}}</span>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { GETTERS } from '@/store';

export default {
    name: 'PersonalizedText',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            name: null
        };
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    async mounted () {
        await this.answers;
        // Set text to answer
        if (this.answers && this.answers['name']) {
            this.name = this.answers['name'];
        }
    }
};
</script>
