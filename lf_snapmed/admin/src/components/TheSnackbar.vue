<template>
    <v-snackbar v-model="showSnackbar" :timeout="4000">
        {{message}}
    </v-snackbar>
</template>

<script>
import { SET_SNACKBAR_DISPLAY } from '@/store';

export default {
    name: 'the-snackbar',
    computed: {
        message () {
            return this.$store.state.snackbarMessage;
        },
        showSnackbar: {
            get () {
                return this.$store.state.snackbarShow;
            },
            set (value) {
                this.$store.commit(SET_SNACKBAR_DISPLAY, value);
            }
        }
    }
};
</script>

<style scoped>

</style>
