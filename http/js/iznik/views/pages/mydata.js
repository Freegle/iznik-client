define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'renderjson',
    'file-saver',
    'jquery-show-first',
    'iznik/views/pages/pages',
    'iznik/views/chat/chat',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
], function ($, _, Backbone, Iznik, moment, renderjson, s) {
    var saveAs = s.saveAs;

    Iznik.Views.MyData = Iznik.Views.Page.extend({
        template: 'mydata_main',

        modtools: MODTOOLS,

        noGoogleAds: true,

        events: {
            'click .js-export': 'export'
        },

        export: function() {
            var self = this;
            var blob = new Blob([JSON.stringify(self.json)], {type: "application/json;charset=utf-8"});
            saveAs(blob, "mydata.json");
        },

        waitExport: function() {
            var self = this;

            $.ajax({
                url: API + 'export',
                data: {
                    id: self.exportId,
                    tag: self.exportTag
                },
                timeout: 0,
                success: function(ret) {
                    if (ret.ret === 0 && ret.export && ret.export.data) {
                        console.log("Exported", ret.export);
                        self.json = ret.export.data;

                        var user = new Iznik.Model(ret.export.data);
                        self.model = user;

                        var p = Iznik.Views.Page.prototype.render.call(self);

                        p.then(function() {
                            if (Iznik.Session.isFreegleMod()) {
                                self.$('.js-modonly').show();
                            } else {
                                self.$('.js-modonly').hide();
                            }

                            self.$('.js-date').each((function() {
                                var m = new moment($(this).html().trim());
                                $(this).html(m.format('MMMM Do YYYY, h:mm:ss a'));
                            }));

                            _.each(self.model.get('invitations'), function(invite) {
                                var m = new moment(invite.date);
                                invite.date = m.format('MMMM Do YYYY, h:mm:ss a');
                                var v = new Iznik.Views.MyData.Invitation({
                                    model: new Iznik.Model(invite)
                                });
                                v.render();
                                self.$('.js-invitations').append(v.$el);
                            });

                            _.each(self.model.get('emails'), function(email) {
                                // No need to show emails which are our own domains.  The user didn't
                                // provide them.
                                if (!email.ourdomain) {
                                    var m = new moment(email.added);
                                    email.added = m.format('MMMM Do YYYY, h:mm:ss a');

                                    if (email.validated) {
                                        var m = new moment(email.validated);
                                        email.validated= m.format('MMMM Do YYYY, h:mm:ss a');
                                    }

                                    var v = new Iznik.Views.MyData.Email({
                                        model: new Iznik.Model(email)
                                    });
                                    v.render();

                                    self.$('.js-emails').append(v.$el);
                                }
                            });

                            _.each([
                                [ 'memberships', Iznik.Views.MyData.Membership, '.js-memberships' ],
                                [ 'membershipshistory', Iznik.Views.MyData.MembershipHistory, '.js-membershipshistory' ],
                                [ 'searches', Iznik.Views.MyData.Search, '.js-searches' ],
                                [ 'alerts', Iznik.Views.MyData.Alert, '.js-alerts' ],
                                [ 'donations', Iznik.Views.MyData.Donation, '.js-donations' ],
                                [ 'bans', Iznik.Views.MyData.Ban, '.js-bans' ],
                                [ 'spammers', Iznik.Views.MyData.Spammer, '.js-spammers' ],
                                [ 'spamdomains', Iznik.Views.MyData.SpamDomain, '.js-spamdomains' ],
                                [ 'images', Iznik.Views.MyData.Image, '.js-images' ],
                                [ 'notifications', Iznik.Views.MyData.Notification, '.js-notifications' ],
                                [ 'addresses', Iznik.Views.MyData.Address, '.js-addresses' ],
                                [ 'communityevents', Iznik.Views.MyData.CommunityEvent, '.js-communityevents' ],
                                [ 'volunteering', Iznik.Views.MyData.Volunteering, '.js-volunteerings' ],
                                [ 'comments', Iznik.Views.MyData.Comment, '.js-comments' ],
                                [ 'locations', Iznik.Views.MyData.Location, '.js-locations' ],
                                [ 'messages', Iznik.Views.MyData.Message, '.js-messages' ],
                                [ 'chatrooms', Iznik.Views.MyData.ChatRoom, '.js-chatrooms' ],
                            ], function(view) {
                                _.each(self.model.get(view[0]), function(mod) {
                                    var v = new view[1]({
                                        model: new Iznik.Model(mod)
                                    });
                                    v.render();

                                    self.$(view[2]).append(v.$el);
                                });
                            });

                            self.$('.js-more').each(function() {
                                $(this).showFirst({
                                    controlTemplate: '<div><span class="badge">+[REST_COUNT] more</span>&nbsp;<a href="#" class="show-first-control">show</a></div>',
                                    count: 5
                                });
                            });

                            self.wait.close();
                        });
                    } else {
                        _.delay(_.bind(self.waitExport, self), 30000);
                    }
                }
            });
        },

        render: function() {
            var self = this;

            self.wait = new Iznik.Views.PleaseWait({
                label: 'chat openChat'
            });
            self.wait.template = 'mydata_wait';
            self.wait.closeAfter = 600000;
            self.wait.render();

            $.ajax({
                url: API + 'export',
                type: 'POST',
                success: function(ret) {
                    if (ret.ret == 0) {
                        self.exportId = ret.id;
                        self.exportTag = ret.tag;

                        _.delay(_.bind(self.waitExport, self), 30000);
                    }
                }
            });

            return(Iznik.resolvedPromise(self));
        }
    });

    Iznik.Views.MyData.Invitation = Iznik.View.extend({
        template: 'mydata_invitation'
    });

    Iznik.Views.MyData.Email = Iznik.View.extend({
        template: 'mydata_email'
    });

    Iznik.Views.MyData.Membership = Iznik.View.extend({
        template: 'mydata_membership',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var freq = self.model.get('mysettings').emailfrequency;
                self.$('.js-emailfrequency option[value=' + freq + ']').prop('selected', true);
            });

            return(p);
        }
    });

    Iznik.Views.MyData.MembershipHistory = Iznik.View.extend({
        template: 'mydata_membershiphistory',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Search = Iznik.View.extend({
        template: 'mydata_search',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Alert = Iznik.View.extend({
        template: 'mydata_alert',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('responded'));
                self.$('.js-responded').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Donation = Iznik.View.extend({
        template: 'mydata_donation',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('timestamp'));
                self.$('.js-timestamp').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Ban = Iznik.View.extend({
        template: 'mydata_ban',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Spammer = Iznik.View.extend({
        template: 'mydata_spammer',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Image = Iznik.View.extend({
        template: 'mydata_image',

        className: 'inline'
    });

    Iznik.Views.MyData.Notification = Iznik.View.extend({
        template: 'mydata_notification',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('timestamp'));
                self.$('.js-timestamp').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Address = Iznik.View.extend({
        template: 'mydata_address',
    });

    Iznik.Views.MyData.CommunityEvent = Iznik.View.extend({
        template: 'mydata_communityevent',

        events: {
            'click .js-details': 'details'
        },

        details: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var m = new Iznik.Models.CommunityEvent({
                id: this.model.get('id')
            });

            m.fetch().then(function() {
                var v = new Iznik.Views.User.CommunityEvent.Details({
                    model: self.model
                });

                v.render();
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Volunteering = Iznik.View.extend({
        template: 'mydata_volunteering',

        events: {
            'click .js-details': 'details'
        },

        details: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var m = new Iznik.Models.Volunteering({
                id: this.model.get('id')
            });

            m.fetch().then(function() {
                var v = new Iznik.Views.User.Volunteering.Details({
                    model: self.model
                });

                v.render();
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('added'));
                self.$('.js-added').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Comment = Iznik.View.extend({
        template: 'mydata_comment',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Location = Iznik.View.extend({
        template: 'mydata_location',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.SpamDomain = Iznik.View.extend({
        template: 'mydata_spamdomain',

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.Message = Iznik.View.extend({
        template: 'mydata_message',

        events: {
            'click .js-showdetails': 'details',
            'click .disclosure': 'disclosure'
        },

        disclosure: function(e) {
            // These use href=# so would trigger routing.  Suppress as we expand all anyway.
            e.preventDefault();
            e.stopPropagation();
        },

        details: function() {
            var self = this;

            // Expand all to avoid dull stuff with href=#.
            self.wait = new Iznik.Views.PleaseWait({
                timeout: 1
            });
            self.wait.render();

            _.delay(function() {
                self.$('.js-details').html(renderjson.set_show_to_level('all')(self.model.attributes));
                self.wait.close();
                self.$('.js-detailsrow').show();
            }, 10);
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('arrival'));
                self.$('.js-date').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });

    Iznik.Views.MyData.ChatRoom = Iznik.View.extend({
        template: 'mydata_chatroom',

        events: {
            'click .js-header': 'expand'
        },

        expanded: false,

        expand: function() {
            var self = this;

            if (self.expanded) {
                self.$('.js-chatmessages').slideUp('slow');
            } else {
                var messages = self.model.get('messages');
                _.each(messages, function(message) {
                    // We want to reuse the chat view so set up appropriately.
                    message.user = Iznik.Session.get('me').attributes;

                    var v = new Iznik.Views.Chat.Message({
                        model: new Iznik.Model(message),
                        chatModel: self.model
                    });
                    v.render();
                    self.$('.js-chatmessages').append(v.$el);
                });
            }
        },

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                var m = new moment(self.model.get('date'));
                self.$('.js-chatdate').html(m.format('MMMM Do YYYY, h:mm:ss a'));
            });

            return(p);
        }
    });
});