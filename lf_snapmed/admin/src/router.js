import Vue from 'vue';
import Router from 'vue-router';

import store from '@/store';
import Home from '@/views/Home.vue';
import Assignments from '@/views/Assignments.vue';
import Diagnosis from '@/views/Diagnosis.vue';
import Case from '@/views/Case.vue';
import Cases from '@/views/Cases.vue';
import Profile from '@/views/Profile.vue';
import Partners from '@/views/Partners.vue';
import Login from '@/views/Login.vue';
import Search from '@/views/Search.vue';
import Patient from '@/views/Patient.vue';
import ForgotPassword from '@/views/ForgotPassword.vue';
import UpdatePassword from '@/views/UpdatePassword.vue';
import ChangePassword from '@/views/ChangePassword.vue';
import InviteUser from '@/views/InviteUser.vue';
import MedUserVerify from '@/views/DoctorOnboarding/MedUserVerify.vue';
import MedUserThanks from '@/views/DoctorOnboarding/MedUserThanks.vue';
import IcdCodes from '@/views/IcdCodes/IcdCodes.vue';

Vue.use(Router);

const router = new Router({
    mode: 'history',
    routes: [{
        path: '/',
        name: 'home',
        component: Home,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/assignments',
        name: 'assignments',
        component: Assignments,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/diagnosis',
        name: 'diagnosis',
        component: Diagnosis,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/cases',
        name: 'cases',
        component: Cases,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/case',
        name: 'case',
        component: Case,
        props: true,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/search',
        name: 'search',
        component: Search,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/patient/:uuid',
        name: 'patient',
        component: Patient,
        props: true,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/profile',
        name: 'profile',
        component: Profile,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/change-password',
        name: 'changePassword',
        component: ChangePassword,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/partners',
        name: 'partners',
        component: Partners,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/invite-user',
        name: 'invite-user',
        component: InviteUser,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/icd-codes',
        name: 'icd-codes',
        component: IcdCodes,
        meta: {
            requiresAuth: true
        }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: {
            requiresAuth: false
        }
    },
    {
        path: '/forgot-password',
        name: 'forgotPassword',
        component: ForgotPassword,
        meta: {
            requiresAuth: false
        }
    },
    {
        path: '/update-password/:token',
        name: 'updatePassword',
        component: UpdatePassword,
        meta: {
            requiresAuth: false
        }
    },
    {
        path: '/meduser/verify/:token',
        name: 'meduser-verify',
        component: MedUserVerify,
        meta: {
            requiresAuth: false
        }
    },
    {
        path: '/meduser/thanks',
        name: 'meduser-thanks',
        component: MedUserThanks,
        meta: {
            requiresAuth: false
        }
    }]
});

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        // Yes, the route requires authentication.
        // Is the user authenticated?
        if (!store.getters.isAuthenticated) {
            // No, then route the user to login page - with current path as redirect param.
            next({
                path: '/login',
                query: {
                    redirect: to.fullPath
                }
            });
        } else {
            // Yes, the user is authenticated so let them go to the page.
            next();
        }
    } else {
        // No, the route does not require authentication so let them move along.
        next();
    }
});

export default router;
