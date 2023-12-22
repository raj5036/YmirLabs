<template>
<div class='image-div'>
    <div class="question-image">
        <div @click="hidePopupOnClick" class="backdrop" v-if="popupShown">
            <div class="popup">
                    <div class="cross" v-on:click="hidePopup"><svg-cross/></div>
                    <img v-on:click="showPopup"  v-if="savedImage" :src="`${image}`" alt="Uploaded user image">
                    <img v-on:click="showPopup"  v-else :src="image" alt="Uploaded user image">
            </div>
        </div>
        <div v-if="image" class="question-image__image">
            <div class="question-image__image-image">
                <img v-on:click="showPopup"  v-if="savedImage" :src="`${image}`" alt="Uploaded user image">
                <img v-on:click="showPopup"  v-else :src="image" alt="Uploaded user image">
            </div>

            <div class="question-image__actions">
                    <span class="question-image__actions-action" @click="retake" v-html="$t('questions.image.retake')"></span>
            </div>
        </div>
        <div v-else class="image-placeholder" >{{$t(`questions.${data.image_description}`)}}</div>
        <label :style="{width:'100%'}" class="question-image__label" :class="labelClass" :for="'question-image-' + data.id">
            <span class="question-image__button" v-text="$t(`questions.${data.button}`)"></span>
        </label>
        <input class="question-image__input" ref="image" @change="set" type="file" accept="image/*" :id="'question-image-' + data.id">
    <AutoFocus :disabled="(answers[data.id] !== null)"/>
    </div>
    <div v-if="error" class="question-login-password__error-container">
        <img src="@assets/icons/error.svg" title="Snapmed" alt="Snapmed" class="question-login-password__icon">
        <span class="question-login-password__error">{{error !== true ? error : $t('questions.image.error')}}</span>
    </div>
    <button :disabled="loading" @click="callAPI" class="dd-button proceed-button">
        <div v-if="loading"><svg-loader/></div>
        <span v-else>{{$t(`btn.next`)}}</span>
    </button>
</div>
</template>

<script>

// Vuex and store
import { mapActions, mapGetters, mapState } from 'vuex';
import { ACTIONS, GETTERS } from '@/store';
import presignedUrl from '@/s3Config';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'QuestionImage',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    components: {
        AutoFocus
    },
    data () {
        return {
            error: false,
            showProgress: true,
            image: null,
            isSet: false,
            savedImage: false,
            popupShown: false,
            loading: false
        };
    },
    computed: {
        ...mapState(['region']),
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
        showPopup () {
            this.popupShown = true;
        },
        hidePopup () {
            this.popupShown = false;
        },
        hidePopupOnClick (e) {
            if (e.target === e.currentTarget) {
                this.popupShown && this.hidePopup();
            }
        },
        callAPI () {
            // Collect file information from the form data
            const formData = new FormData();
            if (this.$refs['image']['files'][0]) {
                formData.append('image', this.$refs['image']['files'][0]);
            } else {
                if (this.image === null) {
                    this.error = this.$t('questions.image.empty_error');
                    return;
                }
            }
            // Upload the image via the vuex store.
            this.error = false;
            this.loading = true;
            this.upload({file: formData, id: this.data.id})
                .then(async result => {
                    this.loading = false;
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
                });
        },
        async set () {
            this.savedImage = false;
            this.image = await this.$refs['image']['files'][0] ? URL.createObjectURL(this.$refs['image']['files'][0]) : this.image;
        },
        retake () {
            if (this.$refs['image']) {
                this.$refs['image'].click();
            }
        }
    }
};
</script>
