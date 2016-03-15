Iznik.Views.User.Pages.WhereAmI = Iznik.Views.Page.extend({
    events: function(){
        return _.extend({}, Iznik.Views.Page.prototype.events,{
            'click .js-getloc': 'getLocation'
        });
    },

    getLocation: function() {
        navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
    },

    recordLocation: function(location) {
        var self = this;

        self.$('.js-postcode').val(location.name);
        self.$('.js-next').fadeIn('slow');
        self.$('.js-ok').fadeIn('slow');

        try {
            localStorage.setItem('mylocation', JSON.stringify(location))
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
                    self.recordLocation(ret.location);
                }
            }
        })
    },

    postcodeSource: function(query, syncResults, asyncResults) {
        var self = this;

        $.ajax({
            type: 'GET',
            url: API + 'locations',
            data: {
                typeahead: query
            }, success: function(ret) {
                var matches = [];
                _.each(ret.locations, function(location) {
                    matches.push(location.name);
                })

                asyncResults(matches);
            }
        })
    },

    render: function() {
        Iznik.Views.Page.prototype.render.call(this);
        console.log("Whereami events", this.events(), this.home);

        if (!navigator.geolocation) {
            this.$('.js-geoloconly').hide();
        }

        try {
            // See if we know where we are from last time.
            var mylocation = localStorage.getItem('mylocation');

            if (mylocation) {
                this.recordLocation(JSON.parse(mylocation));
            }
        } catch (e) {};

        this.$('.js-postcode').typeahead({
            minLength: 2,
            hint: false,
            highlight: true
        }, {
            name: 'postcodes',
            source: this.postcodeSource
        });

        return(this);
    }
});