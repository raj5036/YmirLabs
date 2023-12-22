<template>
    <div class="question-image">
        <label class="question-image__label" :class="labelClass" :for="'question-image-' + data.id">
            <span class="question-image__button" v-text="$t(`questions.${data.button}`)"></span>
        </label>
        <input class="question-image__input" ref="image" @change="set" type="file" accept="image/*" :id="'question-image-' + data.id">

        <div v-if="image" class="question-image__image">
            <div class="question-image__image-image">
                <img v-if="savedImage" :src="`${image}`" alt="Uploaded user image">
                <img v-else :src="image" alt="Uploaded user image">
                <span v-if="showProgress" class="question-image__image-progress-bar" :style="{width: progress + '%'}"></span>
            </div>
            <div v-if="error" class="question-login-password__error-container">
                <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
                <span class="question-login-password__error">{{$t('questions.image.error')}}</span>
            </div>
            <div class="question-image__actions">
                <span class="question-image__actions-action" @click="retake" v-html="$t('questions.image.retake')"></span>
            </div>
        </div>
    </div>
</template>

<script>

// Vuex and store
import { mapActions, mapGetters } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import presignedUrl from '@/s3Config';

export default {
    name: 'QuestionImage',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            error: false,
            showProgress: true,
            progress: 0,
            image: null,
            isSet: false,
            savedImage: false
        };
    },
    computed: {
        labelClass () {
            return this.image ? 'question-image__label--hide' : '';
        },
        setActionClass () {
            // return this.isSet ? 'question-image__actions-action--disabled' : '';
            return this.isSet ? 'question-image__actions-action--selected' : '';
        },
        ...mapGetters({
            answers: GETTERS.ANSWERS
        })
    },
    async mounted () {
        await this.answers;
        if (this.answers && this.answers[this.data.id]) {
            this.image = await presignedUrl(this.answers[this.data.id], 'uploads/');
            this.savedImage = true;
        }
    },
    methods: {
        ...mapActions({
            upload: ACTIONS.UPLOAD_IMAGE
        }),
        set () {
            this.savedImage = false;
            this.image = this.$refs['image']['files'][0] ? URL.createObjectURL(this.$refs['image']['files'][0]) : this.image;
            // Collect file information from the form data
            const formData = new FormData();
            formData.append('image', this.$refs['image']['files'][0]);
            // Upload the image via the vuex store.
            this.error = false;
            this.showProgress = true;
            const callback = (value) => {
                this.progress = value;
            };
            this.upload({file: formData, id: this.data.id, callback})
                .then(async result => {
                    console.log(result);
                    if (result && !result.error) {
                        // Update the successfull upload of the image.
                        this.isSet = true;
                        this.data.done = true;
                        this.image = await presignedUrl(result.uuid, 'uploads/');
                        this.savedImage = true;
                        this.$emit('uploaded', result);
                    } else if (result && result.error) {
                        this.isSet = false;
                        this.data.done = false;
                        this.error = true;
                    }
                    this.showProgress = false;
                });
        },
        retake () {
            if (this.$refs['image']) {
                this.$refs['image'].click();
            }
        }
    }
};
</script>

<style lang="scss">
.question-image {
    display: flex;
    flex-direction: column;
    user-select: none;
    max-width: 100%;
    &__button {
        @include chat-btn;
        // width: 100%;
    }
    &__label {
        display: flex;
        flex-direction: column;
        align-items: center;
        align-self: flex-end;
        // min-width: 200px;
        &--hide {
            @include fadeInOut(false);
        }
    }
    &__text {
        font-size: $font-medium;
    }
    &__input {
        display: none;
    }
    &__image {
        max-width: 100%;
        width: 400px;
        display: flex;
        flex-direction: column;
        align-content: center;
        overflow: hidden;
        @include fadeInOut(true);
        &-image {
            border-radius: 31px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
    }
    &__image-progress-bar {
        margin-top: -31px;
        height: 31px;
        background: $success-color;
        transition: width 300ms ease-in-out;
    }
    &__image-error {
        white-space: pre;
        text-align: center;
        padding: 10px 0;
    }
    &__actions {
        display: flex;
        flex-flow: row wrap;
        justify-content: flex-start;
        width: 100%;
        margin-top: spacing();
        &-action {
            @include chat-btn;
        }
    }
    &__margin {
        margin-right: 20px;
        @include breakpoint(small only) {
                margin-right: 10px;
            }
    }
}
</style>
