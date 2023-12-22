<template>
    <div ref="focus_component" />
</template>

<script>
import { scrollBy } from 'seamless-scroll-polyfill';

export default {
    name: 'AutoFocus',
    props: {
        disabled: {
            type: Boolean,
            default: false
        }
    },
    mounted () {
        if (!this.disabled) {
            var scrollDiv = this.$refs.focus_component.offsetTop;
            scrollBy(window, { behavior: 'smooth', top: scrollDiv });
        }
    }

};
</script>
