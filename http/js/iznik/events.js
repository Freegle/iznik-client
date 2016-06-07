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

            getWithValues: function() {
                // We're saving off the full DOM.  What we get from innerHTML doesn't have any values in it which
                // were changed post initial insertion.  So we need to get the DOM with all the values.
                //
                // We only need to do this in the 'f' case, as there will be other input tracking events which
                // will mean the delta case works out ok.
                //
                // Create a copy of the body outside the DOM
                // Fill in all the values
                // Get it back.
                //
                // We don't want to do this on the actual body as this might trigger events.
                //
                // It's tempting to use getPath to find a selector, then find that in the clone.  But getPath
                // isn't very efficient on large DOMs.  So we save off all the vals, then go through the clone,
                // where they will appear in the same order, setting them.
                var clone = $('body').clone();
                var vals = [];
                $('input, select, textarea').each(function() {
                    vals.push($(this).val());
                });

                // console.log("Saved vals", vals);

                // Now go through and set them in the copy.
                clone.find('input, select, textarea').each(function() {
                    var val = vals.shift();
                    var $this = $(this);

                    if (val) {
                        if ($this.is("[type='radio']") || $this.is("[type='checkbox']")) {
                            if (val) {
                                $this.attr("checked", "checked");
                            } else {
                                $this.removeAttr("checked");
                            }
                        } else {
                            if ($this.is("select")) {
                                $this.find("option").each(function() {
                                    if ($(this).val() == val) {
                                        $(this).attr("selected", "selected");
                                    }
                                });
                            } else {
                                $this.attr("value", val);
                            }
                        }
                    }
                });

                // var vals2 = [];
                // clone.find('input, select, textarea').each(function() {
                //     vals2.push($(this).val());
                // });
                //
                // console.log("Restored vals", vals2);

                return(clone.html());
            },

            checkDOM: function () {
                var self = this;

                // Use innerHTML as we don't put classes on body, and it allows us to restore the content within
                // a div when replaying.
                var dom = $('body')[0].innerHTML;
                // console.log("DOM initially", dom.length);
                var type;
                var now = (new Date()).getTime();

                if (!this.lastDOM) {
                    // We've not captured the DOM yet
                    type = 'f';
                    strdiff = dom;
                } else {
                    if (dom.length == this.lastDOM.length) {
                        // Very probably, this is exactly the same.  Save some CPU.
                        return;
                    } else if (now - this.lastDOMtime > 30000) {
                        // We save it regularly to handle the case where it gets messed up and would otherwise never
                        // recover.
                        type = 'f';
                        strdiff = dom;
                    } else if (dom.length / this.lastDOM.length < 0.75 || dom.length / this.lastDOM.length > 1.25) {
                        // The two must be pretty different.  Just track the whole thing.
                        //console.log("Don't even bother with a diff", dom.length, this.lastDOM.length);
                        type = 'f';
                        strdiff = dom;
                    } else {
                        var strdiff = JsDiff.createTwoFilesPatch('o', 'n', this.lastDOM, dom);

                        if (strdiff.length > dom.length) {
                            // Not worth tracking the diff, as the diff is bigger than the whole thing, or it's been
                            // a while.  The second is to help us recover from weirdnesses by providing a periodic
                            // reset, which also helps when playing forwards.
                            type = 'f';
                            strdiff = dom;
                        } else {
                            type = 'd';
                        }
                        //console.log("DOM diff", strdiff, strdiff.length);
                    }
                }

                if (type == 'f') {
                    this.lastDOMtime = now;
                    strdiff = this.getWithValues();
                    // console.log("Dom now", dom.length);
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
                    // We don't want to generate selectors based on classes, because we make very heavy use of them,
                    // and the library will do a querySelectorAll call to see if the selector it has generated is
                    // unique, which would match a lot of elements and hence cause us to crawl.  Similarly for large
                    // documents, the tag is not efficient.
                    this.selgen = new CssSelectorGenerator({
                        selectors: ['id', 'nthchild']
                    });
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
