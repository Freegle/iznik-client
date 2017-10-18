define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    // Stickily dismissible help boxes.
    Iznik.Views.Help.Box = Iznik.View.extend({
        events: {
            'click .js-closehelp': 'close',
            'click .js-close': 'close',
            'click .js-show': 'show'
        },

        close: function () {
            var self = this;
            // We hide this box and try to set local storage to keep it hidden.
            try {
                Storage.set('help.' + this.template, true);
            } catch (e) {
            }

            this.$el.fadeOut('slow');
        },

        show: function (e) {
            // We hide this box and try to set local storage to keep it hidden.
            try {
                Storage.remove('help.' + this.template);
            } catch (e) {
            }

            this.render();

            e.preventDefault();
            e.stopPropagation();
        },

        render: function () {
            // We might have already said we don't want to see this.
            var self = this;
            var show = true;

            try {
                show = Storage.get('help.' + this.template) == null;
            } catch (e) {
            }

            var p;
            
            if (show) {
                // We need to render it.
                p = Iznik.View.prototype.render.call(this);
            } else {
                this.$el.html('<a href="#" class="pull-right js-show"><span class="glyphicon glyphicon-question-sign" />&nbsp;Show help</a><br class="clearfix" />');
                p = new Promise(function(resolve, reject) {
                    resolve(self);
                });
            }

            return(p);
        }
    });
});