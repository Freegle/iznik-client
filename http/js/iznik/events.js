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
    var flushTimerRunning;

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
        flushTimerRunning = false;

        if (eventQueue.length > 0) {
            // If we fail, we'll lose events.  Oh well.
            var currQueue = eventQueue;
            eventQueue = [];

            $.ajax({
                url: API + 'event',
                type: 'POST',
                data: {
                    'events': currQueue
                }, success: function(ret) {
                    if (ret.ret === 0) {
                        if (!flushTimerRunning) {
                            flushTimerRunning = true;
                            window.setTimeout(flushEventQueue, 5000);
                        }
                    }
                }
            });
        } else if (!flushTimerRunning) {
            flushTimerRunning = true;
            window.setTimeout(flushEventQueue, 5000);
        }
    }

    // Scan the DOM on a timer and detect changes.
    var monitorDOM = (function () {
        return ({
            lastDOM: null,
            lastDOMtime: 0,

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
                        var now = (new Date()).getTime();

                        if (strdiff.length > dom.length || now - this.lastDOMtime > 30000) {
                            // Not worth tracking the diff, as the diff is bigger than the whole thing, or it's been
                            // a while.  The second is to help us recover from weirdnesses by providing a periodic
                            // reset, which also helps when playing forwards.
                            type = 'f';
                            strdiff = dom;
                            this.lastDOMtime = now;
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

                    // Rewriting the DOM may lose input values which were set post-page-load (most of 'em).
                    // However this doesn't seem to work very well, and it's also quite expensive.
                    // $('input').each(function() {
                    //     var val = $(this).val();
                    //     if (val) {
                    //         trackEvent(self.getPath($(this)), 'input', e.pageX, e.pageY, val);
                    //     }
                    // });
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

            getPath: function (node) {
                var path = '';
                if (node[0].id) return "#" + node[0].id;
                while (node.length) {
                    var realNode = node[0], name = realNode.localName;
                    if (!name) break;
                    name = name.toLowerCase();

                    var parent = node.parent();

                    var sameTagSiblings = parent.children(name);
                    if (sameTagSiblings.length > 1) {
                        allSiblings = parent.children();
                        var index = allSiblings.index(realNode) + 1;
                        if (index > 1) {
                            name += ':nth-child(' + index + ')';
                        }
                    }

                    path = name + (path ? '>' + path : '');
                    node = parent;
                }

                return path;
            },

            start: function () {
                var self = this;

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

                $(window).on('keydown', _.bind(function(e) {
                    if ($(e.target).is('input')) {
                        var val = $(e.target).val();
                        if (val) {
                            trackEvent(self.getPath($(e.target)), 'input', e.pageX, e.pageY, val);
                        }
                    }
                }, self));

                flushTimerRunning = true;
                window.setTimeout(flushEventQueue, 5000);

                this.startTimer();
            }
        });
    })();

    return(monitorDOM);
});
