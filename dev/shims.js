const requireJs = {
    shims: {
        bootstrap: ['jquery'],
        wicket: ['jquery'],
        'wicket-gmap3': ['wicket'],
        waypoints: {
            deps: ['jquery'],
            exports: 'Waypoint'
        },
        maplabel: {
            deps: ['jquery']
        },
        combodate: {
            deps: ['moment']
        },
        backform: {
            deps: ['backbone']
        },
        moment: {
            // exports: 'moment'
        },
        'react-dom': {
            deps: ['react']
        },
        'jquery.dd': ['jquery'],
        geocomplete: ['jquery'],
        'jquery-show-first': ['jquery'],
        'jquery.validate.additional-methods': ['jquery.validate.min'],
        fileinput: ['jquery', 'canvas-to-blob'],
        'jquery.ui.widget': ['jquery'],
        'jquery.ui.touch-punch': ['jquery'],
        'iznik/accordionpersist': ['jquery'],
        'iznik/selectpersist': ['jquery'],
        'jquery-resizable': ['jquery']
    },
    paths: {
        bootstrap: '/js/lib/bootstrap.min',
        waypoints: '/js/lib/jquery.waypoints',
        'jquery.ui.widget': '/js/lib/jquery-file-upload/vendor/jquery.ui.widget',
        'jquery-ui': '/js/lib/jquery-ui',
        underscore: '/js/lib/underscore',
        react: '/js/lib/react.production.min',
        'react-dom': '/js/lib/react-dom.production.min',
        'jquery-show-first': '/js/lib/jquery-show-first',
        maplabel: '/js/lib/maplabel-compiled',
        iznik: '/js/iznik'
    }
}

exports.aliases = {
    // is referenced, but hasn't been written yet
    'iznik/views/pages/modtools/chat_report': 'empty-module',

    ...convertPathsToAliases(requireJs.paths)
}

exports.rules = [
    ...convertShimsToRules(requireJs.shims)
]

exports.provides = {
    // waypoints wants $ on window.jQuery
    'window.jQuery': 'jquery',
    // bootstrap wants $ global
    jQuery: 'jquery',
    twemoji: 'twemoji'
}

function convertPathsToAliases (paths) {
    const result = {}
    for (let key of Object.keys(paths)) {
        result[key] = paths[key].replace(/^\//, '')
    }
    return result
}

function convertShimsToRules (config) {
    return Object.keys(config).map(lib => {
        const conf = config[lib]
        return convertShimToRule(lib, config[lib])
    })
}

function convertShimToRule (lib, config) {
    const loaders = []
    let deps
    if (Array.isArray(config)) {
        deps = config
    } else {
        deps = config.deps
        if (config.exports) {
            loaders.push('exports-loader?' + config.exports)
        }
    }
    if (deps && deps.length > 0) {
        loaders.push(
            'imports-loader?' +
            deps
                .map(dep => {
                    // any dep with a / in, e.g. iznik/foo we need to set the import name
                    // it *should* just be for deps where you don't want the exported value, just for it to
                    // be loaded
                    if (/[/-]/.test(dep)) {
                        return dep.replace(/[/-]/g, '_') + '=' + dep
                    } else {
                        return dep
                    }
                })
                .join(',')
        )
    }
    return {
        test (val) {
            if (requireJs.paths[lib]) {
                return val.endsWith(requireJs.paths[lib] + '.js')
            } else {
                return new RegExp('/' + lib + '.js').test(val)
            }
        },
        use: loaders
    }
}
