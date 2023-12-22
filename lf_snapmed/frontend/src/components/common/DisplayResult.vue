<template>
    <section class="result">
        <img
            class="result__illustration"
            src="@assets/illustrations/woman-with-documents.svg"
        />

        <div class="result__content">
            <template v-if="diagnosis">
                <div class="result__feedback">
                    <q
                        class="larger-text result__feedback-quote result__white-space"
                        :class="{
                            'result__feedback-space':
                                examination.referrals.length > 0
                        }"
                        >{{ diagnosis.description }}</q
                    >

                    <p class="prescription-subtext" v-if="showSubtext">
                        Your prescription has been sent automatically to our
                        online partner pharmacy, who will shortly e-mail you a
                        paylink, and post your medication to you directly
                    </p>
                    <transition name="fade">
                        <div v-if="processing" class="result__processing">
                            <svg-loader />
                        </div>
                    </transition>
                    <div
                        class="result__referral"
                        v-if="examination.referrals.length > 0"
                    >
                        <button
                            class="button button--light button--tight button--small button--space-around button--outline-none"
                            v-for="referral in examination.referrals"
                            :key="referral.uuid"
                            @click="openReferralModal(referral)"
                        >
                            {{ referral.name }}
                        </button>
                    </div>
                </div>
            </template>
            <template v-else-if="!diagnosis && !examination.complete">
                <h3 class="result__new-images">
                    {{ examination.reject_reason }}
                </h3>
                <div class="result__new-images">
                    {{ $t('page.results.upload') }}
                </div>
                <div
                    class="image-container"
                    :class="{ 'image-container--complete': isCloseupUploaded }"
                >
                    <ask-question
                        :who="examination.who"
                        :question="images.closeup"
                        :age="examination.age"
                    />
                    <div class="chat-question__answer-container">
                        <question-image
                            :data="images.closeup"
                            @uploaded="uploaded"
                        />
                    </div>
                </div>
                <div
                    class="image-container"
                    :class="{ 'image-container--complete': isOverviewUploaded }"
                >
                    <ask-question
                        :who="examination.who"
                        :question="images.overview"
                        :age="examination.age"
                    />
                    <div class="chat-question__answer-container">
                        <question-image
                            :data="images.overview"
                            @uploaded="uploaded"
                        />
                    </div>
                </div>
                <transition name="fade">
                    <button
                        v-if="areImagesUploaded"
                        class="examination__action-button button button--light button--tight"
                        @click="submit"
                    >
                        {{ $t('page.results.update') }}
                    </button>
                </transition>
            </template>
            <template v-else>
                <p class="larger-text result__not-ready">
                    {{ $t('page.results.not_ready') }}
                </p>
            </template>
        </div>
        <div class="result__description">
            <div class="result__description-content">
                <h2 class="result__description-title">
                    {{ $t('page.results.description') }}
                </h2>
                <p class="result__description-text result__white-space">
                    {{ description }}
                </p>
            </div>
            <div class="result__description-images">
                <figure
                    class="image image--closeup"
                    v-for="closeup in images.closeup.url"
                    :key="closeup"
                >
                    <img
                        :src="closeup"
                        :title="$t('page.results.imagecloseup_alt')"
                        :alt="$t('page.results.imagecloseup_alt')"
                    />
                    <figcaption>
                        {{ $t('page.results.imagecloseup') }}
                    </figcaption>
                </figure>
                <figure
                    class="image image--overview"
                    v-for="overview in images.overview.url"
                    :key="overview"
                >
                    <img
                        :src="overview"
                        :title="$t('page.results.imageoverview_alt')"
                        :alt="$t('page.results.imageoverview_alt')"
                    />
                    <figcaption>
                        {{ $t('page.results.imageoverview') }}
                    </figcaption>
                </figure>
            </div>
        </div>
        <div class="result__navigate">
            <div class="result__nav result__nav-start">
                <a
                    v-if="back"
                    href
                    @click.prevent.stop="goBack"
                    class="nav-link"
                >
                    <svg-asset icon="chevron-left" />&nbsp;{{
                        $t('page.response.cta.back')
                    }}
                </a>
            </div>
            <div class="result__nav result__nav-end">
                <button
                    class="button button--outline  button--tight button--small"
                    v-if="canAskForSecondOpinion"
                    @click="showModal = true"
                >
                    {{ $t('page.response.cta.second_opinion') }}
                </button>
            </div>
        </div>
        <transition name="fade">
            <div class="modal-overlay" v-if="showModal">
                <div class="modal-card">
                    <div class="modal-card__title">
                        {{ $t('page.response.modal.title') }}
                    </div>
                    <div class="modal-card__content">
                        <textarea
                            :placeholder="$t('page.response.modal.grounds')"
                            v-model="grounds"
                            class="content__grounds"
                        ></textarea>
                    </div>
                    <div class="modal-card__actions">
                        <button
                            class="button button--light button--space-top button--tight button--small"
                            @click="showModal = false"
                        >
                            {{ $t('page.response.modal.cta.cancel') }}
                        </button>
                        <button
                            class="button button--outline button--space-top button--tight button--small"
                            v-if="canAskForSecondOpinion"
                            :disabled="!isValidForSecondOpinion"
                            @click="secondOpinion"
                        >
                            {{ $t('page.response.cta.second_opinion') }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
        <transition name="fade">
            <div class="modal-overlay" v-if="showReferralModal">
                <div class="modal-card">
                    <div class="modal-card__title">{{ referral.name }}</div>
                    <div class="modal-card__content result__description-images">
                        <!-- <figure class="image">
                            <img :src="src" style="height:250px;max-width: 100%">
                        </figure> -->
                        <embed
                            :src="src"
                            style="height:250px;max-width: 100%"
                        />
                    </div>
                    <div class="modal-card__actions">
                        <button
                            class="button button--light button--space-top button--tight button--small"
                            @click="showReferralModal = false"
                        >
                            {{ $t('page.response.modal.cta.cancel') }}
                        </button>
                        <button
                            class="button button--outline button--space-top button--tight button--small"
                            @click="
                                downloadReferralFromData(referral)
                            "
                        >
                            {{ $t('page.response.modal.cta.download') }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </section>
</template>

<script>
import { mapActions, mapState } from 'vuex';
import { ACTIONS } from '@/store';
import { has, isEmpty, head, size, trim } from 'lodash';

import AskQuestion from '@common/AskQuestion';
import QuestionImage from '@common/QuestionImage';
import presignedUrl from '@/s3Config';

export default {
    name: 'DisplayResult',
    props: {
        examination: {
            type: Object,
            required: true
        },
        back: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            showModal: false,
            showReferralModal: false,
            processing: false,
            referral: {},
            src: null,
            grounds: '',
            images: {
                closeup: {
                    id: 'closeup',
                    question: 'imagecloseup.question',
                    button: 'imagecloseup.button',
                    url: ''
                },
                overview: {
                    id: 'overview',
                    question: 'imageoverview.question',
                    button: 'imageoverview.button',
                    url: ''
                }
            }
        };
    },
    methods: {
        ...mapActions({
            updateImages: ACTIONS.UPDATE_IMAGES,
            submitSecondOpinion: ACTIONS.SECOND_OPINION
        }),
        async openReferralModal (referral) {
            this.processing = true;
            this.referral = referral;
            this.src = await presignedUrl(`${referral.uuid}.${referral.suffix}`, 'referrals/');
            this.processing = false;
            this.showReferralModal = true;
        },
        async downloadReferralFromData (referral) {
            // // For other browsers:
            // // Create a link pointing to the ObjectURL containing the blob.
            const data = await presignedUrl(`${referral.uuid}.${referral.suffix}`, 'referrals/');
            let link = document.createElement('a');
            link.href = data;
            link.download = referral.name;
            link.click();
            setTimeout(function () {
                // For Firefox it is necessary to delay revoking the ObjectURL
                window.URL.revokeObjectURL(data);
            }, 100);
        },
        uploaded (image) {
            if (image && this.images[image.id]) {
                this.$set(this.images[image.id], 'uuid', image.uuid);
            } else {
                window.alert('There was an error with uploading the image');
            }
        },
        async submit () {
            const data = await this.updateImages({
                uuid: this.examination.uuid,
                closeup: this.images.closeup.uuid,
                overview: this.images.overview.uuid
            });
            if (data && data.examination) {
                this.examination = data.examination;
            }
        },
        async secondOpinion () {
            try {
                await this.submitSecondOpinion({
                    uuid: this.examination.uuid,
                    diagnosis: this.diagnosis.uuid,
                    reason: trim(this.grounds)
                });
            } catch (error) {
                // We don't do anything with the error at this point.
            }
            this.$emit('backAndUpdate');
        },
        goBack () {
            this.$emit('back');
        }
    },
    computed: {
        ...mapState(['region']),
        isCloseupUploaded () {
            return (
                this.images && this.images.closeup && !!this.images.closeup.uuid
            );
        },
        showSubtext () {
            return this.region === 'uk' && this.diagnosis.is_prescribed;
        },
        isOverviewUploaded () {
            return (
                this.images &&
                this.images.overview &&
                !!this.images.overview.uuid
            );
        },
        areImagesUploaded () {
            return this.isCloseupUploaded && this.isOverviewUploaded;
        },
        diagnosis () {
            if (
                this.examination &&
                this.examination.diagnoses &&
                !isEmpty(this.examination.diagnoses)
            ) {
                return head(this.examination.diagnoses);
            }
            return false;
        },
        canAskForSecondOpinion () {
            return (
                this.region !== 'uk' &&
                this.examination &&
                this.examination.diagnosed &&
                this.diagnosis &&
                size(this.examination.diagnoses) < 2
            );
        },
        isValidForSecondOpinion () {
            return size(trim(this.grounds)) > 5;
        },
        description () {
            if (this.examination) {
                if (this.examination.category === 'mole') {
                    return this.examination.mole_description;
                } else if (this.examination.category === 'rash') {
                    return this.examination.rash_description;
                } else if (this.examination.category === 'skin_cancer') {
                    return this.examination.skin_cancer_description;
                } else {
                    return this.examination.other_description || '';
                }
            }
            return '';
        },
        duration () {
            return has(this.examination, 'duration')
                ? this.$t(
                    'questions.duration.options.' + this.examination.duration
                )
                : null;
        }
    },
    mounted () {
        if (this.examination && this.examination.closeups) {
            this.images.closeup.url = [];
            this.examination.closeups.forEach(async (closeup) => {
                let url = await presignedUrl(`${closeup.uuid}.${closeup.suffix}`, 'uploads/');
                this.images.closeup.url.push(url);
            });
        }
        if (this.examination && this.examination.overviews) {
            this.images.overview.url = [];
            this.examination.overviews.forEach(async (overview) => {
                let url = await presignedUrl(`${overview.uuid}.${overview.suffix}`, 'uploads/');
                this.images.overview.url.push(url);
            });
        }
    },
    watch: {
        region (value) {
            console.log('watch region', value);
        }
    },
    components: {
        AskQuestion,
        QuestionImage
    }
};
</script>

<style lang="scss">
.result {
    $img_size: 180px;
    display: flex;
    flex-flow: row wrap;
    align-items: center;
    color: color(black);
    &__illustration {
        // Illustration
        width: 100%;
        max-width: $img_size;
        align-self: flex-end;
        @include breakpoint(medium down) {
            order: 3;
            margin: 0 auto -30px;
        }
    }
    &__processing {
        background: rgba(color(white), 0.4);
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1000;
        height: 50px;
    }
    &__content {
        // Content
        padding-left: 40px;
        text-align: left;
        width: 100%;
        .question-ask {
            font-size: $font-medium;
        }
        @include breakpoint(large) {
            width: calc(100% - #{$img_size});
            .question-ask {
                &::before {
                    content: none;
                }
            }
        }
        @include breakpoint(medium down) {
            text-align: left;
            padding-left: 0;
        }
        .image-container {
            margin-bottom: spacing(2);
            &--complete * {
                opacity: 0.8;
            }
        }
        .examination__action-button {
            float: right;
            margin-top: spacing(2);
        }
    }
    &__not-ready {
        // Title when result is not in
        color: color(blueberry);
    }
    &__description {
        // Description
        // $width: 70px;
        display: flex;
        flex-flow: row wrap;
        width: 100%;
        padding: spacing(4) spacing(2);
        @include breakpoint(medium down) {
            padding: spacing(3) 0;
        }
        border-top: 1px solid rgba(22, 75, 76, 0.21);
        &-content {
            // Content
            text-align: left;
            flex: 1 1 50%;
            @include breakpoint(medium down) {
                text-align: center;
                flex: 1 1 100%;
            }
        }
        &-title {
            // Title
            font-weight: bold;
            color: color(blueberry);
        }
        &-images {
            flex: 1 1 50%;
            display: flex;
            flex-flow: row wrap;
            justify-content: space-around;
            .image {
                flex: 1 1 50%;
                margin: 0;
                padding: spacing(1);
                @include breakpoint(medium down) {
                    flex: 1 1 100%;
                }
                img {
                    object-fit: contain;
                }
            }
        }
    }
    &__navigate {
        align-self: flex-end;
        display: flex;
        justify-content: space-between;
        width: 100%;
        .button:focus {
            outline: none;
        }
        @include breakpoint(medium down) {
            justify-content: center;
            margin-bottom: spacing(3);
        }
    }
    &__nav {
        width: 50%;
        display: flex;
        align-items: center;

        &-start {
            justify-content: flex-start;
        }
        &-end {
            justify-content: flex-end;
        }
    }
    &__score {
        // Score
        $height: 43px;
        display: flex;
        margin-bottom: 35px;
        flex-direction: row;
        font-size: $font-medium;
        @include breakpoint(medium down) {
            flex-direction: column;
            align-items: center;
            font-size: $font-small-medium;
        }
        // Icons
        &:before {
            content: '';
            width: $height;
            height: $height;
            display: flex;
            flex-shrink: 0;
            border-radius: 50%;
            margin-right: 13px;
            background-color: color(blueberry);
            background-repeat: no-repeat;
            background-size: 35px;
            background-position: center;
            @include breakpoint(medium down) {
                margin-bottom: 10px;
            }
        }
        // Icon colors
        &--ok:before {
            background-color: #2bbe73;
            //background-image: url('~@assets/result/check.svg');
        }
        &--warn:before {
            background-color: #de6d25;
        }
        &--check:before {
            background-color: #b6312d;
        }
    }
    &__feedback {
        display: flex;
        flex-direction: column;
    }
    &__feedback-quote {
        // Fedback text
        quotes: '«' '»' '‹' '›';
    }
    &__feedback-space {
        margin-top: spacing(11);
    }
    &__new-images {
        margin-bottom: 20px;
    }
    &__white-space {
        white-space: pre-wrap;
    }
    .modal-overlay {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: color(black, 0.4);
    }
    .modal-card {
        background: color(white);
        padding: spacing(2);
        &__title {
            padding-bottom: spacing();
        }
        &__content {
            .content__grounds {
                max-width: 500px;
                min-width: 350px;
                width: 100%;
                padding: spacing();
                border-radius: spacing(0.5);
                outline: none;
                resize: none;
                // background: #fdf1f1;
                color: color(blueberry);
                border: 1px solid #ded4db;
                font-size: $font-medium;
                min-height: 200px;
            }
        }
        &__actions {
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding-top: spacing();
            .button {
                outline: none;
                transition: background-color 500ms ease-in-out;
                &:disabled {
                    cursor: not-allowed;
                    background-color: rgba(lightgray, 0.5);
                    color: color(black, 0.8);
                }
            }
        }
    }
    &__referral {
        display: flex;
        flex-direction: row;
        margin-top: spacing(6);
    }
}
.prescription-subtext {
    padding-block: 0.5rem;
}
</style>
