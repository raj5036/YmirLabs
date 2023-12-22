<template>
    <div class="question-short-text-uk">
        <div v-if="error" class="question-login-password__error-container question-large-text__error">
            <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
            <span class="question-login-password__error">{{$t('page.check.empty_error')}}</span>
        </div>
        <textarea
            :placeholder="$t('questions.name.placeholder')"
            v-model="text"
            @input="set"
            @focus="set"
            class="question-short-text__area">
        </textarea>
        <section v-if="showResults" class="results">
            <ul>
                <li v-for="(res,index) in searchResults" v-on:click="setVal" :key="index" :data-opt="res.item" >{{res.item}}</li>
            </ul>
        </section>
    </div>
</template>

<script>

// Answer
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import _ from 'lodash';
import Fuse from 'fuse.js';

export default {
    name: 'Search',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    computed: {
        ...mapState(['region']),
        ...mapGetters({
            isPartnerFlow: GETTERS.IS_PARTNER_FLOW_ACTIVE,
            answers: GETTERS.ANSWERS
        }),
        ...mapState({
            isPartnerVideoFlow: state => ((state.partner && state.partner.videoFlow) || false)
        })
    },
    data () {
        return {
            text: null,
            error: false,
            showResults: false,
            searchResults: []
        };
    },
    mounted () {
        // Set text to answer
        if (this.answers[this.data.id]) {
            this.text = this.answers[this.data.id];
        }
    },
    methods: {
        ...mapActions({
            setAnswer: ACTIONS.UPDATE_ANSWER,
            updatePartner: ACTIONS.UPDATE_PARTNER
        }),
        search () {
            const list = ['army', 'come', 'eye', 'five', 'fur'];
            const pattern = this.text || '';
            const fuse = new Fuse(list, {});
            let result;
            if (pattern === '' || pattern === undefined) { result = fuse._docs.map((item) => ({item, matches: [], score: 1})); } else { result = fuse.search(pattern); }
            this.searchResults = result;
        },
        set () {
            this.showResults = false;
            const fetchResults = () => {
                // fetchResuts
                this.search();
                this.showResults = true;
            };

            const debounceFunc = _.debounce(fetchResults, 300);
            debounceFunc();
        },
        setVal (e) {
            const selectedOption = e.target.dataset.opt;
            if (selectedOption !== null && selectedOption !== '') {
                this.error = false;
                this.setAnswer({'answer': selectedOption, 'id': this.data.id});
                this.text = selectedOption;
                this.showResults = false;
            } else {
                this.error = true;
                this.setAnswer({'answer': null, 'id': this.data.id});
            }
        }
    }
};
</script>
<style lang="scss">
.question-short-text-uk{
    position: relative;
}
.results{
    width: 300px;
    border: 1px solid $uk-border-color;
    border-radius: 5px;
    position: absolute;
    background: white;
    top: 50px;
    left: 10px;
    ul{
        list-style-type: none;
        max-height: 150px;
        overflow-y: scroll;
        li{
            padding: 10px;
            border-bottom: 1px solid $uk-border-color;
        }
    }
}
</style>
