define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    'iznik/views/pages/pages',
    'iznik/views/pages/modtools/members_spam',
    'iznik/models/spammer'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.SpammerList = Iznik.Views.Page.extend({
        modtools: true,
        members: null,
        context: null,

        events: {
            'click .js-search': 'search',
            'keyup .js-searchterm': 'keyup'
        },

        template: "modtools_spammerlist_main",
        fetching: false,

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('.js-search').click();
            }
        },

        search: function () {
            var term = this.$('.js-searchterm').val();

            if (term != '') {
                Router.navigate('/modtools/spammerlist/' + this.options.urlfragment + '/' + encodeURIComponent(term), true);
            } else {
                Router.navigate('/modtools/spammerlist' + this.options.urlfragment, true);
            }
        },

        fetch: function () {
            var self = this;

            self.$('.js-none').hide();

            var search = self.$('.js-searchterm').val();

            var data = {
                context: self.context,
                search: search && search.length > 0 ? search : null,
                collection: self.options.collection
            };

            if (self.fetching) {
                // Already fetching
                return;
            }

            self.fetching = true;

            var v = new Iznik.Views.PleaseWait();
            v.render();

            this.spammers.fetch({
                data: data,
                remove: false
            }).then(function () {
                v.close();

                self.fetching = false;

                self.context = self.spammers.ret ? self.spammers.ret.context : null;

                if (self.spammers.length > 0) {
                    // Peek into the underlying response to see if it returned anything and therefore whether it is
                    // worth asking for more if we scroll that far.
                    var gotsome = self.spammers.ret.spammers.length > 0;

                    // Waypoints allow us to see when we have scrolled to the bottom.
                    if (self.lastWaypoint) {
                        self.lastWaypoint.destroy();
                    }

                    if (gotsome) {
                        // We got some different members, so set up a scroll handler.  If we didn't get any different
                        // members, then there's no point - we could keep hitting the server with more requests
                        // and not getting any.
                        var vm = self.collectionView.viewManager;
                        var lastView = vm.last();

                        if (lastView) {
                            self.lastMember = lastView;
                            self.lastWaypoint = new Waypoint({
                                element: lastView.el,
                                handler: function (direction) {
                                    if (direction == 'down') {
                                        // We have scrolled to the last view.  Fetch more as long as we've not switched
                                        // away to another page.
                                        if (jQuery.contains(document.documentElement, lastView.el)) {
                                            self.fetch();
                                        }
                                    }
                                },
                                offset: '99%' // Fire as soon as this view becomes visible
                            });
                        }
                    }
                } else {
                    self.$('.js-none').fadeIn('slow');
                }
            });
        },

        render: function () {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                self.$('.js-searchterm').val(self.options.search);

                var v = new Iznik.Views.Help.Box();
                v.template = self.options.helpTemplate;
                v.render().then(function(v) {
                    self.$('.js-help').html(v.el);
                })

                self.spammers = new Iznik.Collections.ModTools.Spammers();

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Spammer,
                    modelViewOptions: {
                        collection: self.spammers,
                        type: self.options.urlfragment,
                        page: self
                    },
                    collection: self.spammers,
                    processKeyEvents: false
                });

                self.collectionView.render();

                // Do so.
                self.fetch();

                // If we detect that the counts have changed on the server, refetch the members so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'spammerpendingaddcountschanged', self.fetch);
                self.listenTo(Iznik.Session, 'spammerpendingremovecountschanged', self.fetch);
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Spammer = Iznik.Views.ModTools.Member.Spam.extend({
        tagName: 'li',

        template: 'modtools_spammerlist_member',

        events: {
            'click .js-notspam': 'notSpam',
            'click .js-confirm': 'confirm',
            'click .js-whitelist': 'whitelist',
            'click .js-requestremove': 'requestRemove'
        },

        requestRemove: function() {
            var self = this;

            $.ajax({
                url: API + 'spammers/' + self.model.get('id'),
                data: {
                    'collection': 'PendingRemove'
                },
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                success: function (ret) {
                    // Now over to someone else to review this report - so remove from our list.
                    self.remove();
                }
            });
        },

        notSpam: function () {
            var self = this;

            $.ajax({
                url: API + 'spammers/' + self.model.get('id'),
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'DELETE'
                },
                success: function (ret) {
                    // Now over to someone else to review this report - so remove from our list.
                    self.remove();
                }
            });
        },

        confirm: function () {
            var self = this;

            $.ajax({
                url: API + 'spammers/' + self.model.get('id'),
                data: {
                    'collection': 'Spammer'
                },
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                success: function (ret) {
                    // Now over to someone else to review this report - so remove from our list.
                    self.remove();
                }
            });
        },

        render: function () {
            var self = this;

            if (!self.rendering) {
                self.rendering = new Promise(function (resolve, reject) {
                    self.model.set('type', self.options.type);
                    var p = Iznik.Views.ModTools.Member.Spam.prototype.render.call(self);
                    p.then(function () {
                        var user = self.model.get('user');
                        var usermod = new Iznik.Model(user);

                        if (Iznik.Session.isAdmin()) {
                            self.$('.js-adminonly').removeClass('hidden');
                        }

                        if (Iznik.Session.isAdminOrSupport()) {
                            self.$('.js-adminsupportonly').removeClass('hidden');
                        }

                        var mom = new moment(self.model.get('added'));
                        self.$('.js-spamadded').html(mom.format('ll'));

                        var v = new Iznik.Views.ModTools.User({
                            model: new Iznik.Models.ModTools.User(user)
                        });

                        v.render().then(function (v) {
                            self.$('.js-user').html(v.el);
                        });

                        // No point duplicating spammer info
                        self.$('.js-spammerinfo').hide();

                        // Add any other emails
                        self.$('.js-otheremails').empty();
                        var selfemail = user.email;
                        _.each(user.otheremails, function (email) {
                            if (email.email != selfemail) {
                                var mod = new Iznik.Model(email);
                                var v = new Iznik.Views.ModTools.Message.OtherEmail({
                                    model: mod
                                });
                                v.render().then(function (v) {
                                    self.$('.js-otheremails').append(v.el);
                                });
                            }
                        });

                        self.$('.js-memberof').empty();
                        _.each(user.memberof, function (group) {
                            var mod = new Iznik.Model(group);
                            var v = new Iznik.Views.ModTools.Member.Of({
                                model: mod,
                                user: usermod
                            });
                            v.render().then(function (v) {
                                self.$('.js-memberof').append(v.el);
                            });
                            if (group.type == 'Freegle') {
                                var v = new Iznik.Views.ModTools.User.FreegleMembership({
                                    model: mod
                                });

                                v.render().then(function (v) {
                                    self.$('.js-freegleinfo').append(v.el);
                                })
                            }
                        });

                        self.$('.js-applied').empty();
                        _.each(user.applied, function (group) {
                            var mod = new Iznik.Model(group);
                            var v = new Iznik.Views.ModTools.Member.Applied({
                                model: mod
                            });
                            v.render().then(function (v) {
                                self.$('.js-applied').append(v.el);
                            });
                        });

                        self.$('.timeago').timeago();

                        self.listenToOnce(self.model, 'deleted removed', function () {
                            self.$el.fadeOut('slow');
                        });

                        resolve();
                    });
                });
            }

            return (self.rendering);
        }
    });
});
