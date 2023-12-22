<template>
	<header>
		Header {{title}}
	</header>
</template>

<script>
	export default {
		name: "Header",
		props: {
			title: String
		}
	}
</script>

<style scoped lang="css">
	header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
	}
</style>