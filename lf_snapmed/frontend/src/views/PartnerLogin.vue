<template>
    <section class="partner-login">
        <div class="page-checkup__heading">
            <span>{{ $t('storebrand.login.heading') }}</span>
        </div>
        <div class="partner-login__points-conatiner">
            <img src="../assets/icons/check-bullets.svg" class="partner-login__icons" />
            <div class="partner-login__points">
                <h3>{{ $t('storebrand.login.info-header') }}</h3>
                <p>{{ $t('storebrand.login.info-text') }}</p>
                <div class="partner-login__links">
                    <a href="https://drdropin.no/salgsvilkar" class="partner-login__anchor" target="_blank"> {{ $t('storebrand.login.terms') }} </a>
                    <a href="https://drdropin.no/personvernerklaering" target="_blank"> {{ $t('storebrand.login.privacy') }} </a>
                </div>
            </div>
        </div>
        <div class="partner-login__points-conatiner">
            <img src="../assets/icons/check-bullets.svg" class="partner-login__icons" />
            <div class="partner-login__points">
                <h3>{{ $t('storebrand.login.privacy-header') }}</h3>
                <p>{{ $t('storebrand.login.privacy-text') }}</p>
            </div>
        </div>
        <footer class="partner-login__footer">

            <button
                v-if="guid"
                @click="confirmTerms"
                :disabled="acceptTermsClicked"
                class="dd-button proceed-button">
                {{ $t('page.partnerlogin.confirmbutton') }}
            </button>

        </footer>

    </section>
</template>

<script>
import { ACTIONS } from '@/store';
import { mapActions } from 'vuex';

export default {
    name: 'partner',
    data: () => {
        return {
            acceptTermsClicked: false,
            appVersion: process.env.VUE_APP_VERSION
        };
    },
    methods: {
        ...mapActions({
            loginStorebrand: ACTIONS.LOGIN_STOREBRAND,
            showPageHeader: ACTIONS.SHOW_PAGE_HEADER,
            setBrand: ACTIONS.SET_BRAND,
            setPromoCode: ACTIONS.SET_PROMOCODE,
            resetState: ACTIONS.RESET_STORE
        }),
        async confirmTerms () {
            this.acceptTermsClicked = true;
            this.loginStorebrand({ 'guid': this.guid, 'brand': this.brand }).then(loginResponse => {
                if (loginResponse) {
                    let promocode = this.$route.query.promocode;
                    if (promocode !== undefined) {
                        this.setPromoCode(promocode);
                    }
                    this.$router.push('/partner/select');
                } else {
                    this.acceptTermsClicked = false;
                    window.alert(this.$t('storebrand.login.fail-alert'));
                }
            }).catch(error => {
                if (error.response && error.response.data && error.response.data.error) {
                    let errorString = error.response.data.error;
                    if (errorString === 'login.missing-info') {
                        window.alert(this.$t('storebrand.login.missing-info-alert'));
                    } else {
                        window.alert(this.$t('storebrand.login.timeout-string-alert') + errorString + ')');
                    }
                } else {
                    window.alert(this.$t('storebrand.login.timeout-alert'));
                }
                this.acceptTermsClicked = false;
            });
        }
    },
    computed: {
        showGuid () {
            return process.env.VUE_APP_MODE === 'qa' || process.env.VUE_APP_MODE === 'development';
        },
        guid () {
            let guid = this.$route.query.guid;
            return guid;
        },
        brand () {
            let brand = this.$route.query.brand;
            this.setBrand(brand);
            return brand;
        },
        getUCBrand () {
            if (this.brand === undefined || this.brand === null) {
                return '';
            }
            return this.brand.toUpperCase();
        }
    },
    async mounted () {
        await this.resetState();
        await this.showPageHeader(true);
    }
};
</script>

<style lang="scss" scoped>
.partner-login{
    @include grid-width-limit-padding-no;
    @include breakpoint(small only) {
        padding-inline: 16px;
    }
    &__points-conatiner{
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 32px;
    }
    &__icons{
        margin-right: 24px;
        @include breakpoint(small only) {
            width: 20px;
        }
    }
    &__points{
        color: #fff;
        @include breakpoint(small only) {
            font-size: 16px;
        }
    }
    &__links{
        width: 100%;
        display: flex;
        margin-top: 16px;
    }
    &__anchor{
        margin-right: 24px;
    }
    &__terms{
        padding: spacing(3) spacing(3);
        color: color(white);
        background-color: color(blueberry);
    }
    &__container{
        max-width: $partner-max-width;
        margin-left: auto;
        margin-right: auto;
    }
    &__main-heading{
        font-family: $serif-font-family;
        font-weight: $WEIGHT_BOLD;
        font-size: 150%;
    }
    &__heading{
        font-weight: $WEIGHT_BOLD;
        font-size: 112.5%;
        margin-top: 1.5em;
    }
    &__footer{
        padding: spacing(4) 0;
        text-align: center;
    }
    a {
        color: color(white);
        text-decoration: underline;
    }

    button {
        &:disabled {
            cursor: not-allowed;
            background-color: rgba(lightgray, 0.5);
            color: color(black, 0.8);
        }
    }
}
</style>
