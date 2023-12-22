<template>
    <v-container fluid align-center align-content-center text-xs-center class="container--assignments">
        <v-layout row wrap justify-center>
            <v-flex xs12 md8>
                <v-card>
                    <v-card-title primary-title>
                        {{$t('title.assignments')}}
                    </v-card-title>
                    <v-data-table :headers="headers" :items="assignments" hide-actions>
                        <template slot="items" slot-scope="props">
                            <td class="text-xs-left">{{ $t(`examination.category.${props.item.category}`) }}</td>
                            <td class="text-xs-left">{{ props.item.deadline_time }}</td>
                            <td class="text-xs-left">{{ props.item.charged }}</td>
                            <td class="text-xs-left">{{ props.item.deadline }} {{$t('assignment.hours')}}</td>
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
export default {
    name: 'assignments',
    data () {
        return {
            assignments: [],
            loading: true,
            snackbar: {
                show: false,
                timeout: 6000,
                text: ''
            },
            headers: [
                {
                    text: this.$t('assignment.category'),
                    value: 'category',
                    sortable: false
                },
                {
                    text: this.$t('assignment.deadline_time'),
                    value: 'deadline_time'
                },
                {
                    text: this.$t('assignment.charged'),
                    value: 'charged'
                },
                {
                    text: this.$t('assignment.deadline'),
                    value: 'deadline'
                }
            ]
        };
    },
    mounted () {
        this.axios.get('/examinations')
            .then(results => {
                this.assignments = results.data;
                this.loading = false;
            })
            .catch(error => {
                this.snackbar.text = error.message;
                this.snackbar.show = true;
            });
    }
};
</script>
