define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/models/communityevent'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.CommunityEvents = Iznik.View.extend({
        template: "communityevents_main",

        containerHeight: function() {
            $('#js-eventcontainer').css('height', window.innerHeight - $('#botleft').height() - $('nav').height() - 50)
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.events = new Iznik.Collections.CommunityEvent();

                self.eventsView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.CommunityEvent,
                    collection: self.events
                });

                self.eventsView.render();

                self.events.fetch().then(function() {
                    self.$('.js-list').fadeIn('slow');
                    if (self.events.length == 0) {
                        self.$('.js-none').fadeIn('slow');
                    }

                    self.containerHeight();
                    $(window).resize(self.containerHeight);
                    $('#js-eventcontainer').fadeIn('slow');
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.CommunityEvent  = Iznik.View.extend({
        template: "communityevents_one",
        className: 'padleftsm',

        render: function() {
            var self = this;
            Iznik.View.prototype.render.call(this).then(function() {
                console.log("Add completefull", self.$el);
                var mom = new moment(self.model.get('dates')[0]['start']);
                self.$('.js-start').html(mom.format('ddd, Do MMM HH:mm'));
                self.$el.closest('li').addClass('completefull');
            })
        }
    });
});