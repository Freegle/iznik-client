define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Home = Iznik.Views.Page.extend({
        template: "user_home_main",

        render: function() {
            var self = this;

            Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            });

            self.messages = new Iznik.Collections.Message(null, {
                collection: 'Approved'
            });

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-offers'),
                modelView: Iznik.Views.User.Home.Offer,
                modelViewOptions: {
                    collection: self.messages,
                    page: self
                },
                collection: self.messages
            });

            self.collectionView.render();
            self.messages.fetch();

            return(this);
        }
    });
    
    Iznik.Views.User.Home.Offer = Iznik.View.extend({
        template: "user_home_offer"
    });
});