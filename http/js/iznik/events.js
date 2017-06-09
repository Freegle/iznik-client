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
    var sessionCookie = null;

    var monitor = (function () {
        return ({
            lastDOM: null,
            lastDOMtime: 0,
            idCount: 0,
            running: true,

            trackEvent: function(target, event, posX, posY, data, timestamp) {
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
            },

            flushEventQueue: function() {
                var self = this;

                flushTimerRunning = false;

                if (eventQueue.length > 0) {
                    // If we fail, we'll lose events.  Oh well.
                    //
                    // We might have multiple mutation events for the same target.  If so, there's no point sending
                    // them to the server, with the cost in bandwidth and encoding time.
                    var mutTargets = [];
                    _.each(eventQueue, function(ent) {
                        if (ent.event == 'mutation') {
                            mutTargets[ent.target] = ent;
                        }
                    });

                    var currQueue = [];
                    _.each(eventQueue, function(ent) {
                        if (ent.event == 'mutation') {
                            if (ent === mutTargets[ent.target]) {
                                // It's the last.
                                currQueue.push(ent);
                            }
                        } else {
                            currQueue.push(ent);
                        }
                    });

                    eventQueue = [];

                    // If we have too much data, throw it away.
                    if (eventQueue.length < 20000) {
                        var eventhost = $('meta[name=iznikevent]').attr("content");

                        // We will typically be posting to another domain, to avoid delaying requests on the main
                        // domain because of event tracking (the per host connection limit).  This means that our
                        // session from the main domain won't be inherited unless we set it manually.  It's the same
                        // system under the covers, so the session is still valid.
                        if (!sessionCookie) {
                            try {
                                var sess = Storage.get('session');
                                if (sess) {
                                    sess = JSON.parse(sess);
                                    sessionCookie = sess.session;
                                    // console.log("Got session from local", sessionCookie);
                                }
                            } catch (e) {console.log(e.message)};
                        }

                        var me = Iznik.Session.get('me');
                        var myid = me ? me.id : null;

                        // console.log("Flush events", currQueue);

                        $.ajax({
                            url: 'https://' + eventhost + API + 'event',
                            type: 'POST',
                            data: {
                                api_key: sessionCookie,
                                userid: myid,
                                events: currQueue
                            }, success: function(ret) {
                                if (ret.ret === 0) {
                                    // Save the cookie
                                    sessionCookie = ret.session;

                                    if (!flushTimerRunning) {
                                        flushTimerRunning = true;
                                        window.setTimeout(_.bind(self.flushEventQueue, self), 5000);
                                    }
                                }
                            }
                        });
                    }
                } else if (!flushTimerRunning) {
                    flushTimerRunning = true;
                    window.setTimeout(_.bind(self.flushEventQueue, self), 5000);
                }
            },

            checkScroll: function() {
                var self = this;

                // Record scroll position in scrollable divs, e.g. chat windows.
                $('.overscrolly').each(function(i) {
                    var scrollTop = this.scrollTop;
                    if (this.scrollHeight > this.clientHeight && this.scrollTop > 0) {
                        var path = self.getPath($(this));
                        var timestamp = (new Date()).getTime();
                        self.trackEvent(path[0], 'scrollpos', null, null, this.scrollTop, timestamp);
                    }
                });
            },

            getWithValues: function(target) {
                // What we get from innerHTML doesn't have any values in it which
                // were changed post initial insertion.  So we need to get the DOM with all the values.
                //
                // Create outside the DOM
                // Fill in all the values
                // Get it back.
                //
                // We don't want to do this on the actual body as this might trigger events.
                //
                // It's tempting to use getPath to find a selector, then find that in the clone.  But getPath
                // isn't very efficient on large DOMs.  So we save off all the vals, then go through the clone,
                // where they will appear in the same order, setting them.
                var clone = $(target).clone();
                var vals = [];
                $(target).find('input, select, textarea').each(function() {
                    vals.push($(this).val());
                });

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

                return(clone);
            },

            // We have a background timer to spot DOM changes which are not driven by events such as clicks.
            startTimer: function () {
                window.setTimeout(_.bind(this.checkTimer, this), 200);
            },

            snapshotDOM: function() {
                var self = this;

                // Use innerHTML as we don't put classes on body, and it allows us to restore the content within
                // a div when replaying.
                //
                // Clone to get input state.
                var dom = this.getWithValues('body').html();
                // console.log("With values", dom);
                // console.log("Without", $('body').html());

                if (dom && dom.length > 0) {
                    var timestamp = (new Date()).getTime();
                    self.trackEvent('body', 'mutation', null, null, dom, timestamp);
                }
            },

            checkTimer: function () {
                this.checkScroll();
                // this.snapshotDOM();
                this.startTimer();
            },

            getPath: function (node) {
                if (node.get(0).tagName.toLowerCase() == 'body') {
                    return('body');
                }

                // We need this to be efficient, so we put an id on every element.
                var id = node.attr('id');
                var usableid = null;

                if (!id) {
                    id = 'eventtracking' + this.idCount++;
                    node.attr('id', id);

                    // We've just set this id, so we can't also use it as a target.  Find the closest element with
                    // an id.
                    var closest = node.parent().closest('[id]');
                    if (closest.length > 0) {
                        if (closest.get(0).tagName.toLowerCase() == 'body') {
                            usableid = 'body';
                        } else {
                            usableid = '#' + closest.attr('id');
                        }
                    }
                } else {
                    usableid = '#' + id;
                }

                return(['#' + id, usableid ? usableid : 'body']);
            },

            start: function () {
                var self = this;

                self.snapshotDOM();

                // Capture scrolls on the window
                $(window).scroll(function (e) {
                    var scroll = $(window).scrollTop();
                    self.trackEvent('window', 'scroll', null, null, scroll);
                });

                // Track mouse movements
                (function () {
                    var lastX = null;
                    var lastY = null;
                    var granularity = 10;

                    $(document).mousemove(function (e) {
                        if (Math.abs(lastX - e.pageX) > granularity || Math.abs(lastY - e.pageY) > granularity) {
                            self.trackEvent('window', 'mousemove', e.pageX, e.pageY, null);

                            lastX = e.pageX;
                            lastY = e.pageY;
                        }
                    });
                })();

                // Track mouse clicks
                $(window).click(function (e) {
                    self.trackEvent('window', 'click', e.pageX, e.pageY, null);
                });

                // Track input changes
                $(window).on('keydown', _.bind(function(e) {
                    var type = $(e.target).get(0).tagName.toLowerCase();

                    if (type == 'input' || type == 'textarea' || type == 'select') {
                        var val = $(e.target).val();
                        if (val) {
                            var path = self.getPath($(e.target))[0];
                            self.trackEvent(path, 'input', e.pageX, e.pageY, val);
                        }
                    }
                }, self));

                // Monitor for DOM changes
                try {
                    self.mutationObserver = new MutationObserver(function(mutations) {
                        if (self.running) {
                            try {
                                // We might well get many mutations for the same target, for example when doing slideUp.  So
                                // first scan to find the separate targets.
                                var paths = [];
                                _.each(mutations, function(mutation) {
                                    if (mutation.target) {
                                        // Get the paths (this sets up ids so we need to do it first).
                                        var path = self.getPath($(mutation.target));
                                        paths[path[0]] = [path, mutation.target];
                                    }
                                });

                                // Now scan
                                for (var key in paths) {
                                    var path = paths[key][0];
                                    var target = paths[key][1];

                                    // Grab the HTML.  We can't use getWithValues because that does a clone, which in turn
                                    // causes more mutations.
                                    var timestamp = (new Date()).getTime();
                                    // Get the paths (this sets up ids so we need to do it first).
                                    //
                                    // First make sure there's an id on this target.
                                    var inputs = [].concat(
                                        Array.prototype.slice.call(target.getElementsByTagName('input'), 0),
                                        Array.prototype.slice.call(target.getElementsByTagName('textarea'), 0),
                                        Array.prototype.slice.call(target.getElementsByTagName('select'), 0));

                                    var inputvals = [];
                                    _.each(inputs, function(input) {
                                        var val = $(input).val();
                                        if (val) {
                                            inputvals[self.getPath($(input))[0]] = val;
                                        }
                                    });

                                    // If we just set an id, then we can't use it as a path because it won't yet exist
                                    // in the DOM when we're replaying.  But the usable path returned will.
                                    //
                                    // Get the HTML - which will now include the ids.  It needs to, because subsquent
                                    // events use those to set values.
                                    if (path[1]) {
                                        var el = $(path[1]).get(0);

                                        if (!_.isUndefined(el)) {
                                            var html = el.outerHTML;
                                            self.trackEvent(path[1], 'mutation', null, null, html, timestamp);
                                        }
                                    }

                                    // Now pick up any input values within it.
                                    for (var path in inputvals) {
                                        self.trackEvent(path, 'input', null, null, inputvals[path], timestamp);
                                    }
                                }
                            } catch (e) {
                                // We can get storage exceptions.  Give up.
                                console.log("Give up on observer", e);
                                self.running = false;
                            }
                        }
                    });

                    self.mutationObserver.observe($('body').get(0), {
                        childList: true,
                        attributes: true,
                        subtree: true
                    });
                } catch (e) { console.log("Mutation start failed", e.message); }

                flushTimerRunning = true;
                window.setTimeout(_.bind(self.flushEventQueue, self), 5000);

                this.startTimer();
            }
        });
    })();

    return(monitor);
});
