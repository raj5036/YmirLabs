<template>
  <div class="stepper-icon" v-if="icon === 'photo_camera'">
    <svg
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
    <g :opacity="selected === 'completed' || selected === 'activated'  ? 1 : 0.6">
      <path
        d="M15.25 11.254H15.25L15.25 11.246C15.2466 10.3862 14.9035 9.56253 14.2955 8.95452C13.6875 8.34651 12.8638 8.00343 12.004 8.00001V8H12C10.213 8 8.75 9.46143 8.75 11.25C8.75 12.4167 9.37269 13.4451 10.3021 14.0188C9.36603 14.2273 8.45497 14.5812 7.68306 15.0233C7.06337 15.3781 6.49235 15.8131 6.06458 16.3169C5.64101 16.8158 5.3 17.4539 5.3 18.188V19.951V20.7141C5.29762 20.7118 5.29525 20.7095 5.29289 20.7071C5.10536 20.5196 5 20.2652 5 20V4C5 3.73478 5.10536 3.48043 5.29289 3.29289C5.48043 3.10536 5.73478 3 6 3H17.988C18.2532 3 18.5076 3.10536 18.6951 3.29289C18.8826 3.48043 18.988 3.73478 18.988 4V20C18.988 20.1428 18.9575 20.2824 18.9 20.4102V19.951V18.188C18.9 17.44 18.5437 16.7914 18.1068 16.2877C17.6644 15.7776 17.0739 15.3371 16.4325 14.9793C15.6402 14.5373 14.7051 14.1858 13.7451 13.9883C13.9426 13.8621 14.1274 13.714 14.2957 13.5457C14.9036 12.9375 15.2466 12.1138 15.25 11.254ZM6 21C5.89401 21 5.78976 20.9832 5.69082 20.951H6.3H12H17.9H18.2972C18.1982 20.9832 18.094 21 17.988 21H6Z"
        stroke="#5E4098"
        stroke-width="2"
      />
    </g>
    </svg>
  </div>
  <div class="stepper-icon" v-else-if="icon === 'email'">
    <svg
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g :opacity="selected === 'completed' || selected === 'activated'  ? 1 : 0.6">
        <circle cx="12" cy="12" r="9" stroke="#5E4098" stroke-width="2" />
        <line
          x1="11.9004"
          y1="7.90039"
          x2="11.9004"
          y2="12.9004"
          stroke="#5E4098"
          stroke-width="2"
          stroke-linecap="round"
        />
        <circle cx="11.8984" cy="16.5039" r="1.5" fill="#5E4098" />
      </g>
    </svg>
  </div>
  <div class="stepper-icon" v-else-if="icon === 'info'">
    <svg
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g :opacity="selected === 'completed' || selected === 'activated'  ? 1 : 0.6">
        <path
          d="M21 12V4.5C21 4.10218 20.842 3.72064 20.5607 3.43934C20.2794 3.15804 19.8978 3 19.5 3H4.5C4.10218 3 3.72064 3.15804 3.43934 3.43934C3.15804 3.72064 3 4.10218 3 4.5V19.5C3 19.8978 3.15804 20.2794 3.43934 20.5607C3.72064 20.842 4.10218 21 4.5 21H12"
          stroke="#5E4098"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          d="M16 19C17.6569 19 19 17.6569 19 16C19 14.3431 17.6569 13 16 13C14.3431 13 13 14.3431 13 16C13 17.6569 14.3431 19 16 19Z"
          stroke="#5E4098"
          stroke-width="2"
        />
        <path
          d="M18.5 18L21 20"
          stroke="#5E4098"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          d="M7 8H17"
          stroke="#5E4098"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          d="M7 12H11"
          stroke="#5E4098"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </g>
    </svg>
  </div>
  <div class="stepper-icon" v-else-if="icon === 'assignment'">
    <svg
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g :opacity="selected === 'completed' || selected === 'activated'  ? 1 : 0.6">
        <path
          d="M9 12C10.995 12 12.5 10.495 12.5 8.5C12.5 6.505 10.995 5 9 5C7.005 5 5.5 6.505 5.5 8.5C5.5 10.495 7.005 12 9 12ZM10 12.948H8C5.243 12.948 3 15.191 3 17.948V18.948H15V17.948C15 15.191 12.757 12.948 10 12.948ZM21.294 8.24L16.994 12.532L15.702 11.24L14.288 12.654L16.994 15.358L22.706 9.656L21.294 8.24Z"
          fill="#5E4098"
        />
      </g>
    </svg>
  </div>
  <div class="stepper-icon" v-else-if="icon === 'credit_card'">
    <svg
      width="18"
      height="14"
      viewBox="0 0 18 14"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <g :opacity="selected === 'completed' || selected === 'activated'  ? 1 : 0.6">
        <path
          d="M0 2.44207C0 1.79439 0.252856 1.17324 0.702944 0.715265C1.15303 0.257289 1.76348 0 2.4 0H15.6C16.2365 0 16.847 0.257289 17.2971 0.715265C17.7471 1.17324 18 1.79439 18 2.44207V4.27362H0V2.44207Z"
          fill="#5E4098"
        />
        <path
          d="M0 5.49512H18V10.9896C18 11.6373 17.7471 12.2584 17.2971 12.7164C16.847 13.1744 16.2365 13.4316 15.6 13.4316H2.4C1.76348 13.4316 1.15303 13.1744 0.702944 12.7164C0.252856 12.2584 0 11.6373 0 10.9896V5.49512ZM13.2 10.3791C13.0409 10.3791 12.8883 10.4435 12.7757 10.5579C12.6632 10.6724 12.6 10.8277 12.6 10.9896C12.6 11.1515 12.6632 11.3068 12.7757 11.4213C12.8883 11.5358 13.0409 11.6001 13.2 11.6001H15.6C15.7591 11.6001 15.9117 11.5358 16.0243 11.4213C16.1368 11.3068 16.2 11.1515 16.2 10.9896C16.2 10.8277 16.1368 10.6724 16.0243 10.5579C15.9117 10.4435 15.7591 10.3791 15.6 10.3791H13.2Z"
          fill="#5E4098"
        />
      </g>
    </svg>
  </div>
</template>
<script>
export default {
    name: 'Icons',
    props: {
        icon: {
            type: String,
            required: true
        },
        selected: {
            type: String,
            default: 'deactivated'
        }
    }
};
</script>
<style lang="scss">
.stepper-icon {
  svg {
    width: 24px;
    height: 24px;
  }
  border: 2px solid #e1f3f3;
  border-radius: 100%;
  padding: 10px;
  display: flex;
}
</style>
