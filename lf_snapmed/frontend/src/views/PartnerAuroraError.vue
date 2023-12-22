<template>
    <section class="partner-aurora-error">
        <header class="partner-aurora-error__container partner-aurora-error__header">
            <img
                src="@assets/illustrations/snap-avatar.svg"
                class="partner-aurora-error__header-img">
            <h1 class="partner-aurora-error__heading">Noe stemmer ikke</h1>
        </header>
        <div class="partner-aurora-error__container">
            <p class="partner-aurora-error__text">
                Login kan ha timet ut. Vennligst naviger tilbake igjen for å forsøke på nytt.
            </p>
            <a
                href
                @click.prevent="navigateBackToAurora"
                class="button button--dark button--large">
                Tilbake til Aurora
            </a>
        </div>
    </section>
</template>

<script>

import { mapActions } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'partner-aurora-error',
    methods: {
        ...mapActions({
            navigateBackToAurora: ACTIONS.NAVIGATE_BACK_TO_AURORA
        })
    }
};
</script>

<style lang="scss">
.partner-aurora-error{
    &__container{
        max-width: $partner-max-width;
        margin: 0 auto;
        text-align: center;
        padding: 0 $partner-outer-spacing;
    }
    &__header{
        padding: spacing(4);
        text-align: center;
        &-img{
            margin-bottom: spacing(4);
        }
    }
    &__heading{
        font-size: 150%;
        font-weight: $WEIGHT_MEDIUM;
        color: color(default-text);
        margin-bottom: 0;
    }
    &__text{
        color: color(default-text);
        font-size: 112.5%;
        font-weight: $WEIGHT_LIGHT;
        line-height: 1.666;
        max-width: 380px;
        margin: 0 auto;
        margin-bottom: spacing(8);
    }
}
</style>
