<template>
    <div class="tooltip">
        <span @mouseenter="showText" @mouseleave="hideText" @focus="showText" @blur="hideText" tabindex="0" class="note">{{$t(`questions.skin_select.note`)}}
            <transition name="fade"><div class='info-text' v-if="isTextShown"><span>{{$t(text)}}</span></div></transition>
        </span>
    </div>
</template>

<script>
export default {
    name: 'tooltip',
    props: {
        text: {
            type: String,
            required: true
        }
    },
    data: function () {
        return {
            isTextShown: false
        };
    },
    methods: {
        showText () {
            this.isTextShown = true;
        },
        hideText () {
            this.isTextShown = false;
        },
        invertShown () {
            this.isTextShown = !this.isTextShown;
        }
    }
};
</script>

<style>

</style>
