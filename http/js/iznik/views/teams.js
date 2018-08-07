require('animate.css');

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
                if (!self.options.hidecontrols && Iznik.Session.hasPermission('Teams')) {
                    self.$('.js-delete').show();
                }
            });

            return(p);
        }
    })

    Iznik.Views.User.BoardMember = Iznik.Views.Team.Member.extend({
        template: 'teams_boardmember',
        tagName: 'div'
    });

    Iznik.Views.User.Volunteer = Iznik.Views.Team.Member.extend({
        template: 'teams_volunteer',
    });

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
        template: 'teams_teamspage',

        events: {
            'click .js-addteam': 'add'
        },

        add: function() {
            var self = this;

            var name = self.$('.js-teamname').val();
            var email = self.$('.js-teamemail').val();
            var desc = self.$('.js-teamdesc').val();
            var c = new Iznik.Collections.Team();

            self.listenTo(c, 'add', _.bind(self.render, self));

            c.create({
                name: name,
                email: email,
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

                        if (team.get('type') == 'Team') {
                            self.$('.js-teamlist').append(v.$el);
                        } else {
                            self.$('.js-rolelist').append(v.$el);
                        }
                    });
                });

                if (Iznik.Session.hasPermission('Teams')) {
                    self.$('.js-teamadmin').show();
                }
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Volunteers = Iznik.Views.Page.extend({
        template: 'teams_volunteerspage',

        modtools: false,

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function() {
                var v = new Iznik.Views.User.Volunteers();
                v.render();
                self.$('.js-volunteers').html(v.$el);
            });

            return(p);
        }
    });

    Iznik.Views.User.Volunteers = Iznik.View.extend({
        template: 'teams_volunteers',

        events: {
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);

            p.then(function() {
                self.board = new Iznik.Models.Team();
                self.board.fetch({
                    data: {
                        name: 'Board'
                    }
                }).then(function() {
                    self.$('.js-board').empty();
                    _.each(self.board.get('members'), function(member) {
                        var v = new Iznik.Views.User.Volunteer({
                            model: new Iznik.Model(member),
                            hidecontrols: true
                        });

                        v.render();

                        self.$('.js-board').append(v.$el);
                    });
                });

                self.volunteers = new Iznik.Models.Team();
                self.volunteers.fetch({
                    data: {
                        name: 'Volunteers'
                    }
                }).then(function() {
                    self.$('.js-volunteerlist').empty();
                    _.each(self.volunteers.get('members'), function(member) {
                        var v = new Iznik.Views.User.Volunteer({
                            model: new Iznik.Model(member),
                            hidecontrols: true
                        });

                        v.render();

                        self.$('.js-volunteerlist').append(v.$el);
                    });
                });
            });

            return(p);
        }
    })

    Iznik.Views.User.Pages.Board = Iznik.Views.Page.extend({
        template: 'teams_boardpage',

        events: {
        },

        modtools: false,

        showDetails: function(team) {
            var self = this;
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function() {
                self.board = new Iznik.Models.Team();
                self.board.fetch({
                    data: {
                        name: 'Board'
                    }
                }).then(function() {
                    self.$('.js-board').empty();
                    _.each(self.board.get('members'), function(member) {
                        var v = new Iznik.Views.User.BoardMember({
                            model: new Iznik.Model(member),
                            hidecontrols: true
                        });

                        v.render();

                        self.listenTo(v, 'clicked', function() {
                            self.showDetails(member)
                        });

                        self.$('.js-board').append(v.$el);
                    });
                });
            });

            return(p);
        }
    });
});