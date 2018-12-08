const HtmlWebpackPlugin = require('html-webpack-plugin')
const FaviconsPlugin = require('favicons-webpack-plugin')
const FriendlyErrorsPlugin = require('friendly-errors-webpack-plugin')
const webpack = require('webpack')
const {Config} = require('webpack-config')
const {join} = require('path')
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');

const ROOT = join(__dirname, '..')

module.exports = new Config().extend('dev/webpack.base.config.js').merge({
    mode: 'development',
    entry: [
        join(ROOT, 'client/appfd.js'),
        'eventsource-polyfill',
        'webpack-hot-middleware/client?noInfo=true&reload=true'
    ],
    devtool: '#cheap-module-eval-source-map',
    output: {
        pathinfo: true
    },
    devServer: {
        historyApiFallback: true,
        noInfo: true
    },
    performance: {
        hints: false
    },
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
        new webpack.HotModuleReplacementPlugin(),
        new webpack.NoEmitOnErrorsPlugin(),
        new FriendlyErrorsPlugin(),
        new FaviconsPlugin('images/user_logo.png'),
        new webpack.DefinePlugin({
            FACEBOOK_APPID: JSON.stringify('134980666550322'),
            FACEBOOK_GRAFFITI_APPID: JSON.stringify('115376591981611'),
            GOOGLE_CLIENT_ID: JSON.stringify(
                '423761283916-1rpa8120tpudgv4nf44cpmlf8slqbf4f.apps.googleusercontent.com'
            ),
            SITE_NAME: JSON.stringify('Freegle Dev'),
            SITE_DESCRIPTION: JSON.stringify(
                'Give and get stuff for free in your local community.  Don\'t just recycle - reuse, freecycle and freegle!'
            ),
            MODTOOLS: false
        }),
        new HtmlWebpackPlugin({
            hash: true,
            template: join(ROOT, 'client/index.ejs'),
            filename: 'index.html'
        }),
        new MomentLocalesPlugin({
            localesToKeep: ['en' ]
        })
    ]
})
