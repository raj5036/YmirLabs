<template>
    <section class="question-payment">
        <card
            ref="card"
            :class="{ complete }"
            :stripe="stripeKey"
            :options="stripeOptions"
            @change="complete = $event.complete"
        />
        <div class="question-payment__promo-container">
            <div class="question-payment__promo-box question-payment__promo">
                <div v-if="this.isCheckFlow" class="question-payment__text">
                    {{ $t('header.picture_consultation') }}
                </div>
                <div class="question-payment__text">{{ amount(true) }}</div>
            </div>
            <div class="question-payment__promo-box">
                <div class="question-payment__promo">
                    <input
                        :disabled="promoApplied"
                        class="question-payment__promo-input"
                        :placeholder="$t('page.check.enter_promocode')"
                        :value="promoCode.toUpperCase()"
                        @input="promoCode = $event.target.value.toUpperCase()"
                    />
                    <div
                        v-if="!promoApplied"
                        class="question-payment__text pointer font-weight"
                        @click="promoDiscount()"
                    >
                        {{ $t('page.check.apply') }}
                    </div>
                    <img
                        v-else
                        src="@assets/icons/cancel.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-login-password__icon pointer"
                        @click="removePromo()"
                    />
                </div>
                <div
                    v-if="promoMessage"
                    class="
            question-login-password__error-container
            question-payment__promo-error
          "
                >
                    <img
                        src="@assets/icons/error.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-login-password__icon"
                    />
                    <span class="question-login-password__error">{{
                        $t('promocode.' + this.promoMessage)
                    }}</span>
                </div>
            </div>
            <div class="question-payment__promo-box question-payment__promo">
                <div class="question-payment__text">
                    {{ $t('page.check.subtotal') }}
                </div>
                <div class="question-payment__text">{{ amount(false) }}</div>
            </div>
        </div>

        <button
            v-if="complete || paymentError"
            :disabled="processing"
            class="button--dark page-checkup__continue"
            @click="token"
        >
            {{ $t('btn.charge') }}
        </button>
        <div
            v-if="(region === 'uk' && complete) || paymentError"
            class="page-checkup__sub-heading"
        >
            {{ $t('page.check.payment_details_sub_text') }}
        </div>
        <transition name="fade">
            <div v-if="processing" class="question-payment__processing">
                <svg-loader />
                <h3>
                    {{ $t('page.check.payment_processing') }}
                </h3>
            </div>
        </transition>
        <transition name="fade">
            <span v-if="paymentError && !complete" class="payment__error">{{
                $t(`errors.card_declined.${cardError}`)
            }}</span>
        </transition>
    </section>
</template>

<script>
import { mapActions, mapState, mapGetters, mapMutations } from 'vuex';
import { ACTIONS, GETTERS, MUTATIONS } from '@/store';
import { Card, createPaymentMethod } from 'vue-stripe-elements-plus';
import { has } from 'lodash';

export default {
    name: 'QuestionPayment',
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            stripeKey: process.env.VUE_APP_STRIPE_KEY,
            price: null,
            complete: false,
            processing: false,
            promoCode: '',
            promoApplied: false,
            promoMessage: '',
            finalAmount: null,
            showPromoBox: false,
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
            newUser: state => state.newUser
        }),
        cardError () {
            console.log(this.paymentError);
            if (this.paymentError['stripe.type'] === 'card_error') {
                if (this.paymentError['stripe.code'] === 'card_declined') {
                    return this.paymentError['stripe.decline_code'];
                }
                return this.paymentError['stripe.code'];
            }
            return 'unknown';
        }
    },
    methods: {
        amount (original) {
            var { CURRENCY_LOCALE } = this.$store.getters.AMOUNT();
            if (original) {
                return this.$n(this.price, 'currency', CURRENCY_LOCALE);
            } else {
                return this.$n(this.finalAmount, 'currency', CURRENCY_LOCALE);
            }
        },
        ...mapActions({
            charge: ACTIONS.UPDATE_PAYMENT,
            performCharge: ACTIONS.CHARGE_CARD,
            confirmQuestionPayment: ACTIONS.CONFIRM_QUESTION_PAYMENT,
            checkPromoCode: ACTIONS.CHECK_PROMOCODE,
            getPromoCode: ACTIONS.GET_PROMOCODE
        }),
        ...mapGetters({
            paymentState: GETTERS.PAYMENT_STATE,
            isCheckFlow: GETTERS.CHECK_FLOW,
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
                    this.performCharge({
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
                            this.mutationsError(error);
                        })
                        .finally(() => {
                            this.processing = false;
                        });
                } else {
                    this.mutationsError(data);
                }
            });
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
        removePromo () {
            this.setInitialPrice();
            this.promoCode = '';
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
            if (this.newUser) {
                this.price = this.finalAmount =
                    process.env[`VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_NEW_USER`];
            } else {
                this.price = this.finalAmount =
                    process.env[`VUE_APP_AMOUNT_${process.env.VUE_APP_CURRENCY}_RETURNING_USER`];
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
        Card
    },
    watch: {
        paymentError (value) {
            console.log('paymentError', value);
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
