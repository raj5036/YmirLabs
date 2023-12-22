const PrerenderSpaPlugin = require('prerender-spa-plugin');
const Renderer = PrerenderSpaPlugin.PuppeteerRenderer;
const path = require('path');

const outputBase = process.env.NODE_ENV === 'production' ? 'webroot' : 'dist';

process.env.VUE_APP_VERSION = require('./package.json').version;
const aliases = {
    resolve: {
        extensions: ['.js', '.vue', '.json', '.scss', '.css'],
        alias: {
            '@assets': path.resolve(__dirname, 'src/assets'),
            '@components': path.resolve(__dirname, 'src/components'),
            '@common': path.resolve(__dirname, 'src/components/common')
        }
    }
};
module.exports = {
    css: {
        loaderOptions: {
            sass: {
                data: '@import "@/scss/_settings.scss";'
            }
        }
    },

    outputDir:
        process.env.NODE_ENV === 'development'
            ? outputBase
            : path.resolve(__dirname, outputBase, process.env.VUE_APP_SITE),

    configureWebpack: config => {
        if (process.env.NODE_ENV === 'development') {
            return { ...aliases };
        } else {
            return {
                ...aliases,
                plugins: [
                    new PrerenderSpaPlugin({
                        // Absolute path to compiled SPA
                        staticDir: path.resolve(
                            __dirname,
                            outputBase,
                            process.env.VUE_APP_SITE
                        ),
                        // List of routes to prerender
                        routes: [
                            '/login',
                            '/book',
                            '/partner',
                            '/bankid-return'
                        ],
                        renderer: new Renderer({
                            timeout: 0,
                            maxConcurrentRoutes: 4,
                            renderAfterTime: 5000,
                            headless: false
                        })
                    })
                ]
            };
        }
    },

    pluginOptions: {
        i18n: {
            enableInSFC: true
        }
    },
    lintOnSave: true,
    chainWebpack: config => {
        config.optimization.splitChunks(false);
    },

    devServer: {
        // host: 'app.snapmed.co.local',
        proxy: {
            '/api/*': {
                target: process.env.API_URL,
                secure: false
            }
        }
    },

    baseUrl: undefined,
    assetsDir: undefined,
    runtimeCompiler: undefined,
    productionSourceMap: false,
    parallel: undefined,
    pages: {
        index: {
            // entry for the page
            entry: 'src/main.js',
            // the source template
            template: 'public/index.html',
            // output as dist/index.html
            filename: 'index.html'
        }
    }
};
