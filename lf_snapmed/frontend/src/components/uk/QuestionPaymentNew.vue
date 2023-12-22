<template lang="">
    <section class="question-payment-new-no">
        <AutoFocus />
        <div class="question-payment-new__container">
            <transition name="fade">
                <div v-if="processing" class="question-payment__processing">
                    <svg-loader />
                    <h3>
                        {{ $t("page.check.payment_processing") }}
                    </h3>
                </div>
            </transition>

            <div class="question-payment-new__flex-alignment">
            </div>
            <div
                v-if="showPromoBox"
                class="question-payment-new__promo-container"
            >
                <div class="question-payment-new__flex-alignment">
                    <div
                        class="question-payment-new__flex-alignment question-payment-new__promo-input w-100"
                        :style="
                            promoMessage
                                ? { border: '1px solid #D63E39' }
                                : null
                        "
                    >
                        <input
                            style="width:100%"
                            :placeholder="$t('page.check.enter_promocode')"
                            :value="promoCode.toUpperCase()"
                            @input="promoCodeInput"
                        />
                        <img
                            src="@assets/icons/cancel.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__icon pointer"
                            @click="removePromo()"
                        />
                    </div>
                    <button
                        :disabled="
                            (promoCode.length > 0 ? false : true) ||
                                promoApplied
                        "
                        class="dd-button"
                        :style="{ width: '100% !important' }"
                        @click="promoDiscount()"
                    >
                        {{$t(`btn.confirm`)}}
                    </button>
                </div>
                <div
                    v-if="promoMessage"
                    class="question-login-password__error-container question-payment__promo-error"
                >
                    <img
                        src="@assets/icons/error.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-login-password__icon"
                    />
                    <span class="question-login-password__error">{{
                        $t("promocode." + this.promoMessage)
                    }}</span>
                </div>
            </div>
            <div v-if="promoApplied && !promoMessage">
                <div class="question-payment-new__flex-alignment price-text">
                    <div class="question-payment-new__payment-text">
                        {{ $t("page.check.payment.price") }}
                    </div>
                    <div class="question-payment-new__payment-text">
                        {{ amount(true) }}
                    </div>
                </div>
                <div class="question-payment-new__flex-alignment price-text">
                    <div class="question-payment-new__payment-text">
                        {{ $t("page.check.payment.code") }}
                    </div>
                    <div class="question-payment-new__payment-text">
                        {{ promoCode.toUpperCase() }}
                    </div>
                </div>
                <div class="question-payment-new__payment-box">
                    <div class="question-payment-new__flex-alignment price-text">
                        <div class="question-payment-new__payment-text">
                            {{ $t("page.check.payment.total") }}
                        </div>
                        <div class="question-payment-new__payment-text">
                            {{ amount(false) }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else>
                    <div class="question-payment-new__flex-alignment price-text">
                        <div class="question-payment-new__payment-text">
                            {{ $t("page.check.payment.total") }}
                        </div>
                        <div class="question-payment-new__payment-text">
                            {{ amount(false) }}
                        </div>
                    </div>
            </div>
        </div>
        <h3>
                {{ $t("page.check.payment.card_details") }}
        </h3>
        <div
            class="question-payment-new__container question-payment-new__responsive-container-styling"
        >
            <stripe-element
                type="cardNumber"
                class="question-payment-new__card-number"
                :stripe="stripeKey"
                :options="newStripeOptions"
                @change="StripeElementOnChange"
            ></stripe-element>
            <div class="question-payment-new__flex-alignment">
                <div style="width:100%">
                    <div class="question-payment-new__card-text">
                        {{ $t("page.check.payment.expiry") }}
                    </div>
                    <stripe-element
                        type="cardExpiry"
                        style="width:100%"
                        :stripe="stripeKey"
                        :options="newStripeOptions"
                        @change="cardExpiryComplete = $event.complete"
                    ></stripe-element>
                </div>
                <div style="width:100%">
                    <div class="question-payment-new__card-text">
                        {{ $t("page.check.payment.cvc") }}
                    </div>
                    <stripe-element
                        type="cardCvc"
                        style="width:100%"
                        :stripe="stripeKey"
                        :options="newStripeOptions"
                        @change="cardCvcComplete = $event.complete"
                    ></stripe-element>
                </div>
            </div>
        </div>
        <span v-if="error" class="dd-error-text">{{error}}</span>
        <div class="page-checkup__continue">
            <button
                :disabled="
                    processing ||
                        !cardNumberComplete ||
                        !cardExpiryComplete ||
                        !cardCvcComplete
                "
                class="dd-button"
                @click="token"
            >
                <span>{{ $t("btn.charge") }}</span>
            </button>
        </div>
    </section>
</template>

<script>
import { mapActions, mapState, mapGetters, mapMutations } from 'vuex';
import { ACTIONS, GETTERS, MUTATIONS } from '@/store';
import { ACTIONS as STORE_ACTIONS } from '@/store/video';
import { createPaymentMethod, StripeElement } from 'vue-stripe-elements-plus';
import { has } from 'lodash';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'QuestionPaymentNew',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            error: null,
            stripeKey: process.env.VUE_APP_STRIPE_KEY,
            price: null,
            complete: false,
            processing: false,
            promoCode: '',
            promoApplied: false,
            promoMessage: '',
            finalAmount: null,
            showPromoBox: true,
            showCard: true,
            cardNumberComplete: false,
            cardExpiryComplete: false,
            cardCvcComplete: false,
            stripeOptions: {
                classes: {
                    base: 'question-payment__input',
                    complete: 'question-payment__input--complete',
                    empty: 'question-payment__input--empty',
                    focus: 'question-payment__input--focus',
                    invalid: 'question-payment__input--invalid'
                },
                style: {
                    base: {
                        fontWeight: '100',
                        fontSize: '18px',
                        color: '#5e4098'
                    }
                }
            },
            newStripeOptions: {
                classes: {
                    base: 'question-payment-new__input',
                    complete: 'question-payment-new__input--complete',
                    empty: 'question-payment-new__input--empty',
                    focus: 'question-payment-new__input--focus',
                    invalid: 'question-payment-new__input--invalid'
                },
                style: {
                    base: {
                        fontWeight: '100',
                        fontSize: '16px',
                        color: '#27112D'
                    },
                    invalid: {
                        color: 'rgb(214, 62, 57)'
                    }
                }
            }
        };
    },
    computed: {
        mobile () {
            return this.$vuetify.breakpoint.sm;
        },
        ...mapState({
            paymentError: state =>
                (state.payment && state.payment.error) || false,
            region: state => state.region,
            newUser: state => state.newUser,
            uuid: state => state.partner.active ? state.partner.uuid : state.user.uuid,
            description: state => state.check.answers.description,
            flowType: state => state.flowType
        })
    },
    methods: {
        StripeElementOnChange (e) {
            this.error = null;
            this.cardNumberComplete = e.complete;
        },
        promoCodeInput (e) {
            this.promoCode = e.target.value.toUpperCase();
            this.removePromo(false);
        },
        amount (original) {
            var { CURRENCY_LOCALE } = this.$store.getters.AMOUNT();
            if (original) {
                return this.$n(this.price, 'currency', CURRENCY_LOCALE);
            } else {
                return this.$n(this.finalAmount, 'currency', CURRENCY_LOCALE);
            }
        },
        ...mapActions({
            goBack: ACTIONS.CHANGE_PAGE,
            charge: ACTIONS.UPDATE_PAYMENT,
            pictureCharge: ACTIONS.CHARGE_CARD,
            videoCharge: STORE_ACTIONS.CONFIRM_APPOINTMENT_AND_CHARGE,
            confirm: ACTIONS.CONFIRM_QUESTION_PAYMENT,
            checkPromoCode: ACTIONS.CHECK_PROMOCODE,
            getPromoCode: ACTIONS.GET_PROMOCODE,
            completeVideo: ACTIONS.SET_PARTNER_VIDEO_COMPLETE
        }),
        ...mapGetters({
            paymentState: GETTERS.PAYMENT_STATE,
            promo: GETTERS.PROMOCODE
        }),
        ...mapMutations({
            mutationsError: MUTATIONS.ERROR
        }),
        handleServerResponse (response) {
            if (response.error) {
                // Show error from server on payment form
            } else if (response.requires_action) {
                // Use Stripe.js to handle required card action
                this.handleAction(response);
            } else {
                // Show success message
            }
        },
        handleAction (response) {
            /* eslint-disable-next-line */
            let stripe = new Stripe(this.stripeKey);
            stripe
                .handleCardAction(response.payment_intent_client_secret)
                .then(result => {
                    if (result.error) {
                        // Show error in payment form
                        this.mutationsError('CHARGE_CARD');
                        this.complete = false;
                        this.processing = false;
                    } else {
                        // The card action has been handled
                        // The PaymentIntent can be confirmed again on the server
                        this.confirmQuestionPayment({
                            payment_intent_id: result.paymentIntent.id
                        })
                            .then(confirmResult => {
                                if (confirmResult.status === 'succeeded') {
                                    this.complete = true;
                                    this.processing = false;
                                } else {
                                    this.mutationsError('CHARGE_CARD');
                                }
                            })
                            .catch(error => {
                                this.mutationsError(error);
                            });
                    }
                });
        },
        token () {
            this.processing = true;
            createPaymentMethod('card').then(data => {
                if (has(data, 'paymentMethod.id')) {
                    if (this.flowType === 'video') {
                        this.videoCharge({
                            uuid: this.uuid,
                            description: this.description,
                            payment_method_id: data.paymentMethod.id,
                            promoCode: this.promoCode,
                            amount: this.finalAmount,
                            currency: process.env.VUE_APP_CURRENCY
                        }).then((response) => {
                            if (response.success) {
                                this.paymentOk = true;
                                this.complete = true;
                                this.processing = false;
                                this.completeVideo();
                            } else if (response.requires_action) {
                                this.complete = false;
                                this.processing = true;
                                this.handleAction(response);
                            } else {
                                this.paymentOk = false;
                                this.complete = false;
                                this.processing = false;
                                if (has(data, 'error')) {
                                    window.alert(data.error.message);
                                }
                            }
                        }).catch((error) => {
                            console.error(error);
                            this.paymentOk = false;
                            this.complete = false;
                            this.processing = false;
                        });
                    } else {
                        this.pictureCharge({
                            id: this.data.id,
                            paymentMethod: data.paymentMethod.id,
                            deadline: 24,
                            amountKey: this.setAmountKey(),
                            amount: this.finalAmount,
                            promoCode: this.promoCode
                        })
                            .then(response => {
                                if (response.success) {
                                    this.complete = true;
                                    this.processing = false;
                                } else if (response.requires_action) {
                                    this.complete = false;
                                    this.processing = true;
                                    this.handleAction(response);
                                } else {
                                    this.mutationsError(data);
                                }
                            })
                            .catch(error => {
                                this.error = this.getError(error.response.data['error.message']);
                                this.mutationsError(error);
                            })
                            .finally(() => {
                                this.processing = false;
                            });
                    }
                } else {
                    this.mutationsError(data);
                }
            });
        },
        getError (errMsgEng) {
            switch (errMsgEng) {
                case 'Your card was declined.' :
                    return this.$t(`page.check.payment['Your card was declined.']`);
                case 'Your card\'s security code is incorrect.' :
                    return this.$t(`page.check.payment['Your card's security code is incorrect.']`);
                default:
                    return errMsgEng;
            }
        },
        async promoDiscount () {
            const data = await this.checkPromoCode({
                promoCode: this.promoCode,
                type: 'cs'
            });
            this.promoApplied = true;
            if (!data.exists) {
                this.promoMessage = 'not_exists';
            } else if (!data.valid) {
                this.promoMessage = 'expired';
            } else if (data.used) {
                this.promoMessage = 'already_used';
            } else {
                // If the promocode exists, is valid and not used then get details
                // If the promocode is applicable for chat payments since we are handling video and chat promo together
                if (data.applicable) {
                    this.promoMessage = '';
                    this.showPromoBox = true;
                    var AMOUNT_FLOAT = parseFloat(this.price);
                    this.finalAmount = this.calculateNewAmount(
                        AMOUNT_FLOAT,
                        data.discount24hrs,
                        data.discountFixed
                    );
                } else {
                    this.promoMessage = 'not_applicable';
                }
            }
        },
        removePromo (resetCode = true) {
            this.setInitialPrice();
            resetCode && (this.promoCode = '');
            this.promoMessage = '';
            this.promoApplied = false;
        },
        calculateNewAmount (initialAmount, discountValue, discountFixed) {
            if (discountFixed) {
                return String(initialAmount - discountValue);
            } else {
                return initialAmount - (initialAmount * discountValue) / 100;
            }
        },
        setInitialPrice () {
            if (this.flowType === 'picture') {
                if (this.newUser) {
                    this.price = this.finalAmount =
                        process.env[`VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_NEW_USER`];
                } else {
                    this.price = this.finalAmount =
                        process.env[`VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_RETURNING_USER`];
                }
            } else {
                this.price = this.finalAmount =
                    process.env[`VUE_APP_AMOUNT_VIDEO_${process.env.VUE_APP_CURRENCY}`];
            }
        },
        setAmountKey () {
            if (this.newUser) {
                return 'Newuser';
            } else {
                return 'Returninguser';
            }
        },
        changePromoBoxState () {
            this.showPromoBox = !this.showPromoBox;
            this.promoMessage = '';
        },
        changeCardState () {
            this.showCard = !this.showCard;
        }
    },
    async mounted () {
        this.setInitialPrice();
        await this.getPromoCode();
        let promoCode = this.promo();
        if (
            promoCode &&
            promoCode !== null &&
            promoCode !== undefined &&
            promoCode !== ''
        ) {
            this.promoCode = promoCode;
            setTimeout(() => {
                this.promoDiscount();
            }, 100);
        }
        gtag('event', 'add_payment_info', { event_category: 'booking' });
    },
    components: {
        StripeElement,
        AutoFocus
    },
    watch: {
        paymentError (value) {
            if (
                value &&
                (value === 'CHARGE_CARD' || value.hasOwnProperty('error'))
            ) {
                this.$refs.card.clear();
                this.processing = false;
                this.complete = false;
            }
        }
    }
};
</script>
