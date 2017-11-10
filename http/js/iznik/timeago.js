define([
    'jquery',
    'underscore',
    'moment'
], function(jQuery, _, moment) {
    // Plugin to automatically update fuzzy timings.  We used to use http://timeago.yarp.com/ but this has
    // some scalability issues.

    var queue = [];
    var queueRunning = false;
    var queueInterval = 30000;
    var pruneInterval = 30000;

    (function(factory){
        if(typeof define === 'function' && define.amd){
            // AMD. Register as an anonymous module.
            define(['jquery'], factory);
        }else{
            // Browser globals
            factory(jQuery);
        }
    }(function($) {

        function nextTime(m, now) {
            // Work out when we next ought to check this time.
            var duration = moment.duration(now.diff(m));
            var seconds = Math.abs(duration.asSeconds());
            var minutes = seconds / 60;
            var hours = minutes / 60;
            var days = hours / 24;
            var years = days / 365;

            var ret = null;

            // Anything over a day can stay - it's very unlikely to change while we look at it, so no point
            // using the CPU.
            if (days < 1) {
                // We want to next check halfway through the unit that will change next - minutes or
                // hours.  Don't bother updating for seconds - too frequent.
                if (hours > 1) {
                    ret = 60 * 60 * 1000 / 2;
                } else {
                    ret = 60 * 1000 / 2;
                }
            }

            return(ret);
        }

        function display(m, el) {
            var text = m.fromNow();

            // Don't allow future times.
            text = text.indexOf('in ') === 0 ? 'just now': text;

            if (el.html() != text) {
                el.html(text);
            }
        }

        function checkQueue() {
            var now = new moment();

            _.each(queue, function(ent) {
                if (ent[0].isBefore(now)) {
                    display(ent[1], ent[2]);

                    var delay = nextTime(ent[1], now);
                    var checkAt = now.add(delay, 'milliseconds');
                    ent[0] = checkAt;
                }
            });

            _.delay(checkQueue, queueInterval);
        }

        function pruneQueue() {
            // We prune the queue occasionally to remove elements which are no longer in the DOM.
            // TODO There's a timing window where this could kick in during a view render before the view has
            // been added to the DOM, which would result in never updating that entry.
            var newQueue = [];

            _.each(queue, function(ent) {
                if (ent[2].closest('body').length > 0) {
                    newQueue.push(ent);
                }
            });

            queue = newQueue;
            _.delay(pruneQueue, pruneInterval);
        }

        $.fn.timeago = function (m) {
            if (m) {
                // Actually setting up the DOM the first time is done by the caller, who can do it more efficiently; here
                // we are concerned only with making sure that it gets updated later.
                var self = $(this);

                // We might already know about this one.
                var ent = _.find(queue, function(e) {
                    return(e.el == self);
                });

                if (!ent) {
                    var now = new moment();
                    var delay = nextTime(m, now);
                    var checkAt = now.add(delay, 'milliseconds');

                    if (delay !== null) {
                        var ent = [checkAt, m, self];
                        queue.push(ent);

                        if (!queueRunning) {
                            queueRunning = true;
                            _.delay(checkQueue, queueInterval);
                            _.delay(pruneQueue, pruneInterval);
                        }
                    }
                }
            }
        };
    }));
});