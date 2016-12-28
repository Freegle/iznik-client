(function ( $ ) {
    var id;

    $.fn.accordionPersist = function() {
        var self = this;
        this.id = $(this).attr('id');

        // Re-open last one
        try {
            var id = Storage.get('accordionPersist.' + this.id);

            if (id) {
                // Doesn't seem to be in the DOM for a while, so use a timeout.
                window.setTimeout(function() {
                    $(self).find('#' + self.id + ' .panel-collapse').removeClass('in');
                    $(self).find(id).addClass("in");
                }, 1000);
            }
        } catch (e) {
            // No matter; if local storage is not available then we can't persist.
            console.log("accordionPersist", e);
        }

        // Add event listener to record changes and persist them.
        var el = $('#' + self.id);
        el.on('shown.bs.collapse', function() {
            var id = el.attr('id');

            try {
                Storage.set('accordionPersist.' + self.id, '#' + id);
            } catch (e) {
                console.log("accordionPersist", e);
            }
        });

        el.on('hidden.bs.collapse', function() {
            try {
                Storage.remove('accordionPersist.' + self.id);
            } catch (e) {
                console.log("accordionPersist", e);
            }
        });
        return this;
    };

}( jQuery ));