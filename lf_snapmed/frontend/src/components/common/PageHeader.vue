<template>
    <div>
        <div class='page-header-no'>
            <img v-if="region === 'no'" src="@assets/logo/dd-logo-dark-no.svg" title="Snapmed" alt="Snapmed" height="50" class="page-header__icon" @click="goToWebsite">
            <img v-else src="@assets/logo/dd-logo-dark-se.svg" title="Snapmed" alt="Snapmed" height="50" class="page-header__icon" @click="goToWebsite">
        </div>
    </div>
</template>

<script>

import { ACTIONS } from '@/store';
import { mapActions, mapState } from 'vuex';

export default {
    name: 'page-header',
    methods: {
        ...mapActions({
            goToWebsite: ACTIONS.GO_TO_WEBSITE
        })
    },
    computed: {
        ...mapState({
            region: state => state.region
        })
    }
};
</script>

<style lang="scss">
    .page-header{
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0px 50px;
        background: #E1F3F3;
        height: 70px;
        @include breakpoint(small only) {
            padding: 0px 15px;
        }
        &__icon{
            cursor: pointer;
            width: 140px;
            height: 120px;
            @include breakpoint(small only) {
                width: 120px;
            }
        }
        &__button{
            height: 38px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #5E4098;
            box-sizing: border-box;
            border-radius: 25px;
            cursor: pointer;
            padding-inline: 14px;
            font-size: $font-small-medium;
            font-weight: 500;
            font-family: Work Sans;
            display: flex;
            column-gap: 8px;
        }
        &__button-icon{
            width: 18px;
        }
    }
    .page-header-no{
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0px 50px;
        background: #4D4557;
        height: 110px;
        @include breakpoint(small only) {
            padding: 0px 15px;
        }
        &__icon{
            cursor: pointer;
            width: 100px;
            @include breakpoint(small only) {
                width: 80px;
            }
        }
    }
    .text{
        color: #43007E;
        font-weight: 500;
        font-size: $font-medium;
        @include breakpoint(small only) {
            font-size: $font-small-medium;
        }
    }
</style>
