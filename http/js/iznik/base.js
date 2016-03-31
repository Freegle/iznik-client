// This sets up the basic structure we need before we can do anything.
//
// TODO Some of these plugins may not be used.
//     "js/lib/jquery-ui.js",
//     "js/lib/FileSaver.min.js",
//     "js/lib/combodate.js",
//     "js/lib/jquery-dateFormat.min.js",
//     "js/lib/jquery.scrollTo.js",
//     "js/lib/jquery.ui.touch-punch.js",
//     "js/lib/json2.js",
//     "js/lib/flowtype.js",
//     "js/lib/Sortable.js",
//     "js/lib/notify.js",
//     "js/lib/validator.min.js",
//     "js/lib/richMarker.js",
//     "js/lib/markerclusterer.min.js",
//     "js/lib/placeholders.min.js",
//     "js/lib/bootstrap-datepicker.js",
//     "js/lib/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
//     "js/lib/bootstrap-datepicker.en-GB.js",
//     "js/lib/jquery-show-first.js",
//     "js/lib/jquery-visibility.js",
//     "js/lib/typeahead.jquery.js",
//     "js/lib/jquery-file-upload/load-image.all.min.js",
//     "js/lib/jquery-file-upload/canvas-to-blob.min.js",
//     "js/lib/jquery-file-upload/jquery.iframe-transport.js",
//     "js/lib/jquery-file-upload/jquery.fileupload.js",
//     "js/lib/jquery-file-upload/jquery.fileupload-process.js",
//     "js/lib/jquery-file-upload/jquery.fileupload-image.js",
//     "js/lib/bootstrap-tagsinput.js",
//     "js/lib/wicket-gmap3.js",
//     "js/iznik/zombies.js",
//     "js/iznik/facebook.js",
//     "js/iznik/google.js",
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
    'jquery-show-first',
    'iznik/underscore',
    'iznik/utility',
    'iznik/majax'
], function($, Backbone, _) {
    var Iznik = {
        Models: {
            ModTools: {},
            Yahoo: {},
            Plugin: {},
            Message: {}
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
                Message: {}
            },
            Group: {},
            Help: {}
        },
        Collections: {
            Messages: {},
            Members: {},
            ModTools: {},
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