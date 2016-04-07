var API = '/api/';
var YAHOOAPI = 'https://groups.yahoo.com/api/v1/';
var YAHOOAPIv2 = 'https://groups.yahoo.com/api/v2/';

require([
    'jquery',
    'underscore',
    'backbone',
    'iznik/router'
], function($, _, Backbone) {
    Backbone.emulateJSON = true;
    
    // We have a busy indicator.
    $(document).ajaxStop(function () {
        $('#spinner').hide();
    });

    $(document).ajaxStart(function () {
        $('#spinner').show();
    });
});
