// This sets up the basic structure we need before we can do anything.
//
// If you add a standard jQuery plugin in here which is not AMD-compatible, then it also needs to go in
// requirejs-setup as a shim.
define([
    'jquery',
    'backbone',
    'backbone.collectionView',
    'backform',
    'waypoints',
    'moment',
    'timeago',
    'dateshim',
    'bootstrap',
    'bootstrap-select',
    'bootstrap-switch',
    'bootstrap-tagsinput',
    'jquery.dd',
    'jquery.dotdotdot',
    'iznik/underscore',
    'iznik/utility',
    'iznik/majax'
], function($, Backbone, _) {
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
            globalClick: function(e) {
                // When a click occurs, we block further clicks for a few seconds, to stop double click damage.
                $(e.target).addClass('blockclick');
                window.setTimeout(function() {
                    $(e.target).removeClass('blockclick');
                }, 5000);

                // We also want to spot if an AJAX call has been made; since this goes to the server, it may take a
                // while before it completes and the user sees some action.  We add a class to pulse the element to
                // provide visual comfort.
                //
                // Note that we expect to have one outstanding request (our long poll) at all times.
                window.setTimeout(function() {
                    if ($.active > 1) {
                        // An AJAX call was started in another click handler.  Start pulsing.
                        $(e.target).addClass('showclicked');

                        window.setTimeout(function() {
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

            render: function () {
                var self = this;

                if (self.model) {
                    self.$el.html(window.template(self.template)(self.model.toJSON2()));
                } else {
                    self.$el.html(window.template(self.template));
                }

                return self;
            },

            inDOM: function() {
                return(this.$el.closest('body').length > 0);
            },

            destroyIt: function () {
                this.undelegateEvents();
                this.$el.removeData().unbind();
                this.remove();
                Backbone.View.prototype.remove.call(this);
            }
        });

        ourview.extend = function(child) {
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

        return(ourview);

    })(Backbone.View);

    // Save as global as it's useful for debugging.
    window.Iznik = Iznik;
    
    return(Iznik);
});