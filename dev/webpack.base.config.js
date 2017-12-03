const { Config } = require('webpack-config');
const webpack = require('webpack');
const { resolve, join } = require('path');
const { ConcatSource } = require('webpack-sources');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const FaviconsPlugin = require('favicons-webpack-plugin');
const AssetsPlugin = require('assets-webpack-plugin');

const shims = require('./shims');

const ROOT = join(__dirname, '..');

const BASE_URL = (process.env.BASE_URL || 'http://localhost:3000').replace(
  /\/$/,
  ''
);
const DOMAIN = BASE_URL.replace(/^https?:\/\//, '');

exports['default'] = new Config().merge({
  output: {
    path: resolve(ROOT, 'dist'),
    filename: 'js/[name].[hash].js',
    chunkFilename: 'js/[id].[chunkhash].js',
    publicPath: '/'
  },
  resolve: {
    modules: [
      'node_modules',
      join(ROOT, 'http'),
      join(ROOT, 'http/css'),
      join(ROOT, 'http/js/lib'),
      join(ROOT, 'node_modules/bootstrap-fileinput/img') // TODO Can't be the right way.
    ],
    alias: {
      '/template': 'template',
      '/images': 'iznik-client/images',
      ...shims.aliases
    }
  },
  module: {
    rules: [
      ...shims.rules,
      {
        test: /\.html$/,
        use: 'text-loader'
      },
      {
        test: /\.(png|jpeg|jpg|gif|woff|woff2|ttf|eot|svg)$/,
        use: [{ loader: 'url-loader', options: { limit: 8192 } }]
      },
      {
        // Naughty module exposes an es6 module
        test: require.resolve('googlemaps-js-rich-marker/src/richmarker.es6'),
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['babel-preset-env']
          }
        }
      }
    ]
  },
  plugins: [
    new webpack.DefinePlugin({
      BASE_URL: JSON.stringify(BASE_URL),
      CHAT_HOST: JSON.stringify(BASE_URL),
      EVENT_HOST: JSON.stringify(BASE_URL),
      API: JSON.stringify(BASE_URL + '/api/'),
      USER_SITE: JSON.stringify(DOMAIN),
      YAHOOAPI: JSON.stringify('https://groups.yahoo.com/api/v1/'),
      YAHOOAPIv2: JSON.stringify('https://groups.yahoo.com/api/v2/')
    }),

    new webpack.ProvidePlugin(shims.provides),

    // https://github.com/moment/moment/issues/2979#issuecomment-287675568
    new webpack.IgnorePlugin(/\.\/locale$/),

    new CopyWebpackPlugin([
      { from: 'http/template', to: 'template' },
      { from: 'http/images', to: 'images' }
    ]),
    new AssetsPlugin()
  ],
  node: {
    fs: 'empty'
  }
});
