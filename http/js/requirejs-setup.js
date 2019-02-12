// We load everything using require.  We need some shims for scripts which aren't AMD-compatible.
var metas = document.getElementsByTagName('meta');
/* // CC var bust = (new Date()).getTime()

for (var i=0; i<metas.length; i++) {
    if (metas[i].getAttribute("name") == "iznikcache") {
        bust = metas[i].getAttribute("content")
    }
}*/

var iznikroot = location.pathname.substring(0, location.pathname.lastIndexOf('/') + 1)	// CC
iznikroot = decodeURI(iznikroot.replace(/%25/g, '%2525'))	// CC
console.log("iznikroot " + iznikroot)

requirejs.config({
    baseUrl: iznikroot + "js/lib",	// CC

    // The server has returned info telling us when code was changed, which we can use to bust our cache.
    // CC urlArgs: "bust=" + bust,

    shim : {
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
            exports: 'moment'
        },
        "jquery.dd": [ "jquery" ],
        "jquery.geocomplete": [ "jquery" ],
        "jquery-show-first": [ "iznik/utility", "jquery" ],
        'jquery.validate.additional-methods': [ 'jquery.validate.min' ],
        "fileinput": [ "jquery", "canvas-to-blob" ],
        "jquery.ui.widget": [ "jquery" ],
        "jquery.ui.touch-punch": [ "jquery" ],
        "iznik/accordionpersist": [ "jquery" ],
        "iznik/selectpersist": [ "jquery" ],
        "jquery-resizable": [ "jquery" ]
    },

    paths: {
			  // CC.. remove /js/lib/ from on-absolute paths
        "bootstrap" :  "bootstrap.min",
        "hammer": "hammer.min",   // CC
        "ga": "https://www.google-analytics.com/analytics",	// CC
        "waypoints": "jquery.waypoints",
        "jquery.ui.widget": "jquery-file-upload/vendor/jquery.ui.widget",
        "jquery-ui": "jquery-ui/jquery-ui.min",
        "underscore": "underscore",
        "jquery-show-first": "jquery-show-first",
        "tinymce": "https://cdn.tinymce.com/4/tinymce.min",
        "gmaps": "https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyCdTSJKGWJUOx2pq1Y0f5in5g4kKAO5dgg&libraries=geometry,places,drawing,visualization",
        "maplabel": "maplabel-compiled",

        "iznik": iznikroot + "js/iznik"	// CC
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