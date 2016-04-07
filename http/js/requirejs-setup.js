// We load everything using require.  We need some shims for scripts which aren't AMD-compatible.
requirejs.config({
    baseUrl: "/js/lib",

    urlArgs: "bust=" +  (new Date()).getTime(),

    shim : {
        "bootstrap" : [ 'jquery' ],
        "bootstrap-select": [ "bootstrap" ],
        "bootstrap-switch": [ "bootstrap" ],
        "bootstrap-tagsinput": [ "bootstrap" ],
        "wicket": [ "jquery" ],
        "wicket-gmap3": [ "wicket" ],
        "ga": {
            exports: "ga"
        },
        "waypoints": {
            deps: [ "jquery" ],
            exports: 'Waypoint'
        },
        "jquery.dd": [ "jquery" ],
        "jquery.dotdotdot": [ "jquery" ],
        "jquery.geocomplete": [ "jquery" ],
        "jquery-show-first": [ "jquery" ],
        "fileupload": [ "jquery" ],
        "jquery.ui.widget": [ "jquery" ],
        "jquery.ui.touch-punch": [ "jquery" ],
        "iznik/accordionpersist": [ "jquery" ],
        "iznik/selectpersist": [ "jquery" ],
        "jquery-resizable": [ "jquery" ],
    },

    paths: {
        "bootstrap" :  "//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min",
        "ga": "//www.google-analytics.com/analytics",
        "waypoints": "/js/lib/jquery.waypoints",
        "fileupload": "/js/lib/jquery-file-upload/jquery.fileupload",
        "jquery.ui.widget": "/js/lib/jquery-file-upload/vendor/jquery.ui.widget",
        "jquery-ui": "/js/lib/jquery-ui/jquery-ui.min",
        "underscore": "/js/lib/underscore",

        "iznik": "/js/iznik"
    },

    tpl: {
        // Configuration for requirejs-tpl
        // Use Mustache style syntax for variable interpolation
        templateSettings: {
            evaluate : /\{\[([\s\S]+?)\]\}/g,
            interpolate : /\{\{([\s\S]+?)\}\}/g
        }
    },

    waitSeconds: 0    
});