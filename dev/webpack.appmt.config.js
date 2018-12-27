//  mode: 'development', rather than 'production' to not minify

const HtmlWebpackPlugin = require('html-webpack-plugin');
// CC const FaviconsPlugin = require('favicons-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
// CC const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
const webpack = require('webpack');
const { Config } = require('webpack-config');
const { join } = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const BASE_URL = (process.env.BASE_URL || 'http://localhost:3000').replace(
  /\/$/,
  ''
);
const ROOT = join(__dirname, '..');

module.exports = new Config().extend({
    'dev/webpack.base.config.js': config => {
        // Fix up base config
        config.output.publicPath = '';
        let bapno = -1;
        for (var index = 0; index < config.plugins.length; index++) {
          let plugin = config.plugins[index];
          if( plugin.constructor.name=="DefinePlugin"){
              console.log("Fix DefinePlugin API");
              plugin.definitions.API = JSON.stringify(BASE_URL+'/api/');
          }
          if( plugin.constructor.name=="BundleAnalyzerPlugin"){
              bapno = index;
          }
        }
        if( bapno>=0){
            console.log("Remove BundleAnalyzerPlugin "+bapno);
            config.plugins.splice(bapno,1);
        }
        return config;
    }
  }).merge({
  //mode: 'development',
  mode: 'production',
  // CC devtool: 'source-map',
  entry: [join(ROOT, 'client/appmt.js')],
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
    /* // CC new BundleAnalyzerPlugin({
      analyzerMode: 'static',
      reportFilename: 'bundlesize.html',
      defaultSizes: 'gzip',
      openAnalyzer: false,
      generateStatsFile: false,
      statsFilename: 'stats.json',
      statsOptions: null,
      logLevel: 'info'
    }),*/
    new MiniCssExtractPlugin({
      filename: "[name].[chunkhash].css"
    }),
    // CC new FaviconsPlugin('images/modtools_logo.png'),
    new webpack.DefinePlugin({
      APP_VERSION: JSON.stringify('0.2.8, 27 December 2018.'),
      // CC SET ABOVE: API: JSON.stringify(BASE_URL+'/api/'),
      FACEBOOK_APPID: JSON.stringify('134980666550322'),
      FACEBOOK_GRAFFITI_APPID: JSON.stringify('115376591981611'),
      GOOGLE_CLIENT_ID: JSON.stringify(
        '423761283916-1rpa8120tpudgv4nf44cpmlf8slqbf4f.apps.googleusercontent.com'
      ),
      SITE_NAME: JSON.stringify('ModTools'),
      SITE_DESCRIPTION: JSON.stringify(
        "Moderating Tools for Freegle Groups"
      ),
      MODTOOLS: true
    }),
    new HtmlWebpackPlugin({
      hash: true,
      template: join(ROOT, 'client/mt-app.ejs'),
      filename: 'index.html',
      inject: true,
      minify: {
        // CC removeComments: true,
          // CC collapseWhitespace: true,
          // CC removeAttributeQuotes: true
        // more options:
        // https://github.com/kangax/html-minifier#options-quick-reference
      },
      // necessary to consistently work with multiple chunks via CommonsChunkPlugin
      chunksSortMode: 'dependency'
    }),
    new CopyWebpackPlugin([
        {from: 'http/xdk', to: 'xdk'}
    ]),

    // do scope hoisting: https://webpack.js.org/plugins/module-concatenation-plugin
    // should reduce scripting time and bundle size
    new webpack.optimize.ModuleConcatenationPlugin()
  ]
});

//console.log(module.exports.plugins[0]);
//console.log(module.exports);