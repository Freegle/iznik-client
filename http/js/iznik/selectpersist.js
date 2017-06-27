(function ( $ ) {
    var id;

    $.fn.selectPersist = function(action, value) {
        var self = this;
        this.id = $(this).attr('id');

        if (!action) {
            // Find last value
            try {
                var val = Storage.get('selectPersist.' + this.id);
                console.log("Last select", this.id, val);

                if (val) {
                    this.selectpicker('val', val);
                }
            } catch (e) {
                // No matter; if local storage is not available then we can't persist.
                console.log("selectPersist", e);
            }

            // Add event listener to record changes and persist them.
            $(this).change(function () {
                var val = $(this).val();

                try {
                    Storage.set('selectPersist.' + self.id, val);
                } catch (e) {
                    console.log("selectPersist", e);
                }
            });
        } else if (action == 'set') {
            Storage.set('selectPersist.' + self.id, value);
        }

        return this;
    };

}( jQuery ));