// This sets up the basic structure we need before we can do anything.
//
// If you add a standard jQuery plugin in here which is not AMD-compatible, then it also needs to go in
// requirejs-setup as a shim.
define([
    'jquery',
    'backbone',
    'iznik/underscore',
    'moment',
    'backbone.collectionView',
    'waypoints',
    'dateshim',
    'bootstrap',
    //'persist-min',
    'bootstrap-select',
    'bootstrap-switch',
    'es6-promise',
    'text',
    'twemoji.min',
    'iznik/diff',
    'iznik/events',
    'iznik/utility',
    'iznik/timeago',
    'iznik/majax'
], function ($, Backbone, _, moment) {

    // Promise polyfill for older browsers or IE11 which has less excuse.
    if (typeof window.Promise !== 'function') {
        require('es6-promise').polyfill();
    }

    var Iznik = {
        Models: {
            Activity: {},
            ModTools: {},
            Yahoo: {},
            Plugin: {},
            Message: {},
            Chat: {},
            User: {}
        },
        Views: {
            Activity: {},
            ModTools: {
                Pages: {},
                Message: {},
                Member: {},
                StdMessage: {
                    Pending: {},
                    Approved: {},
                    PendingMember: {},
                    ApprovedMember: {}
                },
                Settings: {},
                User: {},
                Yahoo: {}
            },
            Plugin: {
                Yahoo: {}
            },
            User: {
                Pages: {
                    Find: {},
                    Give: {},
                    Landing: {}
                },
                Home: {},
                Message: {},
                Settings: {}
            },
            Group: {},
            Chat: {},
            Help: {}
        },
        Collections: {
            Activity: {},
            Messages: {},
            Members: {},
            ModTools: {},
            Chat: {},
            Yahoo: {},
            User: {}
        }
    };

    function cacheKey(url, data) {
        // Get a unique key for this URL and data.  The data is important because it is passed to the AJAX call and
        // can therefore return different data.
        return("cache." + encodeURIComponent(url) + "." + encodeURIComponent(JSON.stringify(data)));
    }

    Iznik.Model = Backbone.Model.extend({
        toJSON2: function () {
            var json;

            if (this.toJSON) {
                json = this.toJSON.call(this);
            } else {
                var str = JSON.stringify(this.attributes);
                json = JSON.parse(str);
            }

            return (json);
        }
        // , fetch: function (options) {
        //     var self = this;
        //     console.log("Fetch model ", self.get('id')); console.trace();
        //     return Backbone.Model.prototype.fetch.call(self);
        // }
    });

    // We have the ability to cache in storage.  This is controlled by several optional parameters.
    //
    // For now we only cache collection fetches because model fetches are unlikely to be on page load, which is
    // what we're interested in optimising.
    //
    // cached is a callback which will be invoked if we can satisfy a request from cache.  Default to no caching.
    // cacheExpiry is the lifetime in seconds of the cache entry corresponding to this fetch.  Default 48 hours.  We
    //   always call cached with expired data because it looks better for the user to see the screen populate and then
    //   update than it does to see a blank screen.
    // cacheOnly indicates whether to bother doing a fetch at all if we managed to use a cached version.  Default false.
    // cacheFetchAfter is a delay in seconds before issuing any fetch after successfully finding it in the cache.
    //   This can be useful for page load - if we manage to populate the page with cached data then we can refresh
    //   it later when things have quietened down, which makes the page feel more responsive to users while keeping
    //   the data roughly up to date.  Default to 3-10 seconds with some randomness, which means it will usually
    //   happen after the page has rendered.
    Iznik.Collection = Backbone.Collection.extend({
        model: Iznik.Model,

        promise: null,

        constructor: function (options) {
            this.options = options || {};
            Backbone.Collection.apply(this, arguments);
        }, fetch: function(options) {
            var self = this;
            var issueFetch = true;
            var fetchDelay = 0;
            var url = typeof self.url == 'string' ? self.url : self.url();

            if (options && options.cached) {
                // We would like a cached fetch.
                var key = cacheKey(url, options.data);
                // console.log("Fetch key", url, key);

                try {
                    var cached = Storage.get(key);
                    // console.log("Cache get returned", cached ? cached.length : null);
                    var expires = Storage.get(key + '.time');
                    // console.log("Expires", expires);

                    if (cached && expires) {
                        // We have some cached data.  Put it into the collection.
                        // console.log("Got cached data");
                        var data = JSON.parse(cached);
                        self.set(data);
                        // console.log("Collection after set", self);

                        // Now invoke our callback to show we've completed.
                        options.cached();

                        var now = (new Date()).getTime();
                        var age = now - expires;
                        var expiry = options.hasOwnProperty('cacheExpiry') ? options.cacheExpiry : 60 * 60 * 48;
                        // console.log("Compare expire", age, expiry);

                        // We want to fetch if our cache has expired, or if it is valid but we don't just want the
                        // cached value.
                        issueFetch = age >= expiry || !options.cacheOnly;

                        if (issueFetch && age >= expiry) {
                            // Our entry has expired and we are going to get a new one.  It's possible that this
                            // might fail due to quota issues.  Zap our old one to avoid always showing data
                            // that is too old.
                            try {
                                Storage.remove(key);
                                Storage.remove(key + '.time');
                            } catch (e) {}
                        }

                        // We might want to delay it.
                        fetchDelay = options.hasOwnProperty('cacheFetchAfter') ? (options.cacheFetchAfter * 1000) :
                            (3000 + Math.floor(Math.random() * 7000));
                    }
                } catch (e) {console.error(e.message);}

                // console.log("Cached collection fetch", url, issueFetch, fetchDelay); console.trace();
            }

            if (issueFetch) {
                // Use our own promise so that we can get the data if we need to.
                self.promise = new Promise(function(resolve, reject) {
                    // We don't have a cached value.  Fetch it.
                    function issueFetch() {
                        // console.log("Issue fetch", options);
                        Backbone.Collection.prototype.fetch.call(self, options).then(function() {
                            // TODO Error handling?
                            if (options && options.cached) {
                                // We have fetched it - save it in our cache (before the caller can mess with it).
                                // console.log("Fetched, save it", url);
                                try {
                                    var key = cacheKey(url, options.data);
                                    var data = JSON.stringify(self.toJSON());

                                    // CC   if (Persist.size == -1 || data.length < Persist.size) { // CC
                                        // Don't cache stuff that's too big.
                                        try {
                                            Storage.set(key, data);
                                            Storage.set(key + '.time', (new Date()).getTime());
                                            // console.log("Stored length", key, Storage.get(key).length);
                                        } catch (e) {
                                            // Failed.  Most likely quota - tidy some stuff up, including
                                            // this value so that it doesn't stay out of date.
                                            Storage.remove(key);
                                            Storage.remove(key + '.time');

                                            console.log("Failed to set", e.message);
                                            Storage.iterate(function(k,v) {
                                                console.log("Consider prune ", k);
                                                if (k.indexOf('cache.') === 0 ||
                                                    (k.indexOf('chat-') !== -1 &&
                                                    (k.indexOf('-width') !== -1 || k.indexOf('-height') !== -1 || k.indexOf("-lp") !== -1))) {
                                                    console.log("Remove", k, v.length);
                                                    Storage.remove(k);
                                                }
                                            });
                                        }
                                    /* CC } else {
                                        // We can't cache this, as it's too big.  Remove any previously cached data
                                        // which might be below this limit, as otherwise it will persist forever and
                                        // become increasingly misleading.
                                        console.log("Don't cache too long", key, data.length);
                                        Storage.remove(key);
                                        Storage.remove(key + '.time');
                                    } */
                                } catch (e) {console.log("Exception", e); console.error(e.message);}
                            }

                            // Now tell the caller the fetch has completed.
                            // console.log("Resolve fetch");
                            resolve();
                        });
                    }

                    // Now fetch - immediately or after a delay.
                    if (fetchDelay > 0) {
                        // console.log("Delay fetch for", fetchDelay);
                        window.setTimeout(issueFetch, fetchDelay);
                    } else {
                        // console.log("Immediate fetch");
                        issueFetch();
                    }
                });
            } else {
                self.promise = resolvedPromise();
            }

            return(self.promise);
        }
    });

    Iznik.View = (function (View) {

        var ourview = View.extend({
            globalClick: function (e) {
                // When a click occurs, we block further clicks for a few seconds, to stop double click damage.
                $(e.target).addClass('blockclick');
                window.setTimeout(function () {
                    $(e.target).removeClass('blockclick');
                }, 5000);

                // We also want to spot if an AJAX call has been made; since this goes to the server, it may take a
                // while before it completes and the user sees some action.  We add a class to pulse the element to
                // provide visual comfort.
                //
                // Note that we expect to have one outstanding request (our long poll) at all times.
                window.setTimeout(function () {
                    if ($.active > 1) {
                        // An AJAX call was started in another click handler.  Start pulsing.
                        $(e.target).addClass('showclicked');

                        window.setTimeout(function () {
                            // The pulse should be removed in the ajaxStop handler, but have a fallback just in
                            // case.
                            $(e.target).removeClass('showclicked');
                        }, 5000);
                    }
                }, 0);
            },

            constructor: function (options) {
                this.options = options || {};
                View.apply(this, arguments);
            },

            checkDOM: function(self) {
                console.log("CheckDOM", this);
                if (!self) {
                    self = this;
                }

                if (self.$el.closest('body').length > 0) {
                    console.log("Now in DOM", self);
                    self.inDOMProm.resolve(self);
                } else {
                    console.log("Not in DOM yet", self);
                    window.setTimeout(self.checkDOM, 50, self);
                }
            },

            ourRender: function() {
                if (this.model) {
                    this.$el.html(window.template(this.template)(this.model.toJSON2()));
                } else {
                    this.$el.html(window.template(this.template));
                }

                return this;
            },

            render: function () {
                // A key difference from normal Backbone is that our render method is async and returns a Promise,
                // rather than synchronous.  The reason for this is to allow us to fetch templates on demand from
                // the server, rather than in a big blob.  This reduces page load time.
                //
                // This means that where we override render in a view, and call the prototype render, we have to both 
                // issue a then() on the returned promise and return it from our own render.  So you'll see code
                // along the lines of:
                //
                // var p = Iznik.View.prototype.render.call(this);
                // p.then(function() {...}
                // return(p);
                //
                // You'll get used to it.
                var self = this;
                var promise = new Promise(function(resolve, reject) {
                    if (!self.template) {
                        // We don't have a template.  We can render.
                        resolve(self.ourRender.call(self));

                        if (self.hasOwnProperty('triggerRender')) {
                            // We don't often need this, so it's controlled by a flag.
                            self.trigger('rendered');
                        }
                    } else {
                        // We have a template.  We need to fetch it.
                        templateFetch(self.template).then(function() {
                            resolve(self.ourRender.call(self));
                            if (self.hasOwnProperty('triggerRender')) {
                                self.trigger('rendered');
                            }
                        })
                    }
                });

                return(promise);
            },

            inDOM: function () {
                return (this.$el.closest('body').length > 0);
            },

            waitDOM: function(self, cb) {
                // Sometimes, we need to wait until our rendering has completed and an element is in the DOM.  We
                // do this in a rather clunky polling way, as it's not idiomatic with promises.
                if (self.$el.closest('body').length > 0) {
                    cb.call(self, self);
                } else {
                    window.setTimeout(self.waitDOM, 50, self, cb);
                }
            },

            destroyIt: function () {
                this.undelegateEvents();
                this.$el.removeData().unbind();
                this.remove();
                Backbone.View.prototype.remove.call(this);
            }
        });

        ourview.extend = function (child) {
            // We want to inherit events when we extend a view.  This is useful in cases such as a modal which has
            // its own events but wants the modal events too.
            //
            // We do this by overriding extend itself, so that we merge in the events from the child.  Using
            // _.extend to do this makes weird bad things happen, so we do it ourselves in JS.
            //
            // We don't have to worry about the case where the events property is a function because we don't use that.
            var view = View.extend.apply(this, arguments);

            if (view.prototype.events) {
                if (child.hasOwnProperty('events')) {
                    var ourevents = typeof this.prototype.events !== 'undefined' ? jQuery.extend({}, this.prototype.events) : {
                        // 'click .btn': 'globalClick'
                    };
                    for (var i in child.events) {
                        ourevents[i] = child.events[i];
                    }

                    view.prototype.events = ourevents;
                }
            }

            return view;
        }

        return (ourview);

    })(Backbone.View);

    Iznik.View.Timeago = Iznik.View.extend({
        timeagoRunning: false,

        render: function() {
            var self = this;

            // Expand the template via the parent then set the times.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                if (!self.timeagoRunning) {
                    self.timeagoRunning = true;

                    // We want to ensure this gets updated, and also update the title to be human readable on
                    // mouseover.  But we don't need to do this immediately, so delay it, to avoid extra
                    // expensive DOM manipulation during page load.
                    _.delay(_.bind(function() {
                        var self = this;

                        self.$('.timeago').each(function() {
                            var $el = $(this);
                            var d = $el.prop('title');

                            if (d) {
                                // Ensure that we will keep this up to date.
                                $el.timeago(new moment(d));

                                // Prettify the title so that it looks readable on mouseover.
                                var s = (new moment(d)).format('LLLL');
                                $el.prop('title', s);
                            }
                        });
                    }, self), 30000);
                }
            });

            return(p);
        }
    });

    // Save as global as it's useful for debugging.
    window.Iznik = Iznik;

    return (Iznik);
}, function (err) {
    // We failed to load.  This could be due to various things - network error, server issues.  We've also seen it
    // when the service worker is updated and this causes our fetch requests to fail.  Reload the page after a slight
    // delay.
    console.log("Require error", err);
    panicReload();
});
