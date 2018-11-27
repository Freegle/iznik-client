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

            $('body').css('padding-right', '');
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

            $('body').css('padding-right', '');
        },

        attach: function() {
            var self = this;

            // Remove any previous modal.
            if (modalOpen) {
                modalOpen.cancel();
            }

            modalOpen = this;
            
            // Attach the modal to the DOM
            $('#bodyContent').append(this.$el);

            // Show it.
            this.$el.find('.modal').modal({
                show: true,
                backdrop: 'static'
            });

            this.$el.find('.modal').on('hidden.bs.modal', function () {self.cancel.call(self);});

            //console.log(this.$el.html());

            // We cancel after a link is clicked so that the view can tidy itself up, unless
            // it will have opened a new tab.
            this.$el.on('click', 'a:not([data-realurl])', function(){
                self.cancel();
            });

            // Because this happens aysynchronously, our events might not be set up.
            this.delegateEvents();
        },

        open: function(template){
            var self = this;
            var p;

            // We want the back button to close the modal.  We achieve this by adding an entry for the modal here.
            // When we get the popstate event we can then close this modal.
            window.history.pushState('modalOpen', null, window.location.href);

            if (template) {
                // For more complex modals we might have set up the content before calling open.
                self.template = template;
                p = Iznik.View.prototype.render.call(this);
                p.then(function(self) {
                    self.attach.call(self);
                });
            } else {
                this.attach();
                p = Iznik.resolvedPromise(self);
            }

            // Bootstrap seems to add padding, which has the effect of shrinking the body as more modals open.
            self.$('.modal').one('hidden.bs.modal', function () {
                $('body').css('padding-right', '');
            })
            self.$('.modal').one('shown.bs.modal', function () {
                $('body').css('padding-right', '');
            });

            return(p);
        },

        render: function() {
            return(this.open(this.template, this.model));
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
    var waitCount = 0;
    var waitOpen = null;
    var waitPromise = null;

    Iznik.Views.PleaseWait = Iznik.Views.Modal.extend({
        template: 'wait',

        timeout: null,
        closeTimeout: null,
        isOpen : false,
        closeAfter: 20000,  // 60000,

        render: function() {
            var self = this;
            waitCount++;

            self.options.label = self.options.label ? self.options.label : 'unknown caller';
            console.log("Start pleasewait", self.options.label, waitCount); // console.trace();

            this.timeout = setTimeout(function() {
                self.timeout = null;

                if (!waitOpen) {
                    // We don't have a modal open.  Open ours.
                    console.log("Open pleasewait", self.options.label);
                    waitOpen = self;
                    waitPromise = self.open(self.template);

                    // Loader depends on which site we are.
                    waitPromise.then(function() {
                        $('#js-modalloader').attr('src', iznikroot + (MODTOOLS ? 'images/loadermodal.gif' : 'images/userloader.gif'));    // CC
                        $('#js-modalloader').show();
                    });

                    // Start backstop timeout to close the modal - there are various error cases which could leave
                    // it stuck forever, which looks silly.
                    self.closeTimeout = setTimeout(function() {
                        console.log("Closed wait backstop", self.closeAfter);
                        self.close();
                    }, self.closeAfter);
                }
            }, self.options.timeout ? self.options.timeout : 3000);

            console.log("this.timeout", this.timeout);

            return(Iznik.resolvedPromise(this));
        },

        close: function() {
            var self = this;

            if (this.timeout) {
                console.log("Clear show timer", self.options.label, this.timeout);
                clearTimeout(this.timeout);
            }

            if (this.closeTimeout) {
                console.log("Clear backstop timer", self.options.label, this.closeTimeout);
                clearTimeout(this.closeTimeout);
            }

            waitCount--;
            console.log("Close pleasewait: waits open", self.options.label, waitCount); //console.trace();

            if (waitCount === 0 && waitOpen) {
                // We don't need any more open.  But this one might not quite have rendered yet, so we need to wait.
                console.log("Close wait open one", self.options.label, waitOpen);
                waitPromise.then(function() {
                    console.log("Close wait after open rendered", self.options.label);
                    Iznik.Views.Modal.prototype.close.call(waitOpen);
                    waitOpen = null;
                });
            }
        }
    });

    // We want the back button to close any open modal.
    $(window).on('popstate', function(event, data) {
        if (modalOpen) {
            modalOpen.cancel();
            modalOpen = null;
        }
    });
});