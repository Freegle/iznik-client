// This sets up the basic structure we need before we can do anything.
//
// If you add a standard jQuery plugin in here which is not AMD-compatible, then it also needs to go in
// requirejs-setup as a shim.
define([
    'jquery',
    'backbone',
    'backbone.collectionView',
    'waypoints',
    'moment',
    'timeago',
    'dateshim',
    'bootstrap',
    'bootstrap-select',
    'bootstrap-switch',
    'bootstrap-tagsinput',
    'es6-promise',
    'text',
    'iznik/diff',
    'iznik/events',
    'iznik/underscore',
    'iznik/utility',
    'iznik/majax'
], function ($, Backbone, _) {

    // Promise polyfill for older browsers or IE11 which has less excuse.
    if (typeof window.Promise !== 'function') {
        console.log("Promise polyfill");
        require('es6-promise').polyfill();
    } else {
        console.log("Got Promise");
    }

    var Iznik = {
        Models: {
            ModTools: {},
            Yahoo: {},
            Plugin: {},
            Message: {},
            Chat: {}
        },
        Views: {
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
                    Give: {}
                },
                Home: {},
                Message: {}
            },
            Group: {},
            Chat: {},
            Help: {}
        },
        Collections: {
            Messages: {},
            Members: {},
            ModTools: {},
            Chat: {},
            Yahoo: {}
        }
    };

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
    });

    Iznik.Collection = Backbone.Collection.extend({
        model: Iznik.Model,

        constructor: function (options) {
            this.options = options || {};
            Backbone.Collection.apply(this, arguments);
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

            waitDOM: function(self) {
                // Some libraries we use don't work properly until the view element is in the DOM.
                function pollRecursive() {
                    return self.$el.closest('body').length > 0 ?
                        Promise.resolve(true) :
                        Promise.delay(50).then(pollRecursive);
                }

                return pollRecursive();
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
                        self.trigger('rendered');
                    } else {
                        // We have a template.  We need to fetch it.
                        templateFetch(self.template).then(function() {
                            resolve(self.ourRender.call(self));
                            self.trigger('rendered');
                        })
                    }
                })

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
        render: function() {
            // Expand the template via the parent then set the times.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.$('.timeago').timeago();
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
