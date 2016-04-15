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
    'ajaxq',
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

    // Set options into this.options by default.
    Iznik.View = (function (View) {
        return View.extend({
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
    })(Backbone.View);

    return(Iznik);
});