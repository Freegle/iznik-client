define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    // Stickily dismissible help boxes.
    Iznik.Views.Help.Box = Iznik.View.extend({
        events: {
            'click .js-close': 'close'
        },

        close: function () {
            // We hide this box and try to set local storage to keep it hidden.
            try {
                localStorage.setItem('help.' + this.template, true);
            } catch (e) {
            }

            this.$el.fadeOut('slow');
        },

        render: function () {
            // We might have already said we don't want to see this.
            var show = true;

            try {
                show = localStorage.getItem('help.' + this.template) == null;
            } catch (e) {
            }

            if (show) {
                this.$el.html(window.template(this.template));
            }

            return (this);
        }
    });
});