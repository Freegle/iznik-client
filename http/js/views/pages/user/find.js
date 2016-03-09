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

Iznik.Views.User.Pages.Find.Search = Iznik.Views.Page.extend({
    template: "user_find_search",

    events: {
        'click #searchbutton': 'doSearch',
        'keyup .js-search': 'keyup'
    },

    keyup: function(e) {
        // Search on enter.
        if (e.which == 13) {
            this.$('#searchbutton').click();
        }
    },

    doSearch: function () {
        var term = this.$('.js-search').val();

        if (term != '') {
            Router.navigate('/user/find/search/' + encodeURIComponent(term), true);
        } else {
            Router.navigate('/user/find/search', true);
        }
    },

    itemSource: function(query, syncResults, asyncResults) {
        var self = this;

        $.ajax({
            type: 'GET',
            url: API + 'item',
            data: {
                typeahead: query
            }, success: function(ret) {
                var matches = [];
                _.each(ret.items, function(item) {
                    matches.push(item.item.name);
                })

                asyncResults(matches);
            }
        })
    },

    render: function() {
        var self = this;

        Iznik.Views.Page.prototype.render.call(this);

        if (this.options.search) {
            this.$('.js-search').val(this.options.search);

            self.collection = new Iznik.Collections.Messages.Search(null, {
                searchmess: self.options.search,
                collection: 'Approved'
            });

            self.collectionView = new Backbone.CollectionView( {
                el : self.$('.js-list'),
                modelView : Iznik.Views.User.Message.SearchResult,
                modelViewOptions: {
                    collection: self.collection,
                    page: self
                },
                collection: self.collection
            } );

            self.collectionView.render();

            var v = new Iznik.Views.PleaseWait();
            v.render();

            var mylocation = null;
            try {
                mylocation = localStorage.getItem('mylocation');
            } catch (e) {}

            self.collection.fetch({
                remove: true,
                data: {
                    messagetype: 'Offer',
                    nearlocation: mylocation
                },
                success: function(collection, response, options) {
                    v.close();

                    if (collection.length == 0) {
                        self.$('.js-none').fadeIn('slow');
                    } else {
                        self.$('.js-none').hide();
                    }
                }
            });
        }

        this.$('.js-search').typeahead({
            minLength: 2,
            hint: false,
            highlight: true
        }, {
            name: 'items',
            source: this.itemSource
        });

        return(this);
    }
});

Iznik.Views.User.Message.SearchResult = IznikView.extend({
    template: 'user_find_result',

    render: function() {
        var self = this;

        self.$el.html(window.template(self.template)(self.model.toJSON2()));

        _.each(self.model.get('attachments'), function(att) {
            var v = new Iznik.Views.ModTools.Message.Photo({
                model: new IznikModel(att)
            });

            self.$('.js-attlist').append(v.render().el);
        });

        self.$('.timeago').timeago();
    }
})
