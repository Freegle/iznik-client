var wc = require('webpack-config');
var Config = wc.Config;
const FaviconsPlugin = require('favicons-webpack-plugin');

exports['default'] = new Config().extend('webpack.base.config.js').merge({
    devtool: '#source-map',
    output: {
        pathinfo: true
    },
    plugins: [
        new FaviconsPlugin('images/user_logo.png'),
    ]
});