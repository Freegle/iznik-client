// We load everything using require.  We need some shims for scripts which aren't AMD-compatible.
requirejs.config({
    baseUrl: "/js/lib",
    shim : {
        "bootstrap" : [ 'jquery' ],
        "bootstrap-select": [ "bootstrap" ],
        "bootstrap-switch": [ "bootstrap" ],
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
        "jquery.ui.widget": [ "jquery", "fileupload" ],
        "iznik/accordionpersist": [ "jquery" ],
        "iznik/selectpersist": [ "jquery" ]
    },
    paths: {
        "bootstrap" :  "//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min",
        "ga": "//www.google-analytics.com/analytics",
        "waypoints": "/js/lib/jquery.waypoints",
        "fileupload": "/js/lib/jquery-file-upload/jquery.fileupload",
        "jquery.ui.widget": "/js/lib/jquery-file-upload/vendor/jquery.ui.widget",
        "iznik": "/js/iznik"
    },
    waitSeconds: 0    
});