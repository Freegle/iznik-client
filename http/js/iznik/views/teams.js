define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'iznik/models/teams',
    'iznik/views/pages/pages',
], function ($, _, Backbone, moment, Iznik) {
    Iznik.Views.Team = Iznik.View.extend({
        template: 'teams_team',

        events: {
            'click .js-addmember': 'add'
        },

        add: function() {
            var self = this;
            var userid = self.$('.js-userid').val();

            self.model.add(userid).then(_.bind(self.render, self));
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
                console.log()
                if (Iznik.Session.hasPermission('Teams')) {
                    self.$('.js-teamadmin').show();
                }

                // Get the members
                self.team = new Iznik.Models.Team({
                    id: self.model.get('id')
                });

                self.team.fetch().then(function() {
                    self.$('.js-memberlist').empty();

                    _.each(self.team.get('members'), function(member) {
                        var v = new Iznik.Views.Team.Member({
                            model: new Iznik.Model(member),
                            team: self.team
                        });

                        v.render();
                        self.$('.js-memberlist').append(v.$el);
                    });
                });
            })

            return(p);
        }
    });

    Iznik.Views.Team.Member = Iznik.View.extend({
        template: 'teams_member',
        tagName: 'li',
        events: {
            'click .js-delete': 'deleteMe'
        },

        deleteMe: function() {
            var self = this;

            self.options.team.remove(self.model.get('id'));
            self.$el.fadeOut('slow');
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
                if (Iznik.Session.hasPermission('Teams')) {
                    self.$('.js-delete').show();
                }
            });

            return(p);
        }
    })

    Iznik.Views.Team.Button = Iznik.View.extend({
        template: 'teams_button',

        tagName: 'li',

        events: {
            'click': 'details'
        },

        details: function() {
            this.trigger('clicked');
        }
    })

    Iznik.Views.ModTools.Pages.Teams = Iznik.Views.Page.extend({
        template: 'teams_main',

        events: {
            'click .js-addteam': 'add'
        },

        add: function() {
            var self = this;

            var name = self.$('.js-teamname').val();
            var desc = self.$('.js-teamdesc').val();
            var c = new Iznik.Collections.Team();

            self.listenTo(c, 'add', _.bind(self.render, self));

            c.create({
                name: name,
                description: desc
            }, {
                wait: true
            });
        },

        modtools: true,

        showDetails: function(team) {
            var self = this;

            var v = new Iznik.Views.Team({
                model: team
            })

            v.render();
            self.$('.js-teamdetails').html(v.$el);
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function() {
                self.teams = new Iznik.Collections.Team();
                self.teams.fetch().then(function() {
                    self.$('.js-teamlist').empty();
                    self.teams.each(function(team) {
                        var v = new Iznik.Views.Team.Button({
                            model: team
                        });

                        v.render();

                        self.listenTo(v, 'clicked', function() {
                            self.showDetails(team)
                        });

                        self.$('.js-teamlist').append(v.$el);
                    });
                });

                if (Iznik.Session.hasPermission('Teams')) {
                    self.$('.js-teamadmin').show();
                }
            });

            return(p);
        }
    });
});