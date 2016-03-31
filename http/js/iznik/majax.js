define([
    'jquery',
    'underscore'
], function($, _) {
    // We only need to use this for requests which use non-simple verbs,
    // e.g. PUT, DELETE.  Otherwise it is more efficient to use the
    // standard AJAX call and rely on the plugin making it work by munging
    // the CORS headers - those calls can happen in parallel and don't require
    // the serialisation (in both senses) of the request.
    //
    // It works like this:
    // - We create two hidden divs, one for the request and one for the response
    // - We store off the parameters for a call in the request dif
    // - We issue the call as normal
    // - If the plugin is not present, the call may work or not depending
    //   on CORS.
    // - If the plugin is present, the call may succeed (for non-simple verbs)
    //   or be made by the plugin and trigger back to us by cancellation.
    //   We pick up the response from such cancellations and invoke the callback.

    // SerialQueue for executing operations one at a time.
    //
    // Assumes the object passed contains a deferred property and a start method.

    function SerialQueue() {
        this.entries = [];
        this.count = 0;
    }

    SerialQueue.prototype.add = function (args) {
        var self = this;

        return (function () {

            // We assume the argument contains a Deferred.
            //
            // Set up the function to invoke when this entry completes;
            args.deferred.done(function (obj) {
                // Callback on this object

                // Remove our entry from the front of the queue
                self.entries.shift();

                // Start the next entry if any
                var next = self.entries.shift();

                if (typeof next != 'undefined') {
                    next.start();
                }
            });

            // Add this entry
            var count = self.entries.push(args);

            if (count == 1) {
                // First entry - start it
                args.start();
            }
        });
    };

    var majaxQueue = new SerialQueue();

    window.majax = function(args) {
        this.args = args;
        this.deferred = new jQuery.Deferred();

        majaxQueue.add(this)();
    }

    majax.prototype.start = function () {
        var self = this;

        // Copy the args excluding callbacks
        var copy = {};

        for (var arg in this.args) {
            if ((arg != 'success') && (arg != 'error')) {
                copy[arg] = this.args[arg];
            }
        }

        var req = JSON.stringify(copy);

        // Save off the arguments for the plugin
        $('#modtoolsreq').text(req);
        $('#modtoolsrsp').empty();

        // Add our own success/error callbacks
        copy.success = function (ret) {
            self.deferred.resolve(self);

            // Pass a success straight through
            self.args.success(ret);
        };

        copy.error = function (request, status, error) {
            // This error may have been triggered by the plugin, and we may have
            // a success to pick up.
            var rsp = $('#modtoolsrsp').text();

            self.deferred.resolve(self);

            if (rsp.length > 0) {
                // We did succeed
                //
                // Quite what we get back, string or JSON, depends a bit on the browser.
                try {
                    if (typeof rsp == 'string') {
                        rsp = JSON.parse(rsp);
                    }
                    if (typeof rsp == 'string') {
                        rsp = JSON.parse(rsp);
                    }

                    self.args.success(rsp);
                } catch (e) {
                    console.log("Parse error", e);
                    self.args.error(request, rsp, null);
                }
            } else {
                // We failed.
                if (typeof self.args.error == 'function') {
                    self.args.error(request, status, error);
                } else {
                    console.log("majax failure", request, status, error);
                }
            }
        };

        // Set a timeout, otherwise we can get stuck with our per-host connection limit reached.
        copy.timeout = 30000;

        // Issue the request to kick the plugin
        $.ajax(copy);
    };
});