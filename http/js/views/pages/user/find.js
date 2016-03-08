Iznik.Views.User.Pages.Find.WhereAmI = Iznik.Views.Page.extend({
    template: "user_find_whereami",

    events: {
        'click .js-getloc': 'getLocation'
    },

    getLocation: function() {
        navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
    },

    recordLocation: function(location) {
        var self = this;

        self.$('.js-postcode').val(location);
        self.$('.js-next').fadeIn('slow');
        self.$('.js-ok').fadeIn('slow');

        try {
            localStorage.setItem('mylocation', location);
        } catch (e) {};
    },

    gotLocation: function(position) {
        var self = this;

        $.ajax({
            type: 'GET',
            url: API + 'locations',
            data: {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            }, success: function(ret) {
                if (ret.ret == 0 && ret.location) {
                    self.recordLocation(ret.location.name);
                }
            }
        })
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);

        if (!navigator.geolocation) {
            this.$('.js-geoloconly').hide();
        }

        try {
            // See if we know where we are from last time.
            var mylocation = localStorage.getItem('mylocation');

            if (mylocation) {
                this.recordLocation(mylocation);
            }
        } catch (e) {};

        return(this);
    }
});