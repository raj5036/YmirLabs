import Vue from 'vue';
import Router from 'vue-router';
import Meta from 'vue-meta';

import Flow from '@/views/Flow';
import PartnerLogin from '@/views/PartnerLogin';
import PartnerSelectProduct from '@/views/PartnerSelectProduct';
import PartnerAuroraError from '@/views/PartnerAuroraError';
import PageNotFound from '@/views/PageNotFound';
import BankIDReturn from '@/views/BankIDReturn';
import PartnerAurora from '@/views/PartnerAurora';
import UpdatePassword from '@/views/UpdatePassword';
import EmailVerification from '@/views/EmailVerification';
import ResendEmail from '@/views/ResendEmail';
import IntroPage from '@/views/IntroPage';
import ThankYouPage from '@/views/ThankYouPage';

Vue.use(Router);
Vue.use(Meta);

const router = new Router({
    mode: 'history',
    routes: [
        {
            path: '/book',
            name: 'check',
            component: Flow
        },
        {
            path: '/bankid-return',
            name: 'bankid-return',
            component: BankIDReturn
        },
        {
            path: '/partner',
            name: 'partner',
            component: PartnerLogin
        },
        {
            path: '/partner/select',
            name: 'partner-select-product',
            component: PartnerSelectProduct
        },
        {
            path: '/partner/error',
            name: 'partner-aurora-error',
            component: PartnerAuroraError
        },
        {
            path: '/partner/aurora',
            name: 'partner-aurora',
            component: PartnerAurora
        },
        {
            path: '/resend-email',
            name: 'resend-email',
            component: ResendEmail
        },
        {
            path: '/update-password/:token',
            name: 'update-password',
            component: UpdatePassword
        },
        {
            path: '/email-verification/:token',
            name: 'email-verification',
            component: EmailVerification
        },
        {
            path: '/intro',
            name: 'intro',
            component: IntroPage
        },
        {
            path: '/thank-you',
            name: 'thank-you',
            component: ThankYouPage
        },
        {
            path: '*',
            component: PageNotFound
        }
    ],
    scrollBehavior () {
        return { x: 0, y: 0 };
    }
});

router.afterEach((to) => {
    gtag('config', process.env.VUE_APP_GA_ID, {
        'page_path': to.path,
        'page_title': to.name || 'PageNotFound'
    });
});

export default router;
