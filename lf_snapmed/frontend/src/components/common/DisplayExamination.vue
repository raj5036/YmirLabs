<template>
    <a
        href="#"
        :title="check(examination)"
        class="display-examination">
        <div class="display-examination__category-mobile">
            <div class="display-examination__value display-examination__value--category">
                {{$t(`questions.category.options.${examination.category}`)}}
            </div>
        </div>
        <div class="display-examination__column display-examination__column--medium">
            <label class="subtle-label display-examination__label">{{$t('examination.labels.ref')}}</label>
            <div class="display-examination__value">{{ check(examination) }}</div>
        </div>
        <div class="display-examination__column display-examination__column--medium">
            <label class="subtle-label display-examination__label">{{$t('examination.labels.category')}}</label>
            <div class="display-examination__value display-examination__value--category">
                {{$t(`questions.category.options.${examination.category}`)}}
            </div>
        </div>
        <div class="display-examination__column display-examination__column--medium">
            <label class="subtle-label display-examination__label">{{$t('examination.labels.age')}}</label>
            <div class="display-examination__value">{{ examination.age }}</div>
        </div>
        <div class="display-examination__column">
            <label class="subtle-label display-examination__label">{{$t('examination.labels.charged')}}</label>
            <div class="display-examination__value display-examination__value--date">{{ examination.charged | formatDate }}</div>
        </div>
        <div class="display-examination__column">
            <label class="subtle-label display-examination__label">{{$t('examination.labels.diagnosed')}}</label>
            <div class="display-examination__value display-examination__value--diagnosed">
                <svg-asset :icon="statusIcon" class="display-examination__icon" /> {{$t(`page.response.diagnosed.${examination.diagnosed}`)}}
            </div>
        </div>
        <div class="display-examination__arrow">
            <svg-asset icon="chevron-right" />
        </div>
    </a>
</template>

<script>
import { last, split, join } from 'lodash';
import SvgAsset from '@common/SvgAsset';

export default {
    name: 'DisplayExamination',
    components: {
        SvgAsset
    },
    props: {
        examination: {
            type: Object,
            required: true
        }
    },
    computed: {
        check: () => (examination) => {
            const lastUid = last(split(examination.uuid, '-'));
            return join(lastUid.match(/.{1,4}/g), '-');
        },
        statusIcon () {
            return this.examination.diagnosed ? 'check-success-circle' : 'alert-circle';
        }
    },
    filters: {
        formatDate (value) {
            return value && value.slice(0, -3);
        }
    }
};
</script>

<style lang="scss">
.display-examination {
    $block: &;
    position: relative;
    display: flex;
    flex-flow: row wrap;
    align-items: center;
    justify-content: space-between;
    padding: spacing(2) spacing(3);
    @include breakpoint(small only) {
        justify-content: flex-start;
        padding-left: spacing(2);
    }
    padding-right: spacing(5);
    margin-bottom: spacing(2);
    border: solid 1px color(mid-purple);
    text-decoration: none;
    text-align: left;
    &:first-child {
        border-radius: 8px 8px 0px 0px;
    }
    &:last-child {
        border-radius: 0px 0px 8px 8px;
    }
    transition: background-color .25s ease;
    &:hover {
        background-color: color(subtle-purple);
    }
    &__column {
        margin-right: spacing(3);
        &:last-child{
            margin-right: 0;
        }
        margin-top: spacing(1);
        margin-bottom: spacing(1);
        &--medium {
            display: none;
            @include breakpoint(medium){
                display: block;
            }
        }
        &--mobile {
            @include breakpoint(medium){
                display: none;
            }
        }
    }
    &__category-mobile {
        position: absolute;
        top: 0;
        left: 0;
        height: auto;
        @include breakpoint(medium){
            display: none;
        }
        #{$block}__value--category {
            border-radius: 0px 0px 4px 0px;
            font-size: 62.5%;
        }
    }
    &__label {
        display: block;
        margin-bottom: spacing(1);
        cursor: inherit;
    }
    &__value {
        display: block;
        color: color(default-text);
        font-size: 87.5%;
        min-height: 1.5em;
        &--date {
            color: color(green);
        }
        &--diagnosed {
            display: flex;
            align-items: center;
            margin-top: -.25em;
        }
        &--category {
            color: color(white);
            background-color: color(secondary-purple);
            border-radius: 4px;
            padding: 0 .5em;
        }
    }
    &__icon {
        margin-right: spacing(.5);
    }
    &__arrow {
        position: absolute;
        top: 0;
        right: spacing(3);
        width: auto;
        height: 100%;
        display: flex;
        align-items: center;
    }
}
</style>
