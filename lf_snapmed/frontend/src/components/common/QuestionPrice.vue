<template>
    <div>
        <div class="promo-code">
            <span v-if="promoCodeInput === 'input'">
                <!-- <input class="promo-code__input" :placeholder="$t('promocode.enter_code_text')" ref="promoCode" v-model="promoCode" v-on:keyup.enter="promoDiscount" v-touppercase> -->
                <input class="promo-code__input" :placeholder="$t('promocode.enter_code_text')" :value="promoCode.toUpperCase()" @input="promoCode = $event.target.value.toUpperCase()" v-on:keyup.enter="promoDiscount">
                <!-- <span class="promo-code__button button button--light button--tight" @click="promoDiscount" v-if="promoCode">✔</span> -->
                <span class="promo-code__button-tick button button--tight-cross-tick" @click="promoDiscount" v-if="promoCode"></span>
                <span class="promo-code__button-cross button button--tight-cross-tick" @click="removePromo"></span>

            </span>
            <span v-else-if="promoCodeInput === 'applied'">
                <label> {{$t('promocode.applied')}} {{ this.promoCode }}  </label>
                <label @click="removePromo" class="promo-code__underline">Remove</label>
            </span>
            <span v-else>
                <label @click="setPromoCodeInput" class="promo-code__underline"> {{$t('promocode.main_text')}}</label>
            </span>

            <span v-if="promoMessage && !promoCode">
                <label> {{ $t('promocode.'+this.promoMessage) }}  </label>
            </span>
        </div>

        <div class="question-price" :key="discount12hrs || discount24hrs">
            <span v-for="option in data.options" :key="option[2]" class="question-price__button">
                <span class="question-price__option" @click="set(option[2])" :class="selected(option[2])">{{$t('questions.' + option[0])}}</span>
                <div v-if="discount12hrs === 0.0 && discount24hrs === 0.0">
                    <span class="question-price__text">{{ amount(option[2], 0.0) }}</span>
                </div>
                <div v-else>
                    <div>
                        <span class="question-price__text_strike_through">{{ amount(option[2], true) }}</span>
                    </div>
                    <div>
                        <span class="question-price__text">{{ amount(option[2], false) }}</span>
                    </div>
                </div>
            </span>
        </div>

    </div>
</template>

<script>

import { mapActions, mapState } from 'vuex';
import { ACTIONS } from '@/store';

export default {
    name: 'QuestionPrice',
    props: {
        data: {
            type: Object,
            required: true,
            validate: object => object.options
        }
    },
    data () {
        return {
            payment: null,
            promoCodeInput: 'initial', // initial, input, applied
            promoCode: '',
            discount12hrs: 0.0,
            discount24hrs: 0.0,
            discountFixed: false,
            promoMessage: '',
            amount12Hrs: 0.0,
            amount24Hrs: 0.0,
            totalAmount: 0.0
        };
    },
    computed: {
        ...mapState({
            region: state => state.region
        })
    },
    methods: {
        ...mapActions({
            setPayment: ACTIONS.UPDATE_PAYMENT,
            checkPromoCode: ACTIONS.CHECK_PROMOCODE
        }),
        amount (time, original) {
            var {AMOUNT_KEY, CURRENCY_LOCALE} = this.$store.getters.AMOUNT(false, time);
            var AMOUNT_FLOAT = parseFloat(AMOUNT_KEY);

            // This is just to render the original strikedthrough price
            if (original) {
                return this.$n(AMOUNT_KEY, 'currency', CURRENCY_LOCALE);
            }

            // Calculating amounts for 12/24 hrs with promocodes
            if (time === 12) {
                if (this.discount12hrs) {
                    this.amount12Hrs = this.calculateNewAmount(AMOUNT_FLOAT, this.discount12hrs, this.discountFixed);
                } else {
                    this.amount12Hrs = AMOUNT_KEY;
                }

                // This is here such that if the duration option is already selected and the card part has already rendered, then just update the total amount
                if (this.payment) {
                    this.set(this.payment);
                }

                return this.$n(this.amount12Hrs, 'currency', CURRENCY_LOCALE);
            } else {
                if (this.discount24hrs) {
                    this.amount24Hrs = this.calculateNewAmount(AMOUNT_FLOAT, this.discount24hrs, this.discountFixed);
                } else {
                    this.amount24Hrs = AMOUNT_KEY;
                }
                // This is here such that if the duration option is already selected and the card part has already rendered, then just update the total amount
                if (this.payment) {
                    this.set(this.payment);
                }

                return this.$n(this.amount24Hrs, 'currency', CURRENCY_LOCALE);
            }
        },
        set (x) {
            this.payment = x;
            this.$emit('addSelectedDuration', this.payment);
            if (x) {
                this.setTotalAmount(x);
                gtag('event', 'set_checkout_option', {'event_category': 'booking', 'event_label': this.payment, 'value': this.data.id});
                this.setPayment({ 'input': this.payment, 'id': this.data.id, 'totalAmount': this.totalAmount, 'promoCode': this.promoCode });
            }
        },
        selected (x) {
            return x === this.payment ? 'question-price__option--selected' : '';
        },
        // Get the promo code details from the backend and set promo details accordingly
        async promoDiscount () {
            const data = await this.checkPromoCode({'promoCode': this.promoCode, 'type': 'cs'});

            if (!data.exists) {
                this.promoMessage = 'not_exists';
                this.resetDiscountAndPromo();
            } else if (!data.valid) {
                this.promoMessage = 'expired';
                this.resetDiscountAndPromo();
            } else if (data.used) {
                this.promoMessage = 'already_used';
                this.resetDiscountAndPromo();
            } else {
                // If the promocode exists, is valid and not used then get details
                // If the promocode is applicable for chat payments since we are handling video and chat promo together
                if (data.applicable) {
                    this.promoMessage = '';
                    this.setDiscount(data);
                } else {
                    this.promoMessage = 'not_applicable';
                    this.resetDiscountAndPromo();
                }
            }
        },
        removePromo () {
            this.resetDiscountAndPromo();
            this.promoCodeInput = 'initial';
            this.promoMessage = '';
        },
        resetDiscountAndPromo () {
            this.discount12hrs = 0.0;
            this.discount24hrs = 0.0;
            this.promoCode = '';
        },
        setTotalAmount (duration) {
            if (duration === 12) {
                this.totalAmount = this.amount12Hrs;
            } else if (duration === 24) {
                this.totalAmount = this.amount24Hrs;
            }
        },
        setDiscount (data) {
            this.discount12hrs = data.discount12hrs;
            this.discount24hrs = data.discount24hrs;
            this.discountFixed = data.discountFixed;

            // Changing the input for promo code to applied
            this.promoCodeInput = 'applied';
        },
        calculateNewAmount (initialAmount, discountValue, discountFixed) {
            if (discountFixed) {
                return String(initialAmount - discountValue);
            } else {
                return (initialAmount - (initialAmount * discountValue / 100));
            }
        },
        setPromoCodeInput () {
            this.promoCodeInput = 'input';
        }
    },
    mounted () {
        if (this.region === 'se') {
            this.payment = this.data.options[0][2];
        }
        gtag('event', 'begin_checkout', {'event_category': 'booking'});
    }
};
</script>

<style lang="scss">
.question-price {
    // Options select
    display: flex;
    flex-flow: row wrap;
    user-select: none;
    justify-content: center;
    // float: right;
    &__button {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: spacing(0.75);
        &:last-of-type {
            margin-right: 0;
        }
    }
    &__text {
        color: color(dark-grey);
    }
    &__text_strike_through {
        color: color(dark-grey);
        text-decoration: line-through;

    }
    &__option {
        @include chat-btn;
    }
}
.promo-code{
    display: flex;
    flex-direction: column;
    &__input {
        // Chat input
        @include chat-input();
        text-transform: uppercase;
    }
    ::placeholder {
    text-transform: none;
    }
    &__button-cross {
        // Next btn
        align-self: flex-end;
        // display: inline !important;
        color: color(dark-grey);
        @include fadeInOut(true);
        @include chat-btn;
        background-image: url('~@assets/chat/cross.svg');
    }
    &__button-tick {
        // Next btn
        align-self: flex-end;
        color: color(dark-grey);
        @include fadeInOut(true);
        @include chat-btn;
        background-image: url('~@assets/chat/tick.svg');
    }
    &__underline {
        text-decoration: underline;
        cursor: pointer;
    }
}
</style>
