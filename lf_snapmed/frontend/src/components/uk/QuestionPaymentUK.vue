<template lang="">
    <section class="question-payment-new">
        <AutoFocus/>
        <div class="question-payment-new__container">
            <transition name="fade">
                <div v-if="processing" class="question-payment__processing">
                <svg-loader />
                <h3>
                    {{ $t('page.check.payment_processing') }}
                </h3>
                </div>
            </transition>
            <div
                class="question-payment-new__flex-alignment"
                @click="changePromoBoxState()"
            >
                <div class="question-payment-new__text">
                    {{ $t('page.check.payment.promo') }}
                </div>
                <img
                    v-if="!showPromoBox"
                    src="@assets/icons/down-arrow.svg"
                    title="Snapmed"
                    alt="Snapmed"
                    height="50"
                    class="question-payment-new__icon"
                />
                <img
                    v-else
                    src="@assets/icons/up-arrow.svg"
                    title="Snapmed"
                    alt="Snapmed"
                    height="50"
                    class="question-payment-new__icon"
                />
            </div>
            <div
                v-if="showPromoBox"
                class="question-payment-new__promo-container"
            >
                <div class="question-payment-new__flex-alignment">
                    <div
                        class="question-payment-new__flex-alignment question-payment-new__promo-input"
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
                            @input="
                                promoCode = $event.target.value.toUpperCase()
                            "
                        />
                        <img
                            src="@assets/icons/cancel.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__icon pointer"
                            @click="removePromo()"
                        />
                    </div>
                    <hide-at breakpoint="small">
                        <button
                            :disabled="
                                (promoCode.length > 0 ? false : true) ||
                                    promoApplied
                            "
                            class="button--dark question-payment-new__button"
                            :style="
                                (promoCode.length > 0 ? false : true) ||
                                promoApplied
                                    ? { background: '#C3C6CB' }
                                    : null
                            "
                            @click="promoDiscount()"
                        >
                            {{ $t('page.check.apply') }}
                        </button>
                    </hide-at>
                    <show-at breakpoint="small">
                        <button
                            :disabled="
                                (promoCode.length > 0 ? false : true) ||
                                    promoApplied
                            "
                            class="button--dark question-payment-new__button"
                            :style="
                                (promoCode.length > 0 ? false : true) ||
                                promoApplied
                                    ? { background: '#C3C6CB' }
                                    : null
                            "
                            @click="promoDiscount()"
                        >
                            <img
                                src="@assets/icons/right-arrow.svg"
                                title="Snapmed"
                                alt="Snapmed"
                                class="pointer"
                            />
                        </button>
                    </show-at>
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
                        $t('promocode.' + this.promoMessage)
                    }}</span>
                </div>
                <div
                    v-if="promoApplied && !promoMessage"
                    class="question-login-password__error-container question-payment__promo-error"
                >
                    <img
                        src="@assets/icons/success.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-login-password__icon"
                    />
                    <span
                        class="question-login-password__error"
                        style="color:#64BD79"
                        >{{ $t('promocode.success') }}</span
                    >
                </div>
            </div>
        </div>
        <show-at breakpoint="small">
            <div
                class="question-payment-new__card"
                @click="changeCardState()"
                :style="
                    showCard
                        ? {
                              'margin-bottom': '0px',
                              'border-bottom-right-radius': '0px',
                              'border-bottom-left-radius': '0px',
                              'box-shadow': '0px 13px 15px 2px rgb(0 0 0 / 15%)'
                          }
                        : null
                "
            >
                <div class="question-payment-new__card-header"></div>
                <div
                    class="question-payment-new__card-container"
                    :style="showCard ? { padding: '30px 16px 0px 30px' } : null"
                >
                    <div
                        class="question-payment-new__flex-alignment"
                        :style="
                            showCard
                                ? {
                                      'border-bottom': '1px solid #DEE0E3',
                                      padding: '0px 0px 30px 0px'
                                  }
                                : null
                        "
                    >
                        <div class="question-payment-new__flex-alignment">
                            <div class="question-payment-new__payment-text">
                                {{ $t('page.check.payment.summary') }}
                            </div>
                        </div>
                        <div class="question-payment-new__payment-text">
                            {{ amount(false) }}
                            <img
                                v-if="showCard"
                                src="@assets/icons/up-arrow.svg"
                                title="Snapmed"
                                alt="Snapmed"
                                class="question-payment-new__icon"
                                style="marginLeft: 15px"
                            />
                            <img
                                v-else
                                src="@assets/icons/down-arrow.svg"
                                title="Snapmed"
                                alt="Snapmed"
                                class="question-payment-new__icon"
                                style="marginLeft: 15px"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </show-at>
        <div
            class="question-payment-new__card"
            :style="
                showCard
                    ? {
                          'border-top-right-radius': '0px',
                          'border-top-left-radius': '0px',
                          'box-shadow': '0px 13px 15px 2px rgb(0 0 0 / 15%)'
                      }
                    : null
            "
        >
            <hide-at breakpoint="small"
                ><div class="question-payment-new__card-header"></div
            ></hide-at>
            <div
                v-if="showCard"
                class="question-payment-new__column-alignment question-payment-new__card-container"
            >
                <div class="question-payment-new__card-microcopy">
                    {{ $t('page.check.payment.details') }}
                </div>
                <div
                    v-if="this.isCheckFlow"
                    class="question-payment-new__title"
                >
                    {{ $t('header.picture_consultation') }}
                </div>
                <div
                    class="question-payment-new__flex-alignment question-payment-new__card-body"
                >
                    <img
                        src="@assets/icons/clock.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-payment-new__icon"
                        style="marginRight: 15px"
                    />
                    <div class="question-payment-new__card-content">
                        {{ $t('page.check.payment.description') }}
                    </div>
                </div>
                <div
                    v-if="promoApplied && !promoMessage"
                    class="question-payment-new__payment-box"
                >
                    <div class="question-payment-new__flex-alignment">
                        <div class="question-payment-new__payment-text">
                            {{ $t('page.check.payment.total') }}
                        </div>
                        <div class="question-payment-new__payment-text">
                            {{ amount(true) }}
                        </div>
                    </div>
                    <div
                        class="question-payment-new__flex-alignment question-payment-new__promo-box"
                    >
                        <div class="question-payment-new__flex-alignment">
                            <img
                                src="@assets/icons/promo.svg"
                                title="Snapmed"
                                alt="Snapmed"
                                class="question-payment-new__promo-icon"
                            />
                            <div class="question-payment-new__payment-subtext">
                                {{ $t('page.check.payment.promo_applied') }}
                            </div>
                        </div>
                        <div class="question-payment-new__payment-coupon-code">
                            {{ promoCode.toUpperCase() }}
                        </div>
                    </div>
                </div>
                <div
                    class="question-payment-new__flex-alignment question-payment-new__payment-box"
                >
                    <div class="question-payment-new__payment-text">
                        {{ $t('page.check.payment.pay') }}
                    </div>
                    <div class="question-payment-new__payment-text">
                        {{ amount(false) }}
                    </div>
                </div>
            </div>
        </div>
        <show-at breakpoint="small">
            <div class="question-payment-new__flex-center">
                <div class="question-payment-new__microcopy">
                    {{ $t('page.check.payment.secure') }}
                </div>
                <img
                    src="@assets/icons/check-badge.svg"
                    title="Snapmed"
                    alt="Snapmed"
                    height="50"
                    class="question-payment-new__icon"
                />
            </div>
        </show-at>
        <show-at breakpoint="small">
            <div
                class="question-payment-new__container question-payment-new__flex-alignment question-payment-new__responsive-container"
                style="width:100%"
            >
                <div class="question-payment-new__flex-alignment">
                    <img
                        src="@assets/icons/radio-active.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-payment-new__icon"
                    />
                    <div>Pay with card</div>
                </div>
                <div class="question-payment-new__flex-alignment">
                    <img
                        src="@assets/logo/amex.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-payment-new__logo"
                    />
                    <img
                        src="@assets/logo/visa.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-payment-new__logo"
                    />
                    <img
                        src="@assets/logo/mastercard.svg"
                        title="Snapmed"
                        alt="Snapmed"
                        class="question-payment-new__logo"
                    />
                </div>
            </div>
        </show-at>
        <div
            class="question-payment-new__container question-payment-new__responsive-container-styling"
        >
            <hide-at breakpoint="small">
                <div class="question-payment-new__flex-alignment">
                    <div class="question-payment-new__flex-alignment">
                        <div class="question-payment-new__microcopy">
                            {{ $t('page.check.payment.secure') }}
                        </div>
                        <img
                            src="@assets/icons/check-badge.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            height="50"
                            class="question-payment-new__icon"
                        />
                    </div>
                    <div></div>
                </div>
            </hide-at>
            <hide-at breakpoint="small">
                <div
                    class="question-payment-new__card-input question-payment-new__payment-method-container question-payment-new__flex-alignment"
                    style="width:100%"
                >
                    <div class="question-payment-new__flex-alignment">
                        <img
                            src="@assets/icons/radio-active.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__icon"
                        />
                        <div>{{ $t('page.check.payment.card') }}</div>
                    </div>
                    <div class="question-payment-new__flex-alignment">
                        <img
                            src="@assets/logo/amex.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__logo"
                        />
                        <img
                            src="@assets/logo/visa.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__logo"
                        />
                        <img
                            src="@assets/logo/mastercard.svg"
                            title="Snapmed"
                            alt="Snapmed"
                            class="question-payment-new__logo"
                        />
                    </div>
                </div>
            </hide-at>
            <hide-at breakpoint="small"
                ><div
                    class="question-payment-new__text"
                    style="margin: 30px 0px;"
                >
                    {{ $t('page.check.payment.card_details') }}
                </div></hide-at
            >
            <div class="question-payment-new__card-text">
                {{ $t('page.check.payment.card_number') }}
            </div>
            <stripe-element
                type="cardNumber"
                class="question-payment-new__card-number"
                :stripe="stripeKey"
                :options="newStripeOptions"
                @change="cardNumberComplete = $event.complete"
            ></stripe-element>
            <div class="question-payment-new__flex-alignment">
                <div style="width:100%">
                    <div class="question-payment-new__card-text">
                        {{ $t('page.check.payment.expiry') }}
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
                        {{ $t('page.check.payment.cvc') }}
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
        <div class="page-checkup__continue">
        <span></span>
        <div class="buttons-div">
             <button
                        @click="goBack"
                        class="button--back"
                        v-if="currentStep !== 0"
            >
                {{ $t('common.go_back') }}
            </button>
        <button
            :disabled="
                processing ||
                    !cardNumberComplete ||
                    !cardExpiryComplete ||
                    !cardCvcComplete
            "
            class="button--next"
            @click="token"
        >
            <span>{{ $t('btn.charge') }}</span>
        </button>
        </div>
        </div>
    </section>
</template>

<script>
import { mapActions, mapState, mapGetters, mapMutations } from 'vuex';
import { ACTIONS, GETTERS, MUTATIONS } from '@/store';
import { createPaymentMethod, StripeElement } from 'vue-stripe-elements-plus';
import { has } from 'lodash';
import { showAt, hideAt } from 'vue-breakpoints';
import AutoFocus from '@common/AutoFocus';

export default {
    name: 'QuestionPaymentUK',
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
        })
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
            goBack: ACTIONS.CHANGE_PAGE,
            charge: ACTIONS.UPDATE_PAYMENT,
            performCharge: ACTIONS.CHARGE_CARD,
            confirm: ACTIONS.CONFIRM_QUESTION_PAYMENT,
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
        StripeElement,
        showAt,
        hideAt,
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
