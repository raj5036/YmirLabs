<template>
    <v-container fluid fill-height>
        <v-layout align-center justify-center>
            <v-flex>
                <div class="text-xs-center">
                    <img src="../../assets/check-badge.svg" class="badge" />
                    <h1> Your profile has been created ! </h1>
                    <p style="margin-top:20px;">You can now start consulting with snapmed.</p>
                    <br/>
                    <v-btn class="button"
                        @click.native.stop="login"
                        color="primary">
                        Let's start
                    </v-btn>
                    <img src="../../assets/woman-with-documents-from-right.svg" class="image" />
                </div>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
export default {
    methods: {
        async login () {
            this.$router.push({ name: `login` });
        }
    }
};
</script>

<style lang="scss" scoped>
.badge {
    margin-bottom: 30px;
}
.image {
    position: absolute;
    right: 0;
    bottom: 0;
    margin-right: 10%;
}
.button {
    width: 226px;
    margin-top: 25px;
}
</style>
