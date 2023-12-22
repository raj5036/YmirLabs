<template>
    <div class="phone-number">
        <span class="phone-number__location">
            <img :src="dropdownImage" class="phone-number__location-flag" />
            <select class="phone-number__location-select" v-model="dropdown">
                <option
                    v-for="pb in allCountries"
                    :key="pb['iso2']"
                    :value="pb['iso2']"
                    v-html="pb.name"
                ></option>
            </select>
        </span>
        <input
            type="tel"
            v-model="countryCode"
            :disabled="true"
            :class="number === null ? 'phone-number__code' : 'phone-number__code-disabled'"
        />
        <input
            type="tel"
            autocomplete="new-phonenumber"
            class="phone-number__input"
            :placeholder="placeholder"
            ref="phonenumberinput"
            v-model="inputNumber"
            v-on:keyup.enter="enterKey"
            :disabled="disabled"
        />
    </div>
</template>

<script>
// Assets
import { formatNumber, AsYouType, isValidNumber } from 'libphonenumber-js';
import allCountries from '@/countries.js';

// Login
export default {
    name: 'PhoneNumber',
    props: {
        placeholder: {
            type: String
        },
        value: {},
        focus: {},
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data: function () {
        return {
            allCountries,
            activeCountry: allCountries[0], // Country selected
            dropdown: null,
            inputNumber: '',
            onMount: true
        };
    },
    mounted: async function () {
        // Set language to no
        await this.changeLanguage(
            (process.env.VUE_APP_COUNTRY || 'US').toUpperCase()
        );
        this.onMount = false;
    },
    computed: {
        formattedResult () {
            // Formatted number
            return this.phoneNumber && this.activeCountry
                ? formatNumber(
                    this.phoneNumber,
                    this.activeCountry && this.activeCountry.iso2,
                    'International'
                )
                : '';
        },
        number () {
            // Return number
            return isValidNumber(this.formattedResult)
                ? this.formattedResult
                : null;
        },
        phoneNumber () {
            // Remove 0 or + prefix
            let nr = this.inputNumber || null;
            return nr && nr[0] === '0' ? nr.slice(1) : nr;
        },
        dropdownImage () {
            // Dropdown image
            return require('@/assets/flags/' +
                this.activeCountry.iso2 +
                '.svg');
        },
        countryCode () {
            return `+${this.activeCountry.dialCode}`;
        }
    },
    methods: {
        changeLanguage (langCode) {
            // Set active country by lang code
            this.dropdown = langCode;
            for (let l of this.allCountries) {
                if (l['iso2'] === langCode) {
                    this.activeCountry = l;
                }
            }
        },
        enterKey () {
            this.$emit('enter', this.number);
        }
    },
    watch: {
        value (value) {
            this.inputNumber = value;
        },
        dropdown (value) {
            // Dropdown change
            this.changeLanguage(value);
        },
        number (value) {
            // Emit number
            this.$emit('input', this.number);
            if (value) {
                // Update visual valid number
                this.inputNumber = this.formattedResult;
            }
        },
        phoneNumber () {
            // Try to format
            const asYouType = new AsYouType();
            asYouType.input(this.phoneNumber);
            if (asYouType.country !== undefined) {
                this.changeLanguage(asYouType.country);
            }
        },
        activeCountry () {
            if (!this.onMount) {
                this.inputNumber = null;
                this.phoneNumber = null;
            }
        }
    }
};
</script>
