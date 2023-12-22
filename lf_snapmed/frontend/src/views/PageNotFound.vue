<template>
    <h1 v-t="'title.404'">
    </h1>
</template>

<script>
export default {
    name: 'info',
    metaInfo () {
        return {
            title: this.$t('title.404')
        };
    }
};
</script>
