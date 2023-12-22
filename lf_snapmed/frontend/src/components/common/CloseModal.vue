<template lang="">
    <div>
        <div class="button-close" @click="setModalShow"><span>{{$t("btn.close")}}</span></div>
        <section :style="{ top: `${overlayOffset}px`}" v-if="showModal" @click="closeModalOnOutsideClick" class="overlay">
            <div class="modal">
                <div v-if="homepageClicked" class="skip" @click="quit">{{$t('page.close-modal.skip')}}</div>
                <section v-if="!homepageClicked">
                    <span class="description">{{$t('page.close-modal.few-step-away')}}</span>
                    <div class="options">
                        <button @click="setHomePageClickedTrue" class="dd-button-light dd-button">{{$t('page.close-modal.back-homepage')}}</button>
                        <button class="dd-button-dark dd-button" @click="closeModal">{{$t('page.close-modal.complete-booking')}}</button>
                    </div>
                </section>
                <section v-else>
                    <span class="description">{{$t('page.close-modal.your-feedback')}}</span>
                    <textarea  v-model="text" class="dd-input" :placeholder="$t('page.close-modal.how-improve')" />
                    <button class="dd-button button-dark" @click="submit">{{$t('page.close-modal.submit')}}</button>
                </section>
            </div>
        </section>
    </div>
</template>

<script>
// Vuex and store
import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'close-modal',
    data: function () {
        return {
            showModal: false,
            homepageClicked: false,
            overlayOffset: 0,
            text: null
        };
    },
    methods: {
        ...mapActions({
            submitFeedback: ACTIONS.SUBMIT_FEEDBACK,
            quit: ACTIONS.GO_TO_WEBSITE
        }),
        async submit () {
            await this.submitFeedback({'feedback': this.text});
        },
        setModalShow: function () {
            this.showModal = true;
            const scroll = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.width = '100vw';
            document.body.style.top = `-${scroll}px`;
            this.overlayOffset = scroll;
        },
        closeModalOnOutsideClick: function (e) {
            if (e.target === e.currentTarget) {
                this.closeModal();
            }
        },
        closeModal: function () {
            const scrollY = document.body.style.top;
            document.body.style.position = '';
            document.body.style.width = 'unset';
            document.body.style.top = '';
            window.scrollTo(0, parseInt(scrollY || '0') * -1);
            this.showModal = false;
            this.homepageClicked = false;
        },
        setHomePageClickedTrue: function () {
            this.homepageClicked = true;
        }
    }
};
</script>
<style lang="">
