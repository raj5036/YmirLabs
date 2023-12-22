<template>
    <div class="personal-details-editor">
        <div class="form">
            <div class="table-header"><span class="header">{{$t('questions.personal_details_editor.header')}}</span> <span class="edit-button" @click="invertEdit">edit</span></div>
            <div class="table-content"><span class="label">{{$t('questions.personal_details_editor.name')}}</span><span>{{ full_name }}</span></div>
            <div class="table-content"><span class="label">{{$t('questions.personal_details_editor.birth_date')}}</span> <span>{{ dob }}</span></div>
            <div class="table-content"><span class="label">{{$t('questions.personal_details_editor.gender')}}</span> <span>{{ gender === "man" ? "Male": "Female" }}</span></div>
            <div v-if="gender !== 'man'" class="table-content"><span class="label">{{$t('questions.personal_details_editor.pregnant')}}</span> <span>{{ pregnant }}</span></div>
            <div v-if="gender !== 'man'" class="table-content"><span class="label">{{$t('questions.personal_details_editor.breastfeeding')}}</span> <span>{{ breastfeeding }}</span></div>
            <div class="table-content" v-if="!skinToneHex()"><span class="label">{{$t('questions.personal_details_editor.skin_tone')}}</span> <span><div class="skin-button" :style="{ background: skin_tone }"></div></span></div>
        </div>
        <div class="backdrop" v-if="edit" @click="clearWarning">
            <div class="popup">
                <div class="content" v-for="(question, index) in patientDetails" :key="index">
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
                <div v-if="warning !== null" class="question-login-password__error-container question-large-text__error">
                    <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
                    <span class="question-login-password__error">{{warning}}</span>
                </div>
                <button id="proceed-button" class="proceed-button" @click="save">Save</button>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import { mapActions, mapGetters } from 'vuex';
import { GETTERS, ACTIONS } from '@/store';
import QuestionShortText from '@common/QuestionShortText';
import QuestionOptions from '@common/QuestionOptions';
import DateInput from '@/components/uk/DateInput';
import { patientDetails } from '@/store/questions.js';
import SelectSkinColour from '@/components/uk/SelectSkinColour';

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
            full_name: null,
            dob: null,
            gender: null,
            pregnant: null,
            breastfeeding: null,
            skin_tone: null,
            edit: false,
            warning: null,
            patientDetails
        };
    },
    components: {
        QuestionShortText,
        DateInput,
        QuestionOptions,
        AskQuestion,
        SelectSkinColour
    },
    mounted () {
        this.updateState();
    },
    computed: {
        ...mapGetters({
            answers: GETTERS.ANSWERS
        }),
        age () {
            return this.answers.age && this.answers.age >= 18
                ? 'adult'
                : 'minor';
        },
        who () {
            return this.answers.who;
        }
    },
    methods: {
        ...mapActions({
            saveUserData: ACTIONS.USER_UPDATE
        }),
        skinToneHex () {
            let toReturn = true;
            if (this.skin_tone) {
                if (this.skin_tone[0] === '#') return false;
            }
            return toReturn;
        },
        isWoman () {
            return this.answers['gender'] === 'woman';
        },
        show (question) {
            const isQuestion = question.id === 'breastfeeding' || question.id === 'pregnant';
            const toShow = isQuestion ? this.isWoman() : true;
            return toShow;
        },
        invertEdit () {
            this.edit = !this.edit;
            if (this.edit) { document.body.classList.add('modal-open'); } else { document.body.classList.remove('modal-open'); }
        },
        updateState () {
            this.full_name = this.answers['name'];
            this.dob = moment(this.answers['date_of_birth'], 'YYYY-MM-DD').format('DD/MM/YYYY');
            this.gender = this.answers['gender'];
            this.pregnant = this.answers['pregnant'];
            this.breastfeeding = this.answers['breastfeeding'];
            this.skin_tone = this.answers['ethnicity'];
        },
        emptyState () {
            return this.gender !== 'man' && (this.pregnant === null || this.breastfeeding === null);
        },
        clearWarning (e) {
            if (e.target.id !== 'proceed-button') { this.warning = null; }
        },
        async save () {
            await this.updateState();
            if (this.emptyState()) { this.warning = 'One or more questions yet to be selected'; } else {
                await this.saveUserData();
                this.invertEdit();
            }
        }
    }};
</script>
