define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
        var modalOpen = null;

    Iznik.Views.Modal = Iznik.View.extend({
        events: {
            "click .js-cancel": "cancel",
            "click .js-close": "close",
            "keydown": "keydown"
        },

        keydown: function(e) {
            switch (e.which) {
                // esc
                case 27 :
                    this.cancel();
                    break;
            }
        },

        cancel: function(){
            // We hide the dialog, but keep the DOM elements until after the callback has been
            // made in case the callback wants to extract data from them.
            this.$el.find('.modal').modal('hide');
            var body = $('body');
            body.removeClass('modal-open');
            $('.modal-backdrop').remove();

            this.trigger('modalCancelled');
            //noinspection JSJQueryEfficiency
            body.trigger('modalCancelled');

            Iznik.View.prototype.remove.call(this);
            this.remove();
            this.undelegateEvents();
            $(this.el).removeData().unbind();
        },

        close: function(){
            // We hide the dialog, but keep the DOM elements until after the callback has been
            // made in case the callback wants to extract data from them.
            this.$el.find('.modal').modal('hide');
            var body = $('body');
            body.removeClass('modal-open');
            $('.modal-backdrop').remove();

            this.trigger('modalClosed');
            body.trigger('modalClosed');

            Backbone.View.prototype.remove.call(this);
            this.remove();
            this.undelegateEvents();
            this.$el.removeData().unbind();
        },

        attach: function() {
            var self = this;

            // Attach the modal to the DOM
            $('#bodyContent').append(this.$el);

            // Show it.
            this.$el.find('.modal').modal({
                show: true,
                backdrop: 'static'
            });

            this.$el.find('.modal').on('hidden.bs.modal', function () {self.cancel.call(self);});

            //console.log(this.$el.html());

            // We cancel after a link is clicked so that the view can tidy itself up.
            this.$el.on('click', 'a', function(){
                self.cancel();
            });

            // Because this happens aysynchronously, our events might not be set up.
            this.delegateEvents();
        },

        open: function(template){
            var self = this;
            // Remove any previous modal.
            if (modalOpen) {
                modalOpen.cancel();
            }

            modalOpen = this;

            if (template) {
                // For more complex modals we might have set up the content before calling open.
                Iznik.View.prototype.render.call(this).then(function(self) {
                    self.attach.call(self);
                });
            } else {
                this.attach();
            }
        },

        render: function() {
            this.open(this.template, this.model);
            return(resolvedPromise(this));
        }
    });

    Iznik.Views.Confirm = Iznik.Views.Modal.extend({
        template: 'confirm',

        events: {
            'click .js-confirm': 'confirm',
            'click .js-cancel': 'cancel'
        },

        confirm: function() {
            this.trigger('confirmed');
            this.close();
        },

        cancel: function() {
            this.trigger('cancelled');
            this.close();
        },

        render: function() {
            this.open(this.template);
        }
    });

    // Please wait popup.  Need to avoid nesting issues.
    var waitModals = [];

    Iznik.Views.PleaseWait = Iznik.Views.Modal.extend({
        template: 'wait',

        timeout: null,

        render: function() {
            var self = this;
            this.waitCount = waitModals.length;
            waitModals.push(this);

            if (this.waitCount == 0) {
                this.timeout = setTimeout(function() {
                    self.timeout = null;
                    self.open(self.template);
                }, self.options.timeout ? self.options.timeout : 3000);
            }

            return(resolvedPromise(this));
        },

        close: function() {
            if (this.waitCount == 0) {
                // We opened a modal; close it.
                if (this.timeout) {
                    clearTimeout(this.timeout);
                } else {
                    Iznik.Views.Modal.prototype.close.call(this);
                }
            }

            waitModals = _.without(waitModals, this);

            if (waitModals.length > 0) {
                // We have more queued.
                var first = _.first(waitModals);
                first.render();
            }
        }
    });
});