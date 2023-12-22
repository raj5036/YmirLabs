<template>
    <div>
        <section class="thank-you-common">
            <h1>
                <p>{{$t(`page.thank-you.${flowType}.thank-you`)}}</p>
                <p>{{$t(`page.thank-you.${flowType}.recieved-case`)}}</p>
            </h1>
            <li>
                <ul>
                   {{$t(`page.thank-you.${flowType}.list-1`)}}
                </ul>
                <ul>
                    {{$t(`page.thank-you.${flowType}.list-2`)}}
                </ul>
            </li>
            <button v-if="isAuroraFlow" v-on:click="navigateBackToAurora">
                {{ $t(`storebrand.picture-confirm.aurora-button`) }}
            </button>
            <button v-else-if="isPartner"  v-on:click="navigateBackToPartner">
                {{ $t(`storebrand.picture-confirm.blifrisk-button`, [getUCBrand]) }}
            </button>
            <button v-else v-on:click="goToWebsite">{{$t(`page.thank-you.${flowType}.back-home`)}}</button>
        </section>
        <div id="st_code"></div>
        <div id="tag_code"></div>
    </div>
</template>

<script>
import { ACTIONS, GETTERS } from '@/store';
import { mapActions, mapState, mapGetters } from 'vuex';

export default {
    name: 'thank-you-common',
    metaInfo () {
        return {
            title: 'Thank-you'
        };
    },
    data () {
        return {
            email: '',
            error: false
        };
    },
    methods: {
        ...mapActions({
            setEmail: ACTIONS.SET_SUBSCRIPTION_EMAIL,
            setThankyouPage: ACTIONS.SET_THANK_YOU_PAGE,
            resetStore: ACTIONS.RESET_STORE,
            goToWebsite: ACTIONS.GO_TO_WEBSITE,
            navigateBackToPartner: ACTIONS.NAVIGATE_BACK_TO_PARTNER,
            navigateBackToAurora: ACTIONS.NAVIGATE_BACK_TO_AURORA
        }),
        submitEmail () {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            if (re.test(this.email.toLowerCase())) {
                this.setEmail(this.email);
            } else {
                this.error = true;
            }
        },
        hideIF () {
            document.getElementById('IF').style.visibility = '';
        },
        getSaleInfo () {
            document.getElementById('st_code').style.visibility = 'hidden';
            document.getElementById(
                'st_code'
            ).innerHTML = `<iframe src="https://snapmed.ositracker.com/sales/salejs/amount:${this.osiAmount}/transaction:${this.examinationId}" alt="" id=IF width=50 height=50 border="0" frameborder="0">`;

            if (this.tagrid) {
                document.getElementById(
                    'tag_code'
                ).innerHTML = `<img src='https://www.tagserve.com/saleServlet?MID=655&PID=957&CRID=&ORDERID=${this.examinationId}&ORDERAMNT=${this.osiAmount}&NUMOFITEMS=1&CUR=${this.currency}&RID=${this.tagrid}' border='0' width='1' height='1'>`;
            }
        }
    },
    computed: {
        ...mapGetters({
            isAuroraFlow: GETTERS.IS_AURORA_FLOW,
            isPartner: GETTERS.IS_PARTNER_FLOW_ACTIVE
        }),
        ...mapState({
            success: state => state.subscriptionSuccessful,
            region: state => state.region,
            examinationId: state => state.examination,
            osiAmount: state => state.osiAmount,
            currency: state => state.currency,
            tagrid: state => state.tagrid,
            flowType: state => state.flowType
        })
    },
    async destroyed () {
        await this.resetStore();
    },
    async mounted () {
        this.setThankyouPage();
        this.getSaleInfo();
        this.hideIF();
    }
};
</script>

<style lang="scss">
.thank-you-common {
        display: flex;
        flex-direction: column;
        align-items: center;
        row-gap: 64px;
        max-width: 747px;
        width: 100%;
        padding-inline: 16px;
        margin-top: 80px;
        h1 {
            color: $dd-text-colour-light;
            font-weight: 400;
            text-align: center;
        }
        li {
            display: flex;
            flex-direction: column;
            row-gap: 42px;
            border-bottom: unset !important;
            ul {
                color: $dd-text-colour-light;
                font-weight: 400;
                font-size: $font-medium-large;
                display: flex;
                align-items: center;
                column-gap: 8px;
                &::before {
                    content: "";
                    min-height: 22px;
                    min-width: 22px;
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: center;
                    background-image: url("~@/assets/dd/ul-ok.svg");
                }
            }
        }
        button {
            @include dd-button;
            width: 346px;
            @include breakpoint(small only) {
                width: 100%;
            }
        }
}
</style>
