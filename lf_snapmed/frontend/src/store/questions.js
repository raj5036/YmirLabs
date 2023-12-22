import { filter, pickBy, has, isEmpty, transform, isNull, find } from 'lodash';

// setting this to false will enable you to go directly to payment for debugging
const isRequired = true; // process.env.NODE_ENV === 'production';
const region = process.env.VUE_APP_SITE;

export const auroraVideoFlow = [
    {
        id: 'bankid',
        required: true,
        type: 'QuestionBankID',
        question: 'login.bankid'
    },
    {
        id: 'email',
        required: false,
        title: 'questions.title.verify',
        type: 'QuestionLoginEmail',
        question: 'login.email'
    },
    {
        id: 'phone',
        required: true,
        type: 'QuestionLoginPhone',
        question: 'login.phone'
    },
    {
        id: 'key',
        required: true,
        type: 'LoginKeyUK',
        question: 'login.key'
    },
    {
        id: 'description',
        required: isRequired,
        category: 'common', // TODO: what does this do? can we remove?
        type: 'QuestionText',
        question: 'partner.video.description',
        placeholder: ''
    }
];

export const videoQuestionFlow = [
    {
        id: 'videoSlot',
        required: true,
        title: 'questions.title.video_booking',
        type: 'VideoBook',
        question: 'video.question'
    },
    {
        id: 'description',
        required: isRequired,
        title: 'questions.title.medical_issue',
        category: 'common',
        type: 'QuestionLargeText',
        question: 'description.question',
        placeholder: 'description.placeholder'
    },
    {
        id: 'bankid',
        required: false,
        title: 'questions.title.auth',
        type: 'QuestionBankID',
        question: 'login.bankid'
    },
    {
        id: 'phone',
        required: true,
        title: 'questions.title.verify',
        type: 'QuestionLoginPhone',
        question: 'login.phone'
    },
    {
        id: 'key',
        required: true,
        type: 'LoginKeyUK',
        title: 'questions.title.verify',
        question: 'login.key'
    },
    {
        id: 'email',
        required: false,
        title: 'questions.title.verify',
        type: 'QuestionLoginEmail',
        question: 'login.email'
    },
    {
        id: 'token',
        required: false,
        title: 'questions.title.payment',
        type: 'QuestionPaymentNew',
        question: 'payment.info.question'
    }
];

export const questionFlow = [
    {
        id: 'who',
        title: 'questions.title.patient_details',
        required: isRequired,
        category: 'common',
        type: 'QuestionOptions',
        question: 'who.question',
        options: ['who.options.me', 'who.options.other'],
        hints: {1: 'who.hints.other'}
    },
    {
        id: 'date_of_birth',
        title: 'questions.title.patient_details',
        required: isRequired,
        category: 'common',
        type: 'DateInput',
        question: 'age.question'
    },
    {
        id: 'gender',
        required: isRequired,
        title: 'questions.title.patient_details',
        category: 'common',
        type: 'QuestionOptions',
        question: 'gender.question',
        options: ['gender.options.man', 'gender.options.woman', 'gender.options.prefer_not']
    },
    ...(['no', 'se'].includes(region)
        ? [
            {
                id: 'child_ssn',
                required: false,
                title: 'questions.title.patient_details',
                category: 'common',
                type: 'QuestionChildSsn',
                question: 'child_ssn.question'
            }
        ]
        : []),

    {
        id: 'pregnant',
        required: false,
        title: 'questions.title.patient_details',
        category: 'common',
        type: 'QuestionOptions',
        question: 'pregnant.question',
        options: ['pregnant.options.yes', 'pregnant.options.no']
    },
    {
        id: 'breastfeeding',
        required: false,
        title: 'questions.title.patient_details',
        category: 'common',
        type: 'QuestionOptions',
        question: 'breastfeeding.question',
        options: ['breastfeeding.options.yes', 'breastfeeding.options.no']
    },
    {
        id: 'duration',
        required: isRequired,
        category: 'common',
        title: 'questions.title.medical_issue',
        question: 'duration.question',
        type: 'SingleSelect',
        options: [
            { text: 'duration.week' },
            { text: 'duration.6_week' },
            { text: 'duration.more_6_week' },
            { text: 'duration.year' }
        ],
        custom: false
    },
    {
        id: 'body_part',
        required: isRequired,
        title: 'questions.title.medical_issue',
        category: 'common',
        type: 'MultiSelect',
        question: 'body_part.question',
        options: [
            { text: 'body_part.scalp' },
            { text: 'body_part.face' },
            { text: 'body_part.chest' },
            { text: 'body_part.arms' },
            { text: 'body_part.legs' }
        ],
        customText: 'body_part.others',
        placeholder: 'body_part.placeholder'
    },
    {
        id: 'description',
        required: isRequired,
        title: 'questions.title.medical_issue',
        category: 'common',
        type: 'MultiSelect',
        question: 'description.question',
        options: [
            { text: 'description.itch' },
            { text: 'description.burn' },
            { text: 'description.bleed' },
            { text: 'description.none' }
        ],
        customText: 'description.others',
        placeholder: 'description.placeholder'
    },
    {
        id: 'medication',
        required: isRequired,
        title: 'questions.title.about_you',
        category: 'common',
        type: 'QuestionOptions',
        question: 'medication.question',
        options: ['medication.options.yes', 'medication.options.no']
    },
    {
        id: 'medication_description',
        required: false,
        title: 'questions.title.about_you',
        category: 'common',
        type: 'QuestionLargeText',
        question: 'medication_description.question',
        placeholder: 'medication_description.placeholder'
    },
    {
        id: 'allergy',
        required: isRequired,
        category: 'common',
        title: 'questions.title.about_you',
        type: 'QuestionOptions',
        question: 'allergy.question',
        options: ['allergy.options.yes', 'allergy.options.no']
    },
    {
        id: 'allergy_description',
        required: false,
        category: 'common',
        title: 'questions.title.about_you',
        type: 'QuestionLargeText',
        question: 'allergy_description.question',
        placeholder: 'allergy_description.placeholder'
    },
    {
        id: 'closeup',
        required: false,
        title: 'questions.title.take_photo',
        category: 'common',
        type: 'QuestionImageUK',
        question: 'imagecloseup.question',
        image_description: 'imagecloseup.description',
        button: 'imagecloseup.button'
    },
    {
        id: 'overview',
        required: isRequired,
        title: 'questions.title.take_photo',
        category: 'common',
        type: 'QuestionImageUK',
        question: 'imageoverview.question',
        image_description: 'imageoverview.description',
        button: 'imageoverview.button'
    },
    {
        id: 'bankid',
        title: 'questions.title.auth',
        required: true,
        type: 'QuestionBankID',
        question: 'login.bankid'
    },
    {
        id: 'phone',
        required: true,
        title: 'questions.title.verify',
        type: 'QuestionLoginPhone',
        question: 'login.phone'
    },
    {
        id: 'key',
        required: true,
        title: 'questions.title.verify',
        type: 'LoginKeyUK',
        question: 'login.key'
    },
    {
        id: 'email',
        required: false,
        title: 'questions.title.verify',
        type: 'QuestionLoginEmail',
        question: 'login.email'
    },
    {
        id: 'token',
        required: true,
        title: 'questions.title.payment',
        type: 'QuestionPaymentNew',
        question: 'payment.info.question'
    }
];

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Class that is exported and used elsewhere in store.

class Questions {
    examinationAnswerTpl (answers) {
        const filtered = filter(questionFlow, question => {
            return (
                question.category === 'common' ||
                question.category === answers.category
            );
        });
        return transform(
            filtered,
            (answerTpl, question) => (answerTpl[question.id] = null),
            {}
        );
    }

    examination (flow, answers, partnerMode, partnerCovered, isAuroraFlow) {
        return this._buildQuestionFlow(flow, answers, partnerMode, partnerCovered, isAuroraFlow);
    }

    examinationIsValid (answers, flow) {
        if (!flow) {
            flow = questionFlow;
        }
        const required = filter(answers, (value, key) => {
            const q = find(flow, { id: key });
            return q && q.required && isNull(value);
        });
        return isEmpty(required);
    }

    auroraPartnerVideo (answers) {
        return this._buildQuestionFlow(auroraVideoFlow, answers);
    }

    _buildQuestionFlow (questions, answers, partnerActive, partnerCovered, isAuroraFlow) {
        // Create an object with only the completed answers
        const answered = pickBy(answers, answer => {
            return answer !== null;
        });
        // Lets build an array of all the questions answered and the first unanswered.
        const results = [];
        for (const question of questions) {
            let addedQuestion = false;
            let hasNoAnswer = !has(answered, question.id);
            // Add the question to the array.
            if (question.id === 'child_ssn') {
                if (answered.who === 'other') {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'medication_description') {
                if (answered.medication === 'yes') {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'allergy_description') {
                if (answered.allergy === 'yes') {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'treatment_description') {
                if (answered.treatment === 'yes') {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'family_history_description') {
                if (answered.family_history === 'yes') {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'confirmPassword') {
                if (!answered.userPasswordAlreadySet) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (
                question.id === 'pregnant' ||
                question.id === 'breastfeeding'
            ) {
                if (
                    (answered.gender === 'woman' || answered.gender === 'prefer_not') &&
                    answered.age >= 15 &&
                    answered.age <= 50
                ) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'bankid') {
                if (!answered.bankid && !partnerActive) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'phone') {
                if (hasNoAnswer || !has(answered, 'key')) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'key') {
                if (hasNoAnswer) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else if (question.id === 'token') {
                if (!partnerCovered && hasNoAnswer) {
                    results.push(question);
                    addedQuestion = true;
                }
            } else {
                // skip 'who' question for partner (Bli Frisk) users
                if (!partnerActive || question.id !== 'who') {
                    results.push(question);
                    addedQuestion = true;
                }
            }
            // If we added a question, and it does not have an answer yet, we have reached our final destination for now..
            if (addedQuestion && hasNoAnswer) {
                console.log(question.id);
                break;
            }
        }
        return results;
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
export let questions = new Questions();

export default questions;
