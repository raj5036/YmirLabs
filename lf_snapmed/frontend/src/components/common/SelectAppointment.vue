<template>
    <div class="select-appointment">
        <!-- <header class="select-appointment__header">
        </header> -->
        <div
            v-h-scroll="onHScroll"
            v-h-scroll-reset="`${selectedYear}-${selectedWeek}`"
            :class="{ 'select-appointment__body--end': this.endOfXScroll }"
            class="select-appointment__body"
        >
            <div class="calender-header">
                <div class="navigation-div" @click.prevent.stop="prev">
                    <svg-asset
                        icon="chevron-left"
                        class="select-appointment__cta-prev"
                    />
                </div>
                <div class="calender-content">
                    <div
                        v-for="week in activeWeek"
                        :key="`${week.year}-${week.week}`"
                        :data-week="week.year + week.week"
                        class="select-appointment__week"
                        ref="calenderHeader"
                    >
                        <div
                            v-for="day in week.days"
                            :key="day.date"
                            class="select-appointment__day"
                        >
                            <header class="select-appointment__day-header">
                                <button
                                    class="select-appointment__day-date"
                                    :data-date="day.date"
                                    :disabled="getSlotsLengthforDate(day.date)"
                                    @click="setSelectedDate"
                                    :class="{
                                        selected:
                                            selectedDayObj.date === day.date
                                    }"
                                >
                                    <div
                                        class="select-appointment__day-of-week"
                                    >
                                        {{ day.dayOfWeek }}
                                    </div>
                                    <h3>{{ day.dayOfMonth }}</h3>
                                    <small>{{ day.month }}</small>
                                </button>
                            </header>
                        </div>
                    </div>

                    <div class="slots" v-if="selectedDayObj.slots">
                        <template v-if="selectedDayObj.slots.length > 0">
                            <div
                                v-for="appointment in selectedDayObj.slots"
                                :key="appointment.id"
                                class="slots-div"
                                :class="{
                                    selected: appointment.selected
                                }"
                                @click="toggle(appointment)"
                            >
                                {{ appointment.startTime }}
                            </div>
                        </template>
                        <template v-else>
                            <span>{{ $t("page.video.book.no_slots") }}</span>
                        </template>
                    </div>
                </div>

                <div class="navigation-div" @click.prevent.stop="next">
                    <svg-asset
                        class="select-appointment__cta-next"
                        icon="chevron-right"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import _ from 'lodash';
import moment from 'moment';

const flyLeft = [
    { transform: 'translateX(0)', opacity: 1 },
    { transform: 'translateX(-30px)', opacity: 0 }
];

const flyRight = [
    { transform: 'translateX(0)', opacity: 1 },
    { transform: 'translateX(30px)', opacity: 0 }
];

export default {
    name: 'SelectAppointment',
    props: {
        value: {
            type: [Object, Array],
            required: true
        },
        selectedWeek: {
            type: Number,
            required: true
        },
        selectedYear: {
            type: Number,
            required: true
        },
        isFirstWeek: {
            type: Boolean,
            required: true
        }
    },
    data () {
        return {
            transitionName: 'fade',
            endOfXScroll: false,
            selectedDayObj: {
                date: null
            }
        };
    },
    computed: {
        isToday: () => date => {
            return false;
        },
        activeWeek () {
            return _.filter(this.value || [], { week: this.selectedWeek });
        }
    },
    mounted: function () {
        const appointment = this.$attrs.appointment;
        if (appointment) this.updateSlotsState(moment(appointment.timestamp).format('YYYY.MM.DD'));
        else this.updateSlotsState(moment().format('YYYY.MM.DD'));
    },
    methods: {
        getSlotsLengthforDate (date) {
            const selectedWeekSlots = this.value.find(
                weekSlots => weekSlots.week === this.selectedWeek
            );
            const slotsObj = selectedWeekSlots.days.find(
                daySlots => daySlots.date === date
            );
            return !(slotsObj.slots.length > 0);
        },
        setSelectedDate (e) {
            const selectedDate = e.currentTarget.dataset.date;
            this.updateSlotsState(selectedDate);
        },
        updateSlotsState (selectedDate) {
            const selectedWeekSlots = this.value.find(
                weekSlots => weekSlots.week === this.selectedWeek
            );
            const slotsObj = selectedWeekSlots.days.find(
                daySlots => daySlots.date === selectedDate
            );
            this.selectedDayObj = slotsObj;
        },
        toggle (appointment) {
            this.$emit(
                'selected',
                Object.assign(appointment, { selected: !appointment.selected })
            );
        },
        next () {
            const el = this.$refs.calenderHeader[0];
            const scrollWidth = el.scrollWidth;
            const width = el.offsetWidth;
            const scrolled = el.scrollLeft;
            const yetToScroll = scrollWidth - (scrolled + width);
            const blocksLeft = Math.floor(yetToScroll / (70 + 6));
            const blocksInSpace = Math.floor(width / (70 + 6));

            if (yetToScroll < 5) {
                this.transitionName = 'slide-left';
                const anim = el.animate(flyLeft, 200);
                anim.finished.then(() => {
                    this.$emit('gotoNextWeek');
                });
            } else {
                if (blocksLeft < blocksInSpace) {
                    el.scroll(scrollWidth - width, 0);
                } else {
                    el.scroll(scrolled + width, 0);
                }
            }
        },
        prev () {
            const el = this.$refs.calenderHeader[0];
            const scrollWidth = el.scrollWidth;
            const width = el.offsetWidth;
            const scrolled = el.scrollLeft;
            const yetToScroll = scrollWidth - (scrolled + width);
            const blocksLeft = Math.floor(yetToScroll / (70 + 6));
            const blocksInSpace = Math.floor(width / (70 + 6));

            if (yetToScroll === (scrollWidth - width)) {
                if (!this.isFirstWeek) {
                    this.transitionName = 'slide-right';
                    const anim = el.animate(flyRight, 200);
                    anim.finished.then(() => {
                        this.$emit('gotoPrevWeek');
                        setTimeout(() => {
                            const el = this.$refs.calenderHeader[0];
                            el.scroll({left: scrollWidth - width,
                                behavior: 'instant'});
                        }, 100);
                    });
                    setTimeout(() => {
                        const el = this.$refs.calenderHeader[0]; el.scroll({
                            left: scrollWidth - width,
                            behavior: 'instant'
                        });
                    }, 200);
                }
            } else {
                if (blocksLeft > blocksInSpace) {
                    el.scroll(scrollWidth + width, 0);
                } else {
                    el.scroll(scrolled - width, 0);
                }
            }
        },
        onHScroll (e, elem) {
            if (elem.scrollLeft + elem.clientWidth >= elem.scrollWidth) {
                this.endOfXScroll = true;
            } else {
                this.endOfXScroll = false;
            }
        },
        onVScroll (e, elem) {
            if (elem.scrollTop + elem.clientHeight >= elem.scrollHeight) {
                if (
                    !elem.parentNode.classList.contains(
                        'select-appointment__day-body--end'
                    )
                ) {
                    elem.parentNode.classList.add(
                        'select-appointment__day-body--end'
                    );
                }
            } else {
                if (
                    elem.parentNode.classList.contains(
                        'select-appointment__day-body--end'
                    )
                ) {
                    elem.parentNode.classList.remove(
                        'select-appointment__day-body--end'
                    );
                }
            }
        }
    }
};
</script>

<style lang="scss">
.select-appointment {
    min-height: 350px;
    position: relative;
    color: white;
    .navigation-div {
        height: min-content;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin-top: 48px;
        svg {
            path {
                stroke: white;
            }
        }
    }
    .slots {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 80px;
        .slots-div {
            background: white;
            color: $dd-text-colour;
            width: 206px;
            height: 64px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            @include breakpoint(small only){
                width: 106px;
            }
        }
        .selected {
            box-shadow: 0px 0px 0px 2px #4d4557, 0px 0px 0px 5px #ac94ce;
            background: #dfd8ea;
            border-radius: 12px;
        }
    }
    .calender-content{
        @include breakpoint(small only){
            width: 78%;
        }
    }
    .calender-header {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        @include breakpoint(small only){
            width: calc(100vw - 16px * 2);
            // padding: 16px;
            gap: 4px;
        }
    }
    .selected {
        box-shadow: 0px 0px 0px 2px #4d4557, 0px 0px 0px 5px #ac94ce;
        background: #dfd8ea;
        border-radius: 12px;
    }
    &__header {
        border: 1px solid color(medium-grey);
        border-top-width: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: spacing(2);
        font-weight: 600;
        @include breakpoint(small only) {
            position: sticky;
            top: 0;
            background-color: color(white);
            z-index: 10;
            padding: spacing(2) spacing(1);
        }
    }
    &__body {
        @include breakpoint(small only) {
            position: relative;
            max-width: 100%;
            // overflow-x: auto;
        }
    }
    &__cta-prev,
    &__cta-next {
        display: inline-flex;
        align-items: center;
        @include breakpoint(small only) {
            font-size: 87.5%;
            line-height: 1.2;
        }
    }
    &__cta-prev {
        padding-left: spacing(0.5);
        &--disabled {
            opacity: 0.75;
            text-decoration: none;
            cursor: not-allowed;
            .svg-asset {
                opacity: 0.75;
            }
        }
        .svg-asset {
            margin-right: spacing();
        }
    }
    &__cta-next {
        .svg-asset {
            margin-left: spacing();
            padding-right: spacing(0.5);
        }
    }
    &__current {
        font-size: 125%;
        text-align: center;
        @include breakpoint(small only) {
            font-size: $font-medium;
            margin: 0 spacing(1);
        }
        min-width: 40%;
    }
    &__week {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        scroll-behavior: smooth;
        @include breakpoint(small only){
            // width: 60%;
            overflow: scroll;
            padding: 10px;
            scroll-snap-type: x mandatory;
            scroll-snap-points-x: repeat(70px);
        }
        // border-bottom: 1px solid color(medium-grey);
    }
    &__day {
        $day: &;
        display: flex;
        flex-flow: column;
        flex: 1 1 14.28%;
        @include breakpoint(small only){
            scroll-snap-align: center;
        }
        // border-right: 1px solid color(medium-grey);
        // border-bottom: 1px solid color(medium-grey);
        // &:first-of-type {
        //     border-left: 1px solid color(medium-grey);
        // }
        // @include breakpoint(small only) {
        //     min-width: 32vw;
        // }
        &-header {
            display: flex;
            flex-flow: column;
            justify-content: flex-start;
            align-content: center;
            @include breakpoint(small only){
                width: 70px;
            }
        }
        &-body {
            position: relative;
            display: block;
            width: 100%;
            max-height: 240px;
            @include breakpoint(medium) {
                max-height: 360px;
            }
        }
        &-scroll {
            position: relative;
            max-height: inherit;
            overflow-y: auto;
            @include scrollbars(8px, color(mid-purple), color(subtle-purple));
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-block: 12px;
        }
        &-empty {
            text-align: center;
            padding: spacing(3) spacing(2);
            color: $dd-text-colour-light;
            font-size: 87.5%;
        }
        &-date {
            text-align: center;
            padding: spacing(2);
            border: 1px solid color(medium-grey);
            border-radius: 12px;
            background: #FFFFFF;
            display: flex;
            flex-direction: column;
            width: 72px;
            height: 120px;
            &:disabled{
               background: #3E3846;
               & *{color: white;}
               border: none;
            }
            @include breakpoint(small only){
                padding: spacing(1);
            }
            small {
                font-size: $font-small-medium;
            }
            h3 {
                font-size: $font-large;
                margin: 0;
                @include breakpoint(small only){
                }
            }
        }
        &-hour {
            background-color: white;
            width: 100px;
            border-radius: 8px;
            padding: spacing(2) spacing(0.5);
            text-align: center;
            transition: all 100ms ease-in-out;
            user-select: none;
            &:hover {
                // transform: scale(1.05) translateZ(0);
                cursor: pointer;
                background-color: #dfd8ea;
            }
            &:active {
                box-shadow: inset 0px 0px 2px #c1c1c1;
                outline: none;
                background-color: darken(#f4e7ff, 5%);
                transform: scale(1);
            }
            &--selected {
                background-color: color(blueberry);
                color: color(white);
                &:hover {
                    background-color: color(blueberry);
                }
            }
            @include breakpoint(small only) {
                margin: spacing(0.75) spacing(0.5) 0;
                padding: spacing(1.25) spacing(0.5);
            }
        }
    }
     &__diabled-day{
        background: rgba(0, 0, 0, 0.2);
        border-top-width: 1px;
        border-bottom-width: 1px;
        text-transform: capitalize;
        font-size: $font-small-medium;
        text-align: center;
        color: #fff;
    }
    &__day-of-week {
        border-top-width: 1px;
        border-bottom-width: 1px;
        text-transform: capitalize;
        font-size: $font-small-medium;
        text-align: center;
        color: #000000;
    }
    h3{
        color: #000000;
    }
    small{
        color: #000000;
    }
}
</style>
