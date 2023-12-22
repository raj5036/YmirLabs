<template>
    <div class="personal-details-editor">
        <div class="form">
            <div class="table-header"><span class="header">{{$t('questions.medical_details_editor.header')}}</span> <span class="edit-button" @click="invertEdit">edit</span></div>
            <div class="table-content"><span class="label medical-label">{{$t('questions.medical_details_editor.current_medication')}}</span> <span>{{ $t(`questions.medication.options.${medication}`) }}</span></div>
            <div class="table-content"><span class="label medical-label">{{$t('questions.medical_details_editor.alergy_to')}}</span> <span>{{ $t(`questions.medication.options.${allergy}`) }}</span></div>
            <div class="table-content"><span class="label medical-label">{{$t('questions.medical_details_editor.treatment_for')}}</span> <span>{{ $t(`questions.medication.options.${treatment}`) }}</span></div>
            <div class="table-content"><span class="label medical-label">{{$t('questions.medical_details_editor.skin_conditions')}}</span> <span>{{ $t(`questions.medication.options.${family_history}`) }}</span></div>
        </div>
        <div class="backdrop" v-if="edit">
            <div class="popup" :style="{ 'min-width': '60%' }">
                <div :style="{ 'width': '100%' }" v-for="(question, index) in medicalHistory" :key="index">
                    <template v-if="show(question)">
                        <ask-question
                        v-if="question && question.question"
                        :who="who"
                        :question="question"
                        :age="age"
                        :phoneNumber="partnerPhone"
                        />
                        <component
                            v-bind:is="question.type"
                            :data="question"
                            :age="age"
                        />
                    </template>
                </div>
                <button class="proceed-button" @click="save">Save</button>
            </div>
        </div>
    </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { GETTERS, ACTIONS } from '@/store';
import QuestionLargeText from '@common/QuestionLargeText';
import QuestionOptions from '@common/QuestionOptions';

import { medicalHistory } from '@/store/questions.js';
import AskQuestion from '@common/AskQuestion';

export default {
    name: 'PersonalDetailsEditor',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            medication: null,
            allergy: null,
            treatment: null,
            family_history: null,
            edit: false,
            medicalHistory
        };
    },
    components: {
        QuestionLargeText,
        QuestionOptions,
        AskQuestion
    },
    mounted () {
        this.updateState();
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    methods: {
        ...mapActions({
            saveMedData: ACTIONS.EXAMINATION_UPDATE
        }),
        hasMedical () {
            return this.answers['medication'] === 'yes';
        },
        hasAllergy () {
            return this.answers['allergy'] === 'yes';
        },
        hasTreatment () {
            return this.answers['treatment'] === 'yes';
        },
        hasFamilyHistory () {
            return this.answers['family_history'] === 'yes';
        },
        show (question) {
            switch (question.id) {
                case 'family_history_description':
                    return this.hasFamilyHistory();
                case 'allergy_description':
                    return this.hasAllergy();
                case 'treatment_description':
                    return this.hasTreatment();
                case 'medication_description':
                    return this.hasMedical();
                default:
                    return true;
            }
        },
        invertEdit () {
            this.edit = !this.edit;
            if (this.edit) { document.body.classList.add('modal-open'); } else { document.body.classList.remove('modal-open'); }
        },
        updateState () {
            this.medication = this.answers['medication'];
            this.allergy = this.answers['allergy'];
            this.treatment = this.answers['treatment'];
            this.family_history = this.answers['family_history'];
        },
        async save () {
            await this.updateState();
            await this.saveMedData();
            this.invertEdit();
        }
    }};
</script>
