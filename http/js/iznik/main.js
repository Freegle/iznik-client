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

    // We want to retry AJAX requests automatically, because we might have a flaky network.  This also covers us for
    // Backbone fetches.
    var _ajax = $.ajax;

    function sliceArgs() {
        return(Array.prototype.slice.call(arguments, 0));
    }

    function delay(errors) {
        // Exponential backoff upto a limit.
        return(Math.min(Math.pow(2, errors) * 1000, 30000));
    }

    function retryIt(jqXHR) {
        var self = this;
        this.errors = this.errors === undefined ? 0 : this.errors + 1;
        var thedelay = delay(this.errors);
        console.log("retryIt", thedelay, this, arguments);
        setTimeout(function () {
            $.ajax(self);
        }, thedelay);
    }

    function extendIt(args, options) {
        _.extend(args[0], options && typeof options === 'object' ? options : {}, {
            error:   function () { retryIt.apply(this, arguments); }
        });
    }
    
    $.ajax = function (options) {
        var url = options.url;

        if (url && url.indexOf('groups.yahoo.com') == -1) {
            // We wrap the AJAX call in our own, with our own error handler.
            var args;
            if (typeof options === 'string') {
                arguments[1].url = options;
                args = sliceArgs(arguments[1]);
            } else {
                args = sliceArgs(arguments);
            }

            extendIt(args, options);

            return _ajax.apply($, args);
        } else {
            // Yahoo can validly return errors as part of its API, and we handle retrying via the plugin work, so
            // don't mess with it.
            return(_ajax.apply($, arguments));
        }
    };
});
