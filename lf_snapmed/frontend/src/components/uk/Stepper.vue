<template>
    <div :class="['stepper-box', vertical && 'vertical']">
        <div class="top">
            <div v-if="vertical" class="divider-line" :style="{height: `${(100/(steps.length) * (steps.length - 1))}%`,width: '1px'}"></div>
            <div v-else class="divider-line" :style="{width: `${(100/(steps.length) * (steps.length - 1)) - 10}%`,height: '1px'}"></div>
            <div class="steps-wrapper">
                <template v-for="(step, index) in steps">
                    <div :class="['step', isStepActive(index, step)]" :key="index" :style="{width: `${100 / steps.length}%`}">
                        <div class="circle">
                            <Icons :icon="step.icon" :selected="isStepActive(index, step)"/>
                        </div>
                        <div v-if="showName" class="step-title">
                            <h4>{{step.title}}</h4>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
import { ACTIONS } from '@/store';
import Icons from '@/components/uk/Icons';

export default {
    name: 'Stepper',
    props: {
        vertical: {
            type: Boolean,
            required: false,
            default: false
        },
        showName: {
            type: Boolean,
            required: false,
            default: true
        },
        steps: {
            type: Array,
            required: true,
            default: () => []
        }
    },
    methods: {
        // Executed when @completed-step event is triggered
        completeStep () {
            this.increaseStepper();
        },
        // Executed when @active-step event is triggered
        isStepActive (index, step) {
            if (this.currentStep === index) {
                return 'activated';
            } else {
                if (this.currentStep > index) {
                    return 'completed';
                } else {
                    return 'deactivated';
                }
            }
        },
        ...mapActions({
            increaseStepper: ACTIONS.INCREASE_STEPPER
        })
    },
    computed: {
        ...mapState({
            'currentStep': state => state.uk.stepper.checkStepper
        })
    },
    components: {Icons}
};
</script>
<style lang="scss">
.vertical{
    position: fixed;
    right: 0;
    top: 170px;
    width: 20% !important;
    .top{
        .steps-wrapper{
            display: flex;
    flex-direction: column;
    row-gap: 25px;
    }
    }
}

</style>
