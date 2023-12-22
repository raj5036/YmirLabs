module.exports = {
    css: {
        loaderOptions: {
            // pass options to sass-loader
            sass: {
                implementation: require('sass'),
                // @/ is an alias to src/
                data: `@import "@/scss/_settings.scss";`
            }
        }
    },

    devServer: {
        proxy: {
            '/admin/api/*': {
                target: process.env.API_URL
            }
        }
    },

    pluginOptions: {
        i18n: {
            enableInSFC: true
        }
    }
};
