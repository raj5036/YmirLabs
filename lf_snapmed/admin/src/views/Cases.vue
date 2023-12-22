<template>
    <v-container fluid align-center align-content-center text-xs-center class="container--cases">
        <v-layout row wrap justify-center>
            <v-flex xs12 md8>
                <v-card>
                    <v-card-title primary-title>
                        {{$t('title.cases')}}
                    </v-card-title>
                    <v-data-table :headers="headers" :items="cases" hide-actions >
                        <template slot="items" slot-scope="props">
                            <router-link :to="{name: 'case', params: {examination: props.item.examination}}" tag="tr" class="view-case">
                                <td class="text-xs-left">{{ props.item.created_at }}</td>
                                <td class="text-xs-left">{{ $t(`examination.category.${props.item.examination.category}`) }}</td>
                                <td class="text-xs-left">{{ $t('examination.who.' + props.item.examination.who) }}</td>
                                <td class="text-xs-left">{{ props.item.examination.age }}</td>
                                <td class="text-xs-left">{{ $t('examination.gender.' + props.item.examination.gender) }}</td>
                            </router-link>
                        </template>
                    </v-data-table>
                </v-card>
            </v-flex>
        </v-layout>
        <v-snackbar :timeout="snackbar.timeout" top multi-line v-model="snackbar.show">
            {{ snackbar.text }}
            <v-btn flat @click.native="snackbar.show = false">Close</v-btn>
        </v-snackbar>
    </v-container>
</template>

<script>
import Vue from 'vue';

export default {
    name: 'cases',
    data () {
        return {
            cases: [],
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            },
            headers: [
                {
                    text: this.$t('label.marked'),
                    value: 'created_at'
                },
                {
                    text: this.$t('assignment.category'),
                    value: 'examination.category',
                    sortable: false
                },
                {
                    text: this.$t('examination.who.label'),
                    value: 'examination.who',
                    sortable: false
                },
                {
                    text: this.$t('examination.age.label'),
                    value: 'examination.age'
                },
                {
                    text: this.$t('examination.gender.label'),
                    value: 'examination.gender',
                    sortable: false
                }
            ]
        };
    },
    async beforeRouteEnter (to, from, next) {
        try {
            const response = await Vue.axios.get('/cases');
            if (response && response.data && response.data.cases) {
                next(vm => {
                    vm.cases = response.data.cases;
                });
                return;
            }
            next(vm => {
                vm.snackbar.text = 'Did not find any cases marked as interesting!';
                vm.snackbar.show = true;
            });
        } catch (error) {
            console.log(error.message, error);
            next(vm => {
                vm.snackbar.text = error.message;
                vm.snackbar.show = true;
            });
        }
    },
    async beforeRouteUpdate (to, from, next) {
        try {
            const response = await Vue.axios.get('/cases');
            if (response && response.data && response.data.cases) {
                this.cases = response.data.cases;
                next();
                return;
            }
            this.snackbar.text = 'Did not find any cases marked as interesting!';
            this.snackbar.show = true;
            next(false);
        } catch (error) {
            console.log(error.message, error);
            this.snackbar.text = error.message;
            this.snackbar.show = true;
            next();
        }
    }
};
</script>

<style lang="scss">
.container--cases {
    .view-case {
        cursor: pointer;
    }
}
</style>
