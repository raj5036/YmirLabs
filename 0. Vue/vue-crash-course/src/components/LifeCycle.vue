<template>
	<h1>This is LifeCycle component</h1>
	<p>It illustrates how Vue component lifeCycle works. Please check the console for better understanding</p>
</template>

<script>
	export default {
		name: "LifeCycle",
		props: {},
		data () {
			return {
				count: 10
			}
		},
		beforeCreate () {
			console.log('Nothing gets called at this moment')
        	// `this` points to the view model instance
			console.log('count is ' + this.count);
			// count is undefined
		},
		created: function () {
			// `this` points to the view model instance
			console.log('count is: ' + this.count);
		},
		beforeMount: function () {
      		// `this` points to the view model instance
      		console.log(`this.$el is yet to be created`);
    	},
		mounted: function() {
      		console.log(this.$el); // I'm text inside the component.
    	},
		beforeUnmount () {
			this.count = null;
			delete this.count;
			console.log("destroyed", this.count)
			console.clear()
		},
		unmounted: function() {
      		console.log(this) // Nothing to show here
			console.clear()
    	}
	}
</script>

<style>

</style>