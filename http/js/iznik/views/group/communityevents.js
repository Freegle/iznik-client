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

        events: {
            'click .js-info': 'info'
        },

        info: function() {
            var v = new Iznik.Views.User.CommunityEvent.Details({
                model: this.model
            });
            v.render();
        },

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

    Iznik.Views.User.CommunityEvent.Details  = Iznik.Views.Modal.extend({
        template: "communityevents_details",

        events: {
            'click .js-delete': 'deleteMe'
        },

        deleteMe: function() {
            var self = this;
            console.log("Delete event");
            this.model.destroy({
                success: function() {
                    self.close();
                }
            });
        },

        render: function() {
            var self = this;
            Iznik.Views.Modal.prototype.render.call(this).then(function() {
                self.$('.js-dates').empty();
                _.each(self.model.get('dates'), function(date) {
                    var start = (new moment(date.start)).format('ddd, Do MMM YYYY HH:mm');
                    var end = (new moment(date.end)).format('ddd, Do MMM YYYY HH:mm');
                    self.$('.js-dates').append(start + ' - ' + end + '<br />');
                });
                console.log("Events", self.events);
            })
        }
    });    
});