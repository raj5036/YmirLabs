<template>
	<button 
		class="btn" 
		:style="{background: color}"
		@click="onClick($event)"
	>
		{{ text }}
	</button>
</template>

<script>
import { onBeforeMount } from 'vue';

	export default {
		name: "Button",
		props: {
			text: String,
			color: String
		},
		methods: {
			onClick (e) {
				console.log(e)
			}
		},
		onBeforeMount () {}
	}
</script>

<style>
</style>