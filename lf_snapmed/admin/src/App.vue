<template>
    <v-app class="snapmed">
        <v-toolbar :clipped-left="$vuetify.breakpoint.lgAndUp" color="primary" dark app fixed>
            <v-toolbar-title>
                <router-link to="/"><img src="@/assets/snapmed_white_logo.svg" title="Snapmed" alt="Snapmed" height="50"></router-link>
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn icon to="/assignments" title="Assignments" v-if="isAuthenticated">
                <v-icon>assignment</v-icon>
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn icon to="/cases" title="Interesting cases" v-if="isAuthenticated">
                <v-icon>assignment_ind</v-icon>
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn icon to="/search" title="User search" v-if="isAuthenticated">
                <v-icon>search</v-icon>
            </v-btn>
            <v-spacer></v-spacer>
            <v-menu right v-if="isAuthenticated">
                <v-btn icon slot="activator">
                    <v-icon>more_vert</v-icon>
                </v-btn>
                <v-list>
                    <v-list-tile to="/profile">
                        <v-list-tile-action>
                            <v-icon>account_circle</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>{{$t('action.profile')}}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-list-tile to="/partners" v-if="isSuperadmin">
                        <v-list-tile-action>
                            <v-icon>poll</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>{{$t('action.partner')}}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-list-tile to="/icd-codes">
                        <v-list-tile-action>
                            <v-icon>add_task</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>Update ICD</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-list-tile to="/change-password">
                        <v-list-tile-action>
                            <v-icon>lock</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>{{$t('action.change_password')}}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-list-tile to="/invite-user">
                        <v-list-tile-action>
                            <v-icon>add_box</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>{{$t('action.add_doctor')}}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-list-tile @click.native.stop.prevent="logout" to="/login">
                        <v-list-tile-action>
                            <v-icon>exit_to_app</v-icon>
                        </v-list-tile-action>
                        <v-list-tile-content>
                            <v-list-tile-title>{{$t('action.logout')}}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                </v-list>
            </v-menu>
        </v-toolbar>
        <v-content>
            <router-view/>
        </v-content>
        <the-snackbar></the-snackbar>
    </v-app>
</template>

<script>
import TheSnackbar from '@/components/TheSnackbar';
import { store, ACTIONS } from '@/store';
import { mapGetters } from 'vuex';

export default {
    name: 'App',
    methods: {
        logout () {
            sessionStorage.removeItem('ssn');
            sessionStorage.removeItem('token');
            store.commit('setAuthenticated', false);
            sessionStorage.removeItem('superadmin');
            store.commit('SET_SUPERADMIN', false);
            sessionStorage.removeItem('refreshToken');
            store.commit('REMOVE_REFRESH_TASK');
        }
    },
    mounted () {
        let currentUserUUID = sessionStorage.getItem('currentUserUUID');
        if (currentUserUUID) {
            store.dispatch(ACTIONS.CURRENTUSER_UUID_RECEIVED, currentUserUUID);
        }
        if (this.isAuthenticated) {
            const refreshTask = setTimeout(() => store.dispatch(ACTIONS.REFRESH_TOKEN), this.refreshTimeout);
            store.commit('SET_REFRESH_TASK', refreshTask);
        }
    },
    computed: {
        ...mapGetters([
            'isSuperadmin',
            'refreshTimeout',
            'isAuthenticated'
        ])
    },
    components: {
        TheSnackbar
    }
};
</script>

<style lang="scss">
@import "scss/app.scss";
</style>
