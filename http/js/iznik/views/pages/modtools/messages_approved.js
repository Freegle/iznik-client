define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    "iznik/modtools",
    'iznik/views/pages/pages',
    "iznik/views/pages/modtools/messages",
    'iznik/views/infinite',
    'iznik/views/group/select'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.ModTools.Pages.ApprovedMessages = Iznik.Views.Infinite.extend({
        modtools: true,

        template: "modtools_messages_approved_main",

        retField: 'messages',

        events: {
            'click .js-searchmess': 'searchmess',
            'keyup .js-searchtermmess': 'keyupmess',
            'click .js-searchmemb': 'searchmemb',
            'keyup .js-searchtermmemb': 'keyupmemb'
        },

        countsChanged: function() {
            this.groupSelect.render();
        },

        keyupmess: function (e) {
            // Ensure we don't try to search on both criteria
            if (this.$('.js-searchtermmess').val().length > 0) {
                this.$('.js-searchtermmemb, .js-searchmemb').attr('disabled', 1);
            } else {
                this.$('.js-searchtermmemb, .js-searchmemb').removeAttr('disabled');
            }

            // Search on enter.
            if (e.which == 13) {
                this.$('.js-searchmess').click();
            }
        },

        searchmess: function () {
            var term = this.$('.js-searchtermmess').val();

            if (term != '') {
                Router.navigate('/modtools/messages/approved/messagesearch/' + encodeURIComponent(term), true);
            } else {
                Router.navigate('/modtools/messages/approved', true);
            }
        },

        keyupmemb: function (e) {
            // Ensure we don't try to search on both criteria
            if (this.$('.js-searchtermmemb').val().length > 0) {
                this.$('.js-searchtermmess, .js-searchmess').attr('disabled', 1);
            } else {
                this.$('.js-searchtermmess, .js-searchmess').removeAttr('disabled');
            }

            // Search on enter.
            if (e.which == 13) {
                this.$('.js-searchmemb').click();
            }
        },

        searchmemb: function () {
            var term = this.$('.js-searchtermmemb').val();

            if (term != '') {
                Router.navigate('/modtools/messages/approved/membersearch/' + encodeURIComponent(term), true);
            } else {
                Router.navigate('/modtools/messages/approved', true);
            }
        },

        render: function () {
            var p = Iznik.Views.Infinite.prototype.render.call(this);
            p.then(function(self) {
                // The type of collection we're using depends on whether we're searching.  It controls how we fetch.
                if (self.options.searchmess) {
                    self.collection = new Iznik.Collections.Messages.Search(null, {
                        modtools: true,
                        searchmess: self.options.searchmess,
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        collection: 'Approved'
                    });

                    self.$('.js-searchtermmess').val(self.options.searchmess);
                    self.$('.js-searchtermmemb, .js-searchmemb').attr('disabled', 1);
                } else if (self.options.searchmemb) {
                    self.collection = new Iznik.Collections.Messages.Search(null, {
                        modtools: true,
                        searchmemb: self.options.searchmemb,
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        collection: 'Approved'
                    });

                    self.$('.js-searchtermmemb').val(self.options.searchmemb);
                    self.$('.js-searchtermmess, .js-searchmess').attr('disabled', 1);
                } else {
                    self.collection = new Iznik.Collections.Message(null, {
                        modtools: true,
                        groupid: self.selected,
                        group: Iznik.Session.get('groups').get(self.selected),
                        collection: 'Approved'
                    });
                }

                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: true,
                    mod: true,
                    counts: ['approved', 'approvedother'],
                    id: 'approvedGroupSelect'
                });

                // CollectionView handles adding/removing/sorting for us.
                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.ModTools.Message.Approved,
                    modelViewOptions: {
                        collection: self.collection,
                        page: self
                    },
                    collection: self.collection,
                    processKeyEvents: false
                });

                self.collectionView.render();

                self.listenTo(self.groupSelect, 'selected', function (selected) {
                    // Change the group selected.
                    self.selected = selected;

                    // We haven't fetched anything for this group yet.
                    self.lastFetched = null;
                    self.context = null;
                    
                    self.fetch({
                        groupid: self.selected > 0 ? self.selected : null
                    });
                });

                // Render after the listen to as they are called during render.
                self.groupSelect.render().then(function(v) {
                    self.$('.js-groupselect').html(v.el);
                });

                // If we detect that the pending counts have changed on the server, refetch the messages so that we add/remove
                // appropriately.  Re-rendering the select will trigger a selected event which will re-fetch and render.
                self.listenTo(Iznik.Session, 'approvedcountschanged', _.bind(self.countsChanged, self));
                self.listenTo(Iznik.Session, 'approvedothercountschanged', _.bind(self.countsChanged, self));
            });
            
            return(p);
        }
    });

    Iznik.Views.ModTools.Message.Approved = Iznik.Views.ModTools.Message.extend({
        tagName: 'li',

        template: 'modtools_messages_approved_message',

        collectionType: 'Approved',

        events: {
            'click .js-viewsource': 'viewSource',
            'click .js-excludelocation': 'excludeLocation',
            'click .js-rarelyused': 'rarelyUsed',
            'click .js-editnotstd': 'editNotStd',
            'click .js-spam': 'spam'
        },

        editNotStd: function () {
            var self = this;

            var v = new Iznik.Views.ModTools.StdMessage.Edit({
                model: this.model
            });

            this.listenToOnce(this.model, 'editsucceeded', function () {
                self.model.fetch().then(function() {
                    self.render();
                });
            });

            v.render();
        },

        rendering: null,

        render: function () {
            var self = this;
            self.model.set('mapicon', window.location.protocol + '//' + window.location.hostname + '/images/mapmarker.gif');

            // Get a zoom level for the map.
            _.each(self.model.get('groups'), function (group) {
                self.model.set('mapzoom', group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 12);
            });

            if (!self.rendering) {
                self.rendering = new Promise(function(resolve, reject) {
                    var p = Iznik.Views.ModTools.Message.prototype.render.call(self);
                    p.then(function(self) {
                        // We handle the subject as a special case rather than a template expansion.  We might be doing a search, in
                        // which case we want to highlight the matched words.  So we split out the subject string into a sequence of
                        // spans, which then allows us to highlight any matched ones.
                        self.$('.js-subject').html(self.wordify(self.model.get('subject')));
                        var matched = self.model.get('matchedon');
                        if (matched) {
                            self.$('.js-subject span').each(function () {
                                if ($(this).html().toLowerCase().indexOf(matched.word) != -1) {
                                    $(this).addClass('searchmatch');
                                }
                            });
                        }

                        self.$('.js-grouplist').empty();

                        _.each(self.model.get('groups'), function (group) {
                            var mod = new Iznik.Model(group);

                            if (!group.onhere) {
                                // Native group - can edit.
                                self.$('.js-editcontainer').show();
                            }

                            // Add in the message, because we need some values from that
                            mod.set('message', self.model.toJSON());

                            var v = new Iznik.Views.ModTools.Message.Approved.Group({
                                model: mod
                            });
                            v.render();
                            self.$('.js-grouplist').append(v.el);

                            mod = new Iznik.Models.ModTools.User(self.model.get('fromuser'));
                            mod.set('groupid', group.id);

                            v = new Iznik.Views.ModTools.User({
                                model: mod
                            });

                            v.render();
                            self.$('.js-user').html(v.el);

                            if (group.onyahoo) {
                                // The Yahoo part of the user
                                mod = IznikYahooUsers.findUser({
                                    email: self.model.get('envelopefrom') ? self.model.get('envelopefrom') : self.model.get('fromaddr'),
                                    group: group.nameshort,
                                    groupid: group.id
                                });

                                mod.fetch().then(function () {
                                    var v = new Iznik.Views.ModTools.Yahoo.User({
                                        model: mod
                                    });

                                    v.render();
                                    self.$('.js-yahoo').html(v.el);
                                });
                            }

                            if (group.type == 'Freegle') {
                                // The FD settings.
                                var v = new Iznik.Views.ModTools.User.FreegleMembership({
                                    model: new Iznik.Model(self.model.get('fromuser')),
                                    groupid: group.id
                                });

                                v.render();
                                self.$('.js-freegleinfo').append(v.el);
                            }

                            // Add the default standard actions.
                            var configs = Iznik.Session.get('configs');
                            var sessgroup = Iznik.Session.get('groups').get(group.id);

                            if (sessgroup) {
                                var config = configs.get(sessgroup.get('configid'));

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Reply',
                                        action: 'Leave Approved Message',
                                        message: self.model,
                                        config: config
                                    })
                                }).render().then(function (v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Delete',
                                        action: 'Delete Approved Message',
                                        message: self.model,
                                        config: config
                                    })
                                }).render().then(function (v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                new Iznik.Views.ModTools.StdMessage.Button({
                                    model: new Iznik.Model({
                                        title: 'Spam',
                                        action: 'Spam',
                                        message: self.model
                                    })
                                }).render().then(function (v) {
                                    self.$('.js-stdmsgs').append(v.el);
                                });

                                if (config) {
                                    self.checkMessage(config);
                                    self.showRelated();

                                    // Add the other standard messages, in the order requested.
                                    var sortmsgs = orderedMessages(config.get('stdmsgs'), config.get('messageorder'));
                                    var anyrare = false;

                                    _.each(sortmsgs, function (stdmsg) {
                                        if (_.contains(['Leave Approved Message', 'Delete Approved Message'], stdmsg.action)) {
                                            stdmsg.message = self.model;
                                            var v = new Iznik.Views.ModTools.StdMessage.Button({
                                                model: new Iznik.Models.ModConfig.StdMessage(stdmsg),
                                                config: config
                                            });

                                            if (stdmsg.rarelyused) {
                                                anyrare = true;
                                            }

                                            v.render().then(function(v) {
                                                self.$('.js-stdmsgs').append(v.el);

                                                if (stdmsg.rarelyused) {
                                                    $(v.el).hide();
                                                }
                                            });
                                        }
                                    });

                                    if (!anyrare) {
                                        self.$('.js-rarelyholder').hide();
                                    }
                                }
                            }
                        });

                        self.$('.js-outcometime').timeago();

                        // Add any attachments.
                        self.$('.js-attlist').empty();
                        _.each(self.model.get('attachments'), function (att) {
                            var v = new Iznik.Views.ModTools.Message.Photo({
                                model: new Iznik.Model(att)
                            });

                            v.render();
                            self.$('.js-attlist').append(v.el);
                        });

                        self.addOtherInfo();

                        self.$('.timeago').timeago();

                        self.listenToOnce(self.model, 'deleted', function () {
                            self.$el.fadeOut('slow')
                        });
                    });

                    resolve();
                    self.rendering = null;
                });
            } else {
                self.rendering.then(function() {
                    self.rendering = null;
                    self.render({
                        model: self.model
                    });
                });
            }

            return (self.rendering);
        }
    });

    Iznik.Views.ModTools.Message.Approved.Group = Iznik.View.Timeago.extend({
        template: 'modtools_messages_approved_group'
    });
});