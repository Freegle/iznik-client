(function ( $ ) {
    var id;

    $.fn.selectPersist = function() {
        var self = this;
        this.id = $(this).attr('id');

        // Find last value
        try {
            var val = localStorage.getItem('selectPersist.' + this.id);

            if (val) {
                this.selectpicker('val', val);
            }
        } catch (e) {
            // No matter; if local storage is not available then we can't persist.
            console.log("selectPersist", e);
        }

        // Add event listener to record changes and persist them.
        $(this).change(function() {
            var val = $(this).val();

            try {
                localStorage.setItem('selectPersist.' + self.id, val);
            } catch (e) {
                console.log("selectPersist", e);
            }
        });

        return this;
    };

}( jQuery ));