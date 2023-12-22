<template>
    <div class="display-diagnoses">
        <transition name="component-fade" mode="out-in">
            <div class="page-respons__result" v-if="selected" key="selected">
                <h1 class="page-respons__title">{{$t('page.response.result.selected')}}</h1>
                <display-result
                    :examination="selected"
                    :back="examinations && examinations.length > 1"
                    @back="deselect"
                    @backAndUpdate="deselectAndUpdate">
                </display-result>
            </div>
            <div class="page-respons__result" v-else-if="examinations" key="examinations">
                <h1 class="page-respons__title">{{$t('page.response.result.multiple')}}</h1>
                <div class="page-respons__list">
                    <display-examination
                        v-for="examination in examinations"
                        :key="examination.uuid"
                        :examination="examination"
                        @click.native.prevent="select(examination)" />
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { isEmpty, isArray, head } from 'lodash';
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

import DisplayResult from '@common/DisplayResult';
import DisplayExamination from '@common/DisplayExamination';

export default {
    components: {
        DisplayResult,
        DisplayExamination
    },
    data () {
        return {
            examinations: false,
            selected: false
        };
    },
    methods: {
        ...mapActions({
            diagnoses: ACTIONS.GET_DIAGNOSES
        }),
        select (examination) {
            this.selected = examination;
        },
        deselect () {
            this.selected = false;
        },
        async deselectAndUpdate () {
            try {
                let result = await this.diagnoses();
                if (result && !isEmpty(result.examinations) && isArray(result.examinations)) {
                    this.examinations = result.examinations;
                    this.selected = false;
                }
            } catch (error) {
                // Let's just ignore this now..
            }
        }
    },
    async mounted () {
        try {
            let result = await this.diagnoses();
            if (result && !isEmpty(result.examinations) && isArray(result.examinations)) {
                this.examinations = result.examinations;
                if (this.examinations.length === 1) {
                    this.selected = head(this.examinations);
                }
            }
        } catch (error) {
            // Let's just ignore this now..
        }
    }
};
</script>

<style lang="scss">
.display-diagnoses {
    width: 100%;
    .component-fade-enter-active, .component-fade-leave-active {
        transition: opacity .3s ease;
    }
    .component-fade-enter, .component-fade-leave-to {
        opacity: 0;
    }
}
</style>
