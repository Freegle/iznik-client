define([
    'jquery',
    'underscore',
    'iznik/diff',
    'css-selector-generator'
], function($, _, JsDiff, CssSelectorGenerator) {
    // We track the mouse, keyboard and DOM activity on the client, and periodically upload it to the server.  This allows
    // us to replay sessions and see what happened, which is invaluable for diagnosing problems and helping users with
    // issues.
    var lastEventTimestamp = null;
    var eventQueue = [];
    var flushTimerRunning;

    function trackEvent(target, event, posX, posY, data, timestamp) {
        if (!timestamp) {
            timestamp = (new Date()).getTime();
        }

        var data = {
            timestamp: timestamp,
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
                var self = this;

                // Use innerHTML as we don't put classes on body, and it allows us to restore the content within
                // a div when replaying.
                var dom = $('body')[0].innerHTML;
                // console.log("DOM initially", dom.length);

                // Get the DOM with all the values.
                //
                // Create a copy of the body outside the DOM
                // Fill in all the values
                // Get it back.
                //
                // We don't want to do this on the actual body as this might trigger events.
                var clone = $('body').clone();
                $('input, select, textarea').each(function() {
                    var $this = $(this);
                    var val = $this.val();

                    if (val) {
                        var path = self.getPath($this);
                        path = path.replace('html>body>', '');
                        var copy = clone.find(path);

                        if ($this.is("[type='radio']") || $this.is("[type='checkbox']")) {
                            if ($this.prop("checked")) {
                                copy.attr("checked", "checked");
                            } else {
                                copy.removeAttr("checked");
                            }
                        } else {
                            if ($this.is("select")) {
                                copy.find(":selected").attr("selected", "selected");
                            } else {
                                copy.attr("value", $this.val());
                            }
                        }

                        // console.log("Set copy input", path, copy.length, val);
                        $(copy).val(val);
                    }
                });
                dom = clone.html();
                // console.log("Dom now", dom.length);

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
                    //
                    // We log these with the same timestamp so that they are replayed seamlessly.
                    var timestamp = (new Date()).getTime();
                    trackEvent('window', 'DOM-' + type, null, null, strdiff, timestamp);
                    this.lastDOM = dom;

                    // Rewriting the DOM may lose input values which were set post-page-load (most of 'em).
                    // $('input, select, textarea').each(function() {
                    //     var val = $(this).val();
                    //     if (val) {
                    //         trackEvent(self.getPath($(this)), 'input', null, null, val, timestamp);
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
                if (!this.selgen) {
                    this.selgen = new CssSelectorGenerator;
                }

                return(this.selgen.getSelector(node.get(0)));
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
