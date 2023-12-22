<template>
    <v-container fluid justify-center align-center content-center text-xs-center>
        <v-layout row wrap justify-space-between>
            <v-flex xs12 md3 mb-4>
                <v-card class="v-card--remaining" flat fill-height>
                    <v-card-title primary-title dark class="primary justify-center text-xs-center">
                        Number of available cases&nbsp;&nbsp;<small>(total)</small>
                    </v-card-title>
                    <v-card-text class="v-card__text--multiple">
                        <h1 class="display-4">{{available}}</h1>
                        <small class="display-1">({{waiting}})</small>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex xs12 md4 mb-4>
                <v-card to="/diagnosis" class="v-card--overview primary elevation-5 fill-height" raised dark>
                    <v-card-title primary-title align-content-start>
                        <h2 class="text-xs-left display-2">Next case<v-icon x-large>arrow_right_alt</v-icon></h2>
                    </v-card-title>
                    <v-card-text>
                        <img class="v-card__illustration" src="../assets/woman-with-computer.svg">
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex xs12 md3 mb-4>
                <v-card class="v-card--remaining" flat fill-height>
                    <v-card-title primary-title dark class="primary justify-center text-xs-center">
                        Number of cases treated
                    </v-card-title>
                    <v-card-text>
                        <h1 class="display-4">{{totals.total}}</h1>
                    </v-card-text>
                </v-card>
            </v-flex>
        </v-layout>
        <v-layout row wrap justify-space-between>
            <v-flex xs12 md8 mb-4>
                <v-card class="v-card--history fill-height" flat>
                    <v-card-title primary-title dark class="primary justify-center text-xs-center">
                        Number of cases per day
                    </v-card-title>
                    <v-card-text>
                        <chartist class="ct-major-twelfth" type="Line" :data="dailyChartData" :options="dailyChartOptions"></chartist>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex xs12 md3 mb-4>
                <v-card class="v-card--pie fill-height" flat>
                    <v-card-title primary-title dark class="primary justify-center text-xs-center">
                        Case distribution
                    </v-card-title>
                    <v-card-text class="v-card--pie__content">
                        <chartist type="Pie" :data="categoryChartTotals" :options="categoryChartOptions"></chartist>
                    </v-card-text>
                </v-card>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import Vue from 'vue';
import _ from 'lodash';
import store, { SET_SNACKBAR_MESSAGE } from '@/store';

export default {
    name: 'home',
    data () {
        return {
            available: 0,
            personal: null,
            daily: null,
            dailyChartOptions: {
            },
            categoryChartOptions: {
                // width: 200,
                // height: 200
            }
        };
    },
    computed: {
        totals () {
            return _.reduce(this.daily, (result, item) => {
                return {
                    total: result.total + (+item.total),
                    completed: result.completed + (+item.completed)
                };
            }, { total: 0, completed: 0 });
        },
        waiting () {
            return this.totals.total - this.totals.completed;
        },
        categoryChartTotals () {
            const results = _.chain(this.daily)
                .groupBy('category')
                .map((values, key) => ({ key: key, value: _.sumBy(values, 'total') }))
                .reduce((accumulator, item) => {
                    return {
                        labels: _.concat(accumulator.labels, this.$t(`examination.category.${item.key}`)),
                        series: _.concat(accumulator.series, item.value)
                    };
                }, { labels: [], series: [] })
                .value();
            // console.log('Results are in:', results);
            return results;
        },
        dailyChartData () {
            const results = _.chain(this.daily)
                .sortBy('date')
                .groupBy('date')
                .map((values, key) => ({ key: key, value: _.sumBy(values, 'total') }))
                .reduce((accumulator, item) => {
                    return {
                        labels: _.concat(accumulator.labels, item.key),
                        series: [_.concat(accumulator.series[0], item.value)]
                    };
                }, { labels: [], series: [[]] })
                .value();
            console.log('Daily results are in:', results);
            return results;
        }
    },
    beforeRouteEnter (to, from, next) {
        Vue.axios.get('/stats')
            .then(results => {
                console.log('Stat results:', results.data);
                const data = results.data || { available: 0, personal: [], daily: [] };
                next(vm => {
                    vm.available = data.available;
                    vm.personal = data.personal;
                    vm.daily = data.daily;
                });
            })
            .catch(reason => {
                store.commit(SET_SNACKBAR_MESSAGE, 'Unable to return stats from server.');
                if (reason.response && reason.response.status === 401) {
                    // Session has timed out lets redirect user.
                    next({
                        path: '/login',
                        query: {
                            redirect: to.fullPath
                        }
                    });
                } else {
                    next();
                }
            });
    },
    beforerouteUpdate (to, from, next) {
        Vue.axios.get('/stats')
            .then(results => {
                console.log('Stat results:', results.data);
                const data = results.data || { available: 0, personal: [], daily: [] };
                this.available = data.available;
                this.personal = data.personal;
                this.daily = data.daily;
                next();
            })
            .catch(reason => {
                store.commit(SET_SNACKBAR_MESSAGE, 'Unable to return stats from server.');
                if (reason.response && reason.response.status === 401) {
                    // Session has timed out lets redirect user.
                    next({
                        path: '/login',
                        query: {
                            redirect: to.fullPath
                        }
                    });
                } else {
                    next();
                }
            });
    }
};
</script>

<style lang="scss">
.v-card {
    $block: #{&};
    &#{$block}--overview {
        #{$block}__text {
            display: flex;
            flex-flow: row;
            justify-content: flex-end;
            padding: 0 10px;
        }
        #{$block}__illustration {
            width: 120px;
            display: flex;
            align-self: flex-end;
            // margin-top: -10px;
        }
    }
    #{$block}__text--multiple {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        small {
            margin: 0 -10px 15px 10px;
        }
    }
    #{$block}__title h2 .v-icon.material-icons {
        padding-bottom: 5px;
    }
}
</style>
