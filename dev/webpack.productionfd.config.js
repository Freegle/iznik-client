const HtmlWebpackPlugin = require('html-webpack-plugin')
const FaviconsPlugin = require('favicons-webpack-plugin')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const {BundleAnalyzerPlugin} = require('webpack-bundle-analyzer')
const webpack = require('webpack')
const {Config} = require('webpack-config')
const {join} = require('path')
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');

const ROOT = join(__dirname, '..')

module.exports = new Config().extend('dev/webpack.base.config.js').merge({
    mode: 'production',
    devtool: 'source-map',
    entry: [join(ROOT, 'client/appfd.js')],
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    {
                        loader: 'css-loader',
                        options: {
                            root: '../'
                        }
                    }
                ]
            }
        ]
    },
    plugins: [
        new BundleAnalyzerPlugin({
            analyzerMode: 'static',
            reportFilename: 'bundlesize.html',
            defaultSizes: 'gzip',
            openAnalyzer: false,
            generateStatsFile: false,
            statsFilename: 'stats.json',
            statsOptions: null,
            logLevel: 'info'
        }),
        new MiniCssExtractPlugin({
            filename: "[name].[chunkhash].css"
        }),
        new FaviconsPlugin('images/user_logo.png'),
        new webpack.DefinePlugin({
            FACEBOOK_APPID: JSON.stringify('134980666550322'),
            FACEBOOK_GRAFFITI_APPID: JSON.stringify('115376591981611'),
            GOOGLE_CLIENT_ID: JSON.stringify(
                '423761283916-1rpa8120tpudgv4nf44cpmlf8slqbf4f.apps.googleusercontent.com'
            ),
            SITE_NAME: JSON.stringify('Freegle'),
            SITE_DESCRIPTION: JSON.stringify(
                'Give and get stuff for free in your local community.  Don\'t just recycle - reuse, freecycle and freegle!'
            ),
            MODTOOLS: false
        }),
        new HtmlWebpackPlugin({
            hash: true,
            template: join(ROOT, 'client/index.ejs'),
            filename: 'index.html',
            inject: true,
            minify: {
                removeComments: true,
                collapseWhitespace: true,
                removeAttributeQuotes: true
                // more options:
                // https://github.com/kangax/html-minifier#options-quick-reference
            },
            // necessary to consistently work with multiple chunks via CommonsChunkPlugin
            chunksSortMode: 'dependency'
        }),

        // do scope hoisting: https://webpack.js.org/plugins/module-concatenation-plugin
        // should reduce scripting time and bundle size
        new webpack.optimize.ModuleConcatenationPlugin(),

        // Reduces moment size
        new MomentLocalesPlugin({
            localesToKeep: ['en']
        })
    ]
})
