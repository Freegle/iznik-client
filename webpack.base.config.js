var wc = require('webpack-config');
var Config = wc.Config;

const webpack = require('webpack');
const {resolve, join} = require('path');
const shell = require('shelljs');
const {ConcatSource} = require('webpack-sources');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const FaviconsPlugin = require('favicons-webpack-plugin');

clean();

const requireJs = {
    shims: {
        /*
        "bootstrap" : [ 'jquery' ],
        "bootstrap-select": [ "bootstrap" ],
        "bootstrap-switch": [ "bootstrap" ],
        "wicket": [ "jquery" ],
        "wicket-gmap3": [ "wicket" ],
        "ga": {
            exports: "ga"
        },
        "gmaps": {
            exports: "google"
        },
        "richMarker": [ "gmaps" ],
        "waypoints": {
            deps: [ "jquery" ],
            exports: 'Waypoint'
        },
        "maplabel": {
            deps: [ "jquery", "gmaps"]
        },
        "combodate": {
            deps: [ 'moment' ]
        },
        'moment': {
            // get "Uncaught ReferenceError: moement is not defined" if I include the line below
            //exports: 'moment'
        },
        "jquery.dd": [ "jquery" ],
        "jquery.dotdotdot": [ "jquery" ],
        "jquery.geocomplete": [ "jquery" ],
        "jquery-show-first": [ "iznik/utility", "jquery" ],
        'jquery.validate.additional-methods': [ 'jquery.validate.min' ],
        "fileinput": [ "jquery", "canvas-to-blob" ],
        "jquery.ui.widget": [ "jquery" ],
        "jquery.ui.touch-punch": [ "jquery" ],
        "iznik/accordionpersist": [ "jquery" ],
        "iznik/selectpersist": [ "jquery" ],
        "jquery-resizable": [ "jquery" ]
        */

        "bootstrap": ['jquery'],
        "bootstrap-select": ["bootstrap"],
        "bootstrap-switch": ["bootstrap"],
        "wicket": ["jquery"],
        "wicket-gmap3": ["wicket"],
        "gmaps": {
            exports: "google"
        },
        "richMarker": ["gmaps"],
        "waypoints": {
            deps: ["jquery"],
            exports: 'Waypoint'
        },
        "maplabel": {
            deps: ["jquery", "gmaps"]
        },
        "combodate": {
            deps: ['moment']
        },
        "backform": {
            deps: ['backbone']
        },
        'moment': {
            // exports: 'moment'
        },
        'react-dom': {
            deps: [ 'react' ]
        },
        "jquery.dd": ["jquery"],
        "jquery.geocomplete": ["jquery"],
        "jquery-show-first": ["iznik/utility", "jquery"],
        'jquery.validate.additional-methods': ['jquery.validate.min'],
        "fileinput": ["jquery", "canvas-to-blob"],
        "jquery.ui.widget": ["jquery"],
        "jquery.ui.touch-punch": ["jquery"],
        "iznik/accordionpersist": ["jquery"],
        "iznik/selectpersist": ["jquery"],
        "jquery-resizable": ["jquery"]
    },
    paths: {
        "bootstrap": "/js/lib/bootstrap.min",
        "waypoints": "/js/lib/jquery.waypoints",
        "jquery.ui.widget": "/js/lib/jquery-file-upload/vendor/jquery.ui.widget",
        "jquery-ui": "/js/lib/jquery-ui",
        "underscore": "/js/lib/underscore",
        "react": "/js/lib/react.production.min",
        "react-dom": "/js/lib/react-dom.production.min",
        "jquery-show-first": "/js/lib/jquery-show-first",
        "tinymce": "https://cdn.tinymce.com/4/tinymce.min",
        "gmaps": "https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyCdTSJKGWJUOx2pq1Y0f5in5g4kKAO5dgg&libraries=geometry,places,drawing,visualization",
        "maplabel": "/js/lib/maplabel-compiled",
        "iznik": "/js/iznik"
    },

}

const externalScripts = [];

for (const [alias, script] of Object.entries(requireJs.paths)) {
    // console.log('testing', alias, script);
    if (/^http/.test(script) || /^\/\//.test(script)) {

        // For now, we can't do anything with these external scripts so just stub them out...
        // requireJs.paths[alias] = "/js/placeholder?script=" + script;
        requireJs.paths[alias] = "/js/placeholder?script=" + script;

        externalScripts.push({alias, script});
    }
}

// We need to shim some global or window functions - see also base.js.
const iznikUtilityShims = {
    globalFunctions: [
        'haversineDistance',
        'resolvedPromise',
        'getURLParam',
        'strip_tags',
        'ABTestShown',
        'ABTestAction',
        'ABTestGetVariant',
        'nullFn',
        'twem',
        'setTitleCounts',
        'ellipsical',
        'formatDuration',
        'getBoundsZoomLevel'
    ],
    windowFunctions: [
        'isXS',
        'innerWidth',
        'isSM',
        'innerWidth',
        'isShort',
        'innerHeight',
        'isVeryShort',
        'innerHeight',
        'canonSubj',
        'setURLParam',
        'removeURLParam',
        'getDistanceFromLatLonInKm',
        'deg2rad',
        'decodeEntities',
        'encodeHTMLEntities',
        'orderedMessages',
        'csvWriter',
        'presdef',
        'chunkArray',
        'base64url',
        'isValidEmailAddress',
        'wbr'
    ]
}

exports['default'] = new Config().merge({
    entry: './client/app.js',
    output: {
        path: resolve(__dirname, 'dist'),
        filename: 'js/[name].[hash].js',
        chunkFilename: 'js/[id].[chunkhash].js'
    },
    resolve: {
        modules: [
            'node_modules',
            join(__dirname, 'http'),
            join(__dirname, 'http/css'),
            join(__dirname, 'http/js/lib'),
            join(__dirname, 'node_modules/bootstrap-fileinput/img') // TODO Can't be the right way.
        ],
        alias: {
            // is referenced, but hasn't been written yet
            'iznik/views/pages/modtools/chat_report': 'empty-module',

            // maybe the first part of the solution for template loading...
            // next part would be getting a file-loader (or something) to apply to
            // those templates
            '/template': 'template',
            '/images': 'iznik-client/images',

            ...convertPaths(requireJs.paths),
        },
    },
    resolveLoader: {
        alias: {
            text: 'text-loader'
        }
    },
    module: {
        rules: [
            ...convertShims(requireJs.shims),
            {
                test: /placeholder/,
                use: ['./scriptjs-loader']
            },
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
            },
            {
                test: /\.(png|jpeg|jpg|gif|woff|woff2|ttf|eot|svg)$/,
                use: [
                    {loader: 'url-loader', options: {limit: 8192}}
                ]
            }
        ]
    },
    plugins: [
        new webpack.ProvidePlugin({
            // waypoints wants $ on window.jQuery
            'window.jQuery': 'jquery',
            // bootstrap wants $ global
            'jQuery': 'jquery',
            // Our template functions are used all over the place.
            'window.template': ['iznik/templateloader', 'template'],
            'templateFetch': ['iznik/templateloader', 'templateFetch'],
            'twemoji': 'twemoji'
        }),

        new webpack.DefinePlugin({
            YAHOOAPI: '"https://groups.yahoo.com/api/v1/"',
            YAHOOAPIv2: '"https://groups.yahoo.com/api/v2/"',
            $dirname: '__dirname'
        }),

        new webpack.ProvidePlugin(iznikUtilityProvideGlobals([...iznikUtilityShims.globalFunctions, ...iznikUtilityShims.windowFunctions])),
        new webpack.ProvidePlugin(iznikUtilityProvideWindows(iznikUtilityShims.windowFunctions)),

        // https://github.com/moment/moment/issues/2979#issuecomment-287675568
        new webpack.IgnorePlugin(/\.\/locale$/),

        new CopyWebpackPlugin([
            {from: 'http/template', to: 'template'},
            {from: 'http/images', to: 'images'}
        ])
    ],
    node: {
        fs: "empty"
    }
});

function iznikUtilityProvideGlobals(names) {
    const config = {};
    for (let name of names) {
        config[name] = ['iznik/base', name];
    }
    return config;
}

function iznikUtilityProvideWindows(names) {
    const config = {};
    for (let name of names) {
        config['window.' + name] = ['iznik/base', name];
    }
    return config;
}

function convertShims(config) {
    return Object.keys(config).map(lib => {
        const conf = config[lib];
        return convertShim(lib, config[lib]);
    });
}

function convertPaths(paths) {
    const result = {}
    for (let key of Object.keys(paths)) {
        result[key] = paths[key].replace(/^\//, '')
    }
    return result
}

function convertShim(lib, config) {
    const loaders = [];
    let deps;
    if (Array.isArray(config)) {
        deps = config
    } else {
        deps = config.deps;
        if (config.exports) {
            loaders.push('exports-loader?' + config.exports)
        }
    }
    if (deps && deps.length > 0) {
        loaders.push('imports-loader?' + deps.map(dep => {
            // any dep with a / in, e.g. iznik/foo we need to set the import name
            // it *should* just be for deps where you don't want the exported value, just for it to
            // be loaded
            if (/[/-]/.test(dep)) {
                return dep.replace(/[/-]/g, '_') + '=' + dep;
            } else {
                return dep
            }
        }).join(','));
    }
    return {
        test(val) {
            if (requireJs.paths[lib]) {
                return val.endsWith(requireJs.paths[lib] + '.js')
            } else {
                return new RegExp('/' + lib + '.js').test(val);
            }
        },
        use: loaders,
    }
}

function clean() {
    shell.rm('-rf', resolve(__dirname, '../dist/*'))
    shell.rm('-rf', resolve(__dirname, '../dist/.*'))
    console.log('Cleaned build artifacts.\n')
}
