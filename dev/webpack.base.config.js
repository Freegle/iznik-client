const { Config } = require('webpack-config');
const webpack = require('webpack');
const { resolve, join } = require('path');
const { ConcatSource } = require('webpack-sources');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const FaviconsPlugin = require('favicons-webpack-plugin');
const AssetsPlugin = require('assets-webpack-plugin');
const ProgressBarPlugin = require('progress-bar-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const shims = require('./shims');
const ROOT = join(__dirname, '..');
const GitRevisionPlugin = require('git-revision-webpack-plugin');

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
      join(ROOT, 'node_modules/bootstrap-fileinput/img'), // TODO Can't be the right way.
      join(ROOT, 'node_modules/raven-js/dist/plugins') // TODO Can't be the right way.
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
        test: /\.js$/,
        exclude: /(node_modules|js(\/|\\)lib)/,
        use: 'babel-loader'
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
          CHAT_HOST: JSON.stringify('https://users.ilovefreegle.org'), // Long polls interact badly with per-host connection limits so send to here instead.
          EVENT_HOST: JSON.stringify(BASE_URL),
          API: JSON.stringify('/api/'),
          USER_SITE: JSON.stringify(DOMAIN),
          YAHOOAPI: JSON.stringify('https://groups.yahoo.com/api/v1/'),
          YAHOOAPIv2: JSON.stringify('https://groups.yahoo.com/api/v2/'),
          RAVEN_ID: JSON.stringify('https://421dadb7cd284c8aaeac285c65649728@sentry.io/261108'),
          GIT_COMMITHASH: JSON.stringify((new GitRevisionPlugin()).commithash())
      }),

      new webpack.ProvidePlugin(shims.provides),

      // https://github.com/moment/moment/issues/2979#issuecomment-287675568
      new webpack.IgnorePlugin(/\.\/locale$/),

      new CopyWebpackPlugin([
          {from: 'http/images', to: 'images'}
      ]),

      new AssetsPlugin(),

      new GitRevisionPlugin(),

      new ProgressBarPlugin({}),

      new BundleAnalyzerPlugin({
          analyzerMode: 'static',
          generateStatsFile: true,
          openAnalyzer: false
      }),

      new webpack.optimize.CommonsChunkPlugin({
          children: true,
          minChunks: 6
      })
  ],
  node: {
    fs: 'empty'
  }
});
