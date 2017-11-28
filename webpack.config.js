var webpack = require('webpack');

var wc = require('webpack-config');

var Config = wc.Config;
var environment = wc.environment;
var theenv = typeof(process.env.NODE_ENV) == 'undefined' ? 'development' : process.env.NODE_ENV;

environment.setAll({
    env: () => theenv
});

module.exports = new Config().extend('webpack.' + theenv + '.config.js');
