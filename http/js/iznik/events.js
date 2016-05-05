define([
    'jquery',
    'underscore',
    'iznik/diff'
], function($, _, JsDiff) {
    // We track the mouse, keyboard and DOM activity on the client, and periodically upload it to the server.  This allows
    // us to replay sessions and see what happened, which is invaluable for diagnosing problems and helping users with
    // issues.
    var lastEventTimestamp = null;
    var eventQueue = [];

    function trackEvent(target, event, posX, posY, data) {
        var data = {
            timestamp: (new Date()).getTime(),
            route: location.pathname + location.hash,
            target: target,
            event: event,
            viewx: $(window).outerWidth(),
            viewy: $(window).outerHeight(),
            posX: posX,
            posY: posY,
            data: data
        };

        eventQueue.push(data);
    }

    function flushEventQueue() {
        if (eventQueue.length > 0) {
            $.ajax({
                url: API + 'event',
                type: 'POST',
                data: {
                    'events': eventQueue
                }
            });

            eventQueue = [];
        }

        window.setTimeout(flushEventQueue, 5000);
    }

    // Scan the DOM on a timer and detect changes.
    var monitorDOM = (function () {
        return ({
            lastDOM: null,

            checkDOM: function () {
                // Use innerHTML as we don't put classes on body, and it allows us to restore the content within
                // a div when replaying.
                var dom = $('body')[0].innerHTML;
                var type;

                if (!this.lastDOM) {
                    // We've not captured the DOM yet
                    type = 'f';
                    strdiff = dom;
                } else {
                    if (dom.length == this.lastDOM.length) {
                        // Very probably, this is exactly the same.  Save some CPU.
                        return;
                    } else if (dom.length / this.lastDOM.length < 0.75 || dom.length / this.lastDOM.length > 1.25) {
                        // The two must be pretty different.  Just track the whole thing.
                        //console.log("Don't even bother with a diff", dom.length, this.lastDOM.length);
                        type = 'f';
                        strdiff = dom;
                    } else {
                        var strdiff = JsDiff.createTwoFilesPatch('o', 'n', this.lastDOM, dom);
                        if (strdiff.length > dom.length) {
                            // Not worth tracking the diff, as the diff is bigger than the whole thing.
                            type = 'f';
                            strdiff = dom;
                        } else {
                            type = 'd';
                        }
                        //console.log("DOM diff", strdiff, strdiff.length);
                    }
                }

                if (strdiff.length > 80) {
                    // 80 is the "no differences" text.
                    trackEvent('window', 'DOM-' + type, null, null, strdiff);
                    this.lastDOM = dom;
                }
                //console.timeEnd('checkDOM');
            },

            // We have a background timer to spot DOM changes which are not driven by events such as clicks.
            startTimer: function () {
                window.setTimeout(_.bind(this.checkTimer, this), 200);
            },

            checkTimer: function () {
                this.checkDOM();
                this.startTimer();
            },

            start: function () {
                // Capture scrolls on the window
                $(window).scroll(function (e) {
                    var scroll = $(window).scrollTop();
                    trackEvent('window', 'scroll', null, null, scroll);
                });

                // Track mouse movements
                (function () {
                    var lastX = null;
                    var lastY = null;
                    var granularity = 10;

                    $(document).mousemove(function (e) {
                        if (Math.abs(lastX - e.pageX) > granularity || Math.abs(lastY - e.pageY) > granularity) {
                            trackEvent('window', 'mousemove', e.pageX, e.pageY, null);

                            lastX = e.pageX;
                            lastY = e.pageY;
                        }
                    });
                })();

                // Track mouse clicks
                $(window).click(function (e) {
                    trackEvent('window', 'click', e.pageX, e.pageY, null);
                });

                // If we reload, we'd like to flush out the events first.
                $(window).on('mousedown', function (e) {
                    flushEventQueue();
                });

                window.setTimeout(flushEventQueue, 5000);

                this.startTimer();
            }
        });
    })();

    return(monitorDOM);
});
