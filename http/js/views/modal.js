var modalOpen = null;

Iznik.Views.Modal = IznikView.extend({
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
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();

        this.trigger('modalCancelled');
        //noinspection JSJQueryEfficiency
        $('body').trigger('modalCancelled');

        IznikView.prototype.remove.call(this);
        this.remove();
        this.undelegateEvents();
        $(this.el).removeData().unbind();
    },

    close: function(){
        // We hide the dialog, but keep the DOM elements until after the callback has been
        // made in case the callback wants to extract data from them.
        this.$el.find('.modal').modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();

        this.trigger('modalClosed');
        //noinspection JSJQueryEfficiency
        $('body').trigger('modalClosed');

        Backbone.View.prototype.remove.call(this);
        this.remove();
        this.undelegateEvents();
        this.$el.removeData().unbind();
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
            this.$el.html(window.template(template)(this.model ? this.model.toJSON2() : null));
        }

        // Attach the modal to the DOM
        $('#bodyContent').append(this.$el);

        // Show it.
        this.$el.find('.modal').modal({
            show: true,
            backdrop: 'static'
        });

        this.$el.find('.modal').on('hidden.bs.modal', function () {console.log("Modal hidden"); self.cancel.call(self);});

        //console.log(this.$el.html());

        // We cancel after a link is clicked so that the view can tidy itself up.
        this.$el.on('click', 'a', function(){
            self.cancel();
        });
    },

    render: function() {
        this.open(this.template, this.model);
    }
});