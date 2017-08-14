define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'iznik/facebook',
    'jquery.caret.min',
    'jquery.atwho.min',
    'jquery-show-last',
    'iznik/models/message',
    'iznik/models/user/search',
    'iznik/models/newsfeed',
    'iznik/views/group/communityevents',
    'iznik/views/group/volunteering',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/post',
    'iznik/views/infinite',
    'iznik/views/user/polls',
    'jquery.scrollTo'
], function($, _, Backbone, Iznik, autosize, FBLoad) {
    Iznik.Views.User.Feed = {};
    
    Iznik.Views.User.Pages.Newsfeed = Iznik.Views.Infinite.extend({
        template: "user_newsfeed_main",

        retField: 'newsfeed',

        events: {
            'click .js-post': 'post',
            'click .js-getloc': 'getLocation',
            'change .js-distance': 'changeDist',
            'click .js-tabpost': 'updateArea',
            'click .js-tabevent': 'addEventInline',
            'click .js-tabvolunteer': 'addVolunteerInline',
            'click .js-taboffer': 'inlineOffer',
            'click .js-tabwanted': 'inlineWanted',
            'focus #js-discussmessage': 'newsfeedHelp'
        },

        newsfeedHelp: function() {
            if (!Storage.get('newsfeedhelp')) {
                Storage.set('newsfeedhelp', true);
                var v = new Iznik.Views.User.Feed.Help();
                v.render();
            }
        },

        updateArea: function() {
            // The area might have changed through posting on another tab.
            var me = Iznik.Session.get('me');

            if (me.settings.mylocation && me.settings.mylocation.area) {
                this.$('.js-areaname').html(me.settings.mylocation.area.name);
            }
        },

        getLocation: function() {
            var self = this;
            self.wait = new Iznik.Views.PleaseWait();
            self.wait.render();

            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        gotLocation: function(position) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.$('.js-nolocation').fadeOut('slow');
                        self.recordLocation(ret.location);
                        self.refetch();
                    }
                }, complete: function() {
                    if (self.wait) {
                        self.wait.close();
                    }
                }
            });
        },

        recordLocation: function(location) {
            var self = this;

            if (!_.isUndefined(location)) {
                try {
                    var l = location;
                    delete l.groupsnear;
                    Iznik.Session.setSetting('mylocation', l);
                    Storage.set('mylocation', JSON.stringify(l))
                } catch (e) {
                    console.log("Exception", e.message);
                };

                self.trigger('gotlocation', location);
            }
        },

        shownFind: false,
        shownGive: false,

        checkMessage: function() {
            var self = this;

            if (self.inDOM()) {
                var msg = self.$('.js-message').val().toLowerCase();

                if (msg.length > 0) {
                    var checks = {
                        'find': [
                            'wanted',
                            'looking for',
                            'has anybody got',
                            'has anyone got',
                            'does anyone have',
                            'i really need',
                            'if anyone has'
                        ],
                        'give': [
                            'offer',
                            'giving away',
                            'does anyone want',
                            'collection from',
                            'collection only'
                        ]
                    };

                    if (!self.shownFind) {
                        var showfind = false;

                        _.each(checks.find, function(c) {
                            if (msg.indexOf(c) !== -1) {
                                showfind = true;
                            }
                        });

                        if (showfind) {
                            self.shownFind = true;
                            self.$('.nav-tabs a[href="#js-wantedsomething"]').tab('show');
                            self.$('.js-tabwanted').click();

                            self.$('.js-tabwanted').tooltip('show');
                            _.delay(_.bind(function() {
                                this.$('.js-tabwanted').tooltip('hide');
                            }, self), 10000);
                        }
                    }

                    if (!self.shownGive) {
                        var showgive = false;

                        _.each(checks.give, function(c) {
                            if (msg.indexOf(c) !== -1) {
                                showgive = true;
                            }
                        });

                        if (showgive) {
                            self.shownGive = true;
                            self.$('.nav-tabs a[href="#js-offersomething"]').tab('show');
                            self.$('.js-taboffer').click();

                            self.$('.js-taboffer').tooltip('show');
                            _.delay(_.bind(function() {
                                this.$('.js-taboffer').tooltip('hide');
                            }, self), 10000);
                        }
                    }
                }

                _.delay(_.bind(self.checkMessage, self), 1000);
            }
        },

        changeDist: function() {
            var self = this;
            var dist = self.$('.js-distance').val();
            Storage.set('newsfeeddist', dist);
            self.collection.reset();
            self.context = {
                'distance': dist
            };
            self.fetch();
        },

        refetch: function() {
            var self = this;
            var dist = self.$('.js-distance').val();
            self.context = {
                'distance': dist
            };
            self.fetch();
        },

        post: function() {
            var self = this;

            self.$('.js-message').prop('disabled', true);
            var msg = self.$('.js-message').val();
            msg = twemoji.replace(msg, function(emoji) {
                return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u';
            });

            if (msg) {
                var mod = new Iznik.Models.Newsfeed({
                    message: msg
                });

                mod.save().then(function() {
                    mod.fetch().then(function() {
                        self.collection.add(mod, {
                            at: 0
                        });
                        self.$('.js-message').val('');
                        self.$('.js-message').prop('disabled', false);
                    });
                });
            }
        },

        sidebars: function() {
            var self = this;

            // Left menu is community events
            var v = new Iznik.Views.User.CommunityEventsSidebar();
            v.render().then(function () {
                $('#js-eventcontainer').append(v.$el);
            });

            // Right menu is volunteer vacancies
            var w = new Iznik.Views.User.VolunteeringSidebar();
            w.render().then(function () {
                $('#js-volunteeringcontainer').append(w.$el);
            });
        },

        visible: function(model) {
            var vis = model.get('visible');
            return(vis);
        },

        addEventInline: function() {
            var self = this;

            var v = new Iznik.Views.User.Feed.CommunityEvent({
                model: new Iznik.Models.CommunityEvent({})
            });

            v.render();
            self.$('#js-addevent').html(v.$el);
        },

        addVolunteerInline: function() {
            var self = this;

            var v = new Iznik.Views.User.Feed.Volunteering({
                model: new Iznik.Models.Volunteering({})
            });

            v.render();
            self.$('#js-addvolunteer').html(v.$el);
        },

        inlineOffer: function() {
            var self = this;
            console.log("Inlne offer");
            var v = new Iznik.Views.User.Feed.InlineOffer();
            v.render();
            self.$('#js-offersomething').html(v.$el);
        },

        inlineWanted: function() {
            var self = this;

            var v = new Iznik.Views.User.Feed.InlineWanted();
            v.render();
            self.$('#js-wantedsomething').html(v.$el);
        },

        render: function () {
            var self = this;

            Storage.set('lasthomepage', 'news');

            var p = Iznik.Views.Infinite.prototype.render.call(this);

            p.then(function(self) {
                if (Iznik.Session.get('me').bouncing) {
                    self.$('.js-bouncing .js-email').html(Iznik.Session.get('me').email);
                    self.$('.js-bouncing').fadeIn('slow');
                }

                // Some options are only available once we've joined a group.
                if (Iznik.Session.get('groups').length > 0) {
                    self.$('.js-somegroups').show();
                }

                if (!self.autosized) {
                    self.autosized = true;
                    autosize(self.$('.js-message'));
                }

                if (!isXS() && !isSM()) {
                    self.$('.js-message').focus();
                }

                // Sticky select.
                var dist = Storage.get('newsfeeddist');
                dist = dist !== null ? dist : 'nearby';
                self.$('.js-distance').val(dist);

                self.context = {
                    'distance': self.$('.js-distance').val()
                };

                self.collection = new Iznik.Collections.Newsfeed();

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-feed'),
                    modelView: Iznik.Views.User.Feed.Item,
                    collection: self.collection,
                    visibleModelsFilter: _.bind(self.visible, self),
                    processKeyEvents: false
                });

                self.collectionView.render();
                self.fetch({
                    types: [
                        'Message',
                        'CommunityEvent',
                        'VolunteerOpportunity',
                        'CentralPublicity',
                        'Alert',
                        'Story'
                    ]
                });

                _.delay(_.bind(self.checkMessage, self), 1000);

                // We can be asked to refetch by the first news
                self.listenTo(self.collection, 'refetch', _.bind(self.refetch, self));

                // Delay load of sidebars to give the main feed chance to load first.
                _.delay(_.bind(self.sidebars, self), 10000);

                // Polls
                var poll = new Iznik.Views.User.Poll();
                poll.render().then(function() {
                    self.$('.js-poll').html(poll.$el);
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Pages.Newsfeed.Single = Iznik.Views.Page.extend({
        template: "user_newsfeed_single",

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function(self) {
                // We are loading something which may be the start of the thread, or a reply.  We want to load
                // the whole thread and focus on the reply.
                self.model = new Iznik.Models.Newsfeed({
                    id: self.options.id
                });

                self.model.fetch({
                    success: function() {
                        if (self.model.get('replyto')) {
                            // Notification is on a reply; render then make sure the reply is visible.
                            self.model = new Iznik.Models.Newsfeed({
                                id: self.model.get('replyto')
                            });

                            self.model.fetch({
                                success: function() {
                                    var v = new Iznik.Views.User.Feed.Item({
                                        model: self.model,
                                        highlight: self.options.id
                                    });

                                    v.render().then(function() {
                                        self.$('.js-item').html(v.$el);
                                        self.$('.js-back').fadeIn('slow');
                                    });
                                },
                                error: function() {
                                    self.$('.js-error').fadeIn('slow');
                                    self.$('.js-back').fadeIn('slow');
                                }
                            });
                        } else {
                            // Start of thread.
                            var v = new Iznik.Views.User.Feed.Item({
                                model: self.model
                            });

                            v.render().then(function() {
                                self.$('.js-item').html(v.$el);
                                self.$('.js-back').fadeIn('slow');
                            })
                        }
                    },
                    error: function() {
                        self.$('.js-error').fadeIn('slow');
                        self.$('.js-back').fadeIn('slow');
                    }
                })
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Base = Iznik.View.Timeago.extend({
        events: {
            'click .js-profile': 'showProfile',
            'click .js-delete': 'deleteMe',
            'click .js-open': 'open',
            'click .js-report': 'report',
            'click .js-follow': 'follow',
            'click .js-unfollow': 'unfollow',
            'click .js-refertowanted': 'referToWanted',
            'click .js-refertooffer': 'referToOffer',
            'click .js-refertotaken': 'referToTaken',
            'click .js-refertoreceived': 'referToReceived',
            'click .js-preview': 'clickPreview',
            'click .js-reply': 'reply',
            'click .js-edit': 'edit'
        },

        edit: function(e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.User.Feed.Edit({
                model: self.model
            });

            self.listenToOnce(v, 'modalClosed', function() {
                self.model.fetch().then(function() {
                    self.$('.js-message').html(_.escape(self.model.get('message')));
                    if (self.$('.js-message').length) {
                        twemoji.parse(self.$('.js-message').get()[0]);
                    }
                });
            });

            v.render();
        },

        open: function (e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            var usersite = $('meta[name=iznikusersite]').attr("content");
            var url = 'https://' + usersite + '/newsfeed/' + self.model.get('id');

            window.open(url);
        },

        follow: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.follow();
            self.$('a.dropdown-toggle').dropdown('toggle');
        },

        unfollow: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.unfollow();
            self.$('a.dropdown-toggle').dropdown('toggle');
        },

        referToWanted: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.referToWanted().then(function() {
                self.$('a.dropdown-toggle').dropdown('toggle');
                self.checkUpdate();
            });
        },

        referToOffer: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.referToOffer().then(function() {
                self.$('a.dropdown-toggle').dropdown('toggle');
                self.checkUpdate();
            });
        },

        referToTaken: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.referToTaken().then(function() {
                self.$('a.dropdown-toggle').dropdown('toggle');
                self.checkUpdate();
            });
        },

        referToReceived: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            self.model.referToReceived().then(function() {
                self.$('a.dropdown-toggle').dropdown('toggle');
                self.checkUpdate();
            });
        },

        clickPreview: function() {
            var p = this.model.get('preview');

            if (p && p.hasOwnProperty('url') && p.url) {
                window.open(p.url);
            }
        },

        report: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation()

            self.$('a.dropdown-toggle').dropdown('toggle');

            var v = new Iznik.Views.User.Feed.Report({
                model: self.model
            });

            self.listenToOnce(v, 'reported', function() {
                self.$el.fadeOut('slow');
            });

            v.render();
        },

        deleteMe: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation()

            self.$('a.dropdown-toggle').dropdown('toggle');
            self.model.destroy();
        },

        reply: function(user) {
            this.$('.js-comment').focus();

            if (user && user.hasOwnProperty('displayname')) {
                this.$('.js-comment').html('@' + user.displayname + ' ');
            }
        },

        showProfile: function() {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(self.model.get('user'))
                });

                v.render();
            });
        },

        mention: function(sel) {
            var self = this;

            // Allow use of @ to mention people.
            self.$(sel).atwho({
                at: "@",
                data: API + 'mentions?id=' + self.model.get('id'),
                callbacks: {
                    beforeSave: function(data) {
                        var ret = [];
                        _.each(data.mentions, function(d) {
                            ret.push({
                                id: d.id,
                                name: d.displayname
                            });
                        });

                        return(ret);
                    }
                }
            });
        },

        highlightMentions: function(msg) {
            var self = this;

            if (self.options.contributors) {
                for (var id in self.options.contributors) {
                    var name = self.options.contributors[id];
                    
                    var p = msg.indexOf('@' + name);

                    if (p !== -1) {
                        msg = msg.substring(0, p) + '<span style="color: blue">@' + name + '</span>' + msg.substring(p + name.length + 1);
                    }
                }
            }

            return(msg);
        },

        render: function() {
            var self = this;

            var msg = self.model.get('message');

            if (msg) {
                msg = twem(msg);
                self.model.set('message', msg);
            }

            var p = new Promise(function(resolve, reject) {
                var v = new Iznik.Views.User.Feed.Loves({
                    model: self.model
                });

                self.model.set('ismod', Iznik.Session.isFreegleMod());
                var user = self.model.get('user');
                self.model.set('ownpost', user && user.id == Iznik.Session.get('me').id);

                v.template = self.lovetemplate;
                v.render().then(function() {
                    Iznik.View.Timeago.prototype.render.call(self).then(function () {
                        self.$(self.lovesel).html(v.$el);
                        resolve();
                    });
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Loves = Iznik.View.extend({
        tagName: 'span',

        events: {
            'click .js-replylove': 'love',
            'click .js-itemlove': 'love',
            'click .js-replyunlove': 'unlove',
            'click .js-itemunlove': 'unlove',
            'click .js-loves': 'lovelist'
        },

        lovelist: function() {
            var self = this;
            if (self.model.get('loves')) {
                (new Iznik.Views.User.Feed.Loves.List({
                    model: self.model
                })).render();
            }
        },

        love: function() {
            var self = this;

            self.model.love().then(function() {
                self.model.fetch().then(function() {
                    self.render();
                });
            });
        },

        unlove: function() {
            var self = this;

            self.model.unlove().then(function() {
                self.model.fetch().then(function() {
                    self.render();
                });
            });
        }
    });

    Iznik.Views.User.Feed.Loves.List = Iznik.Views.Modal.extend({
        template: 'user_newsfeed_lovelist',

        render: function() {
            var self = this;

            var p = self.model.fetch({
                data: {
                    lovelist: true
                }
            });

            p.then(function() {
                Iznik.Views.Modal.prototype.render.call(self, {
                    model: self.model
                }).then(function() {
                    self.collection = new Iznik.Collection(self.model.get('lovelist'));

                    self.collectionView = new Backbone.CollectionView({
                        el: self.$('.js-list'),
                        modelView: Iznik.Views.User.Feed.Loves.List.One,
                        collection: self.collection,
                        processKeyEvents: false
                    });

                    self.collectionView.render();
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Edit = Iznik.Views.Modal.extend({
        template: 'user_newsfeed_edit',

        events: {
            'click .js-save': 'save'
        },

        save: function() {
            var self = this;

            self.model.save({
                id: self.model.get('id'),
                message: self.$('.js-message').val()
            }, {
                patch: true
            }).then(function() {
                self.close();
            });
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(self);

            p.then(function() {
                autosize(self.$('.js-message'));
                self.$('.js-message').val(self.model.get('message'));
                if (self.$('.js-message').length) {
                    twemoji.parse(self.$('.js-message').get()[0]);
                }
                autosize.update(self.$('.js-message'));
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Loves.List.One = Iznik.View.extend({
        template: 'user_newsfeed_onelove'
    });

    Iznik.Views.User.Feed.Item = Iznik.Views.User.Feed.Base.extend({
        lovetemplate: 'user_newsfeed_itemloves',
        lovesel: '.js-itemloves',
        morelimit: 1024,

        events: {
            'keydown .js-comment': 'sendComment',
            'keypress .js-comment': 'addMention',
            'focus .js-comment': 'moreStuff',
            'click .js-addvolunteer': 'addVolunteer',
            'click .js-addevent': 'addEvent',
            'click .js-showearlier': 'showEarlier',
            'click .js-sharefb': 'sharefb',
            'click .js-moremessage': 'moreMessage'
        },

        showAll: false,

        moreMessage: function(e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            self.$('.js-message').html(_.escape(self.model.get('moremessage')));
            if (self.$('.js-message').length) {
                twemoji.parse(self.$('.js-message').get()[0]);
            }

            self.$('.js-moremessage').hide();
        },

        sharefb: function() {
            var self = this;
            var params = {
                method: 'share',
                href: window.location.protocol + '//' + window.location.host + '/newsfeed/' + self.model.get('id') + '?src=fbshare',
                image: self.image
            };

            FB.ui(params, function (response) {
                self.$('.js-fbshare').fadeOut('slow');
                ABTestAction('newsfeedbutton', 'Facebook Share');
            });
        },

        showEarlier: function(e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();
            self.showAll = true;
            self.$('.js-showearlier').hide();
            self.collectionView.render();
        },

        addVolunteer: function() {
            var v = new Iznik.Views.User.Volunteering.Editable({
                model: new Iznik.Models.Volunteering({})
            });
            v.render();
        },

        addEvent: function() {
            var v = new Iznik.Views.User.CommunityEvent.Editable({
                model: new Iznik.Models.CommunityEvent({})
            });
            v.render();
        },

        addMention: function(e) {
            var self = this;

            if (String.fromCharCode(e.which) == '@') {
                if (!self.mentioned) {
                    self.mentioned = true;
                    self.mention('.js-comment');
                }
            }
        },

        moreStuff: function() {
            // Autosize is expensive, so only do it when we focus on the input field.  That means we only do it
            // when someone is actually going to make a comment.
            var self = this;

            if (!self.autosized) {
                self.autosized = true;
                autosize(self.$('.js-comment'));
            }
        },

        sendComment: function (e) {
            var self = this;

            if (e.which === 13) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (e.altKey || e.shiftKey) {
                    // They've used the alt/shift trick.
                    self.$('.js-comment').val(self.$('.js-comment').val() + "\n");
                } else  {
                    self.$('.js-comment').prop('disabled', true);
                    var msg = self.$('.js-comment').val();

                    if (msg.length > 0) {
                        msg = twemoji.replace(msg, function(emoji) {
                            return '\\\\u' + twemoji.convert.toCodePoint(emoji) + '\\\\u';
                        });

                        var mod = new Iznik.Models.Newsfeed({
                            replyto: self.model.get('id'),
                            message: msg
                        });

                        mod.save().then(function() {
                            self.$('.js-comment').val('');
                            self.$('.js-comment').prop('disabled', false);
                            autosize.update(self.$('.js-comment'));

                            mod.fetch().then(function() {
                                self.replies.add(mod);
                            });
                        });
                    }
                }
            }
        },

        updateTimer: false,

        checkUpdate: function() {
            var self = this;
            self.updateTimer = false;

            // console.log("Consider update", self.model.get('id'));

            if (self.inDOM()) {
                // console.log("Newsfeed visible", self.model.get('id'), self.$el.isOnScreen());
                // Only update when we're in the viewport.
                if (self.$el.isOnScreen()) {
                    // Get the latest info to update our view.
                    self.model.fetch().then(function() {
                        // Update the loves.
                        // console.log("Update loves", self.model);
                        self.loves.model = self.model;
                        self.loves.render().then(function() {
                            self.$('.js-itemloves').html(self.loves.$el);
                            self.loves.delegateEvents();
                        });

                        if (self.replies) {
                            // Update the replies collection.
                            var replies = self.model.get('replies');
                            // console.log("Replies", self.replies.length, replies.length);

                            if (replies && self.replies.length != replies.length) {
                                self.replies.add(replies);
                            }
                        }

                        if (self.model.collection && self.model.collection.indexOf(self.model) === 0) {
                            // This is the first one.  Fetch the collection so that if there are any new items
                            // we'll pick them up.
                            self.model.collection.trigger('refetch');

                            // This is the most recent one we've seen.
                            self.model.seen();
                        }

                        if (!self.updateTimer) {
                            self.updateTimer = true;
                            _.delay(_.bind(self.checkUpdate, self), 30000);
                        }
                    });
                }
            }
        },

        startCheck: function() {
            var self = this;

            if (!self.checking && !self.model.get('replyto')) {
                // Check periodically for updates to this item.  We don't check replies, because they are returned
                // in the parent.
                self.checking = true;
                _.delay(_.bind(self.checkUpdate, self), 30000);
            }
        },

        visible: function(model) {
            var self = this;
            var vis = model.get('visible');

            // Show last few.
            vis = vis && (self.showAll || model.collection.length < 10 || model.collection.indexOf(model) > (model.collection.length - 10));

            return(vis);
        },

        render: function() {
            var self = this;

            var p = resolvedPromise();

            if (!self.rendered) {
                self.rendered = true;
                // console.log("Render", self.model.get('id')); if (self.model.get('id') == 700) { console.trace(); }

                self.model.set('me', Iznik.Session.get('me'));

                self.template = null;
                switch (self.model.get('type')) {
                    case 'Message':                  self.template = 'user_newsfeed_item'; break;
                    case 'CommunityEvent':           self.template = 'user_newsfeed_communityevent'; break;
                    case 'VolunteerOpportunity':     self.template = 'user_newsfeed_volunteering'; break;
                    case 'CentralPublicity':         self.template = 'user_newsfeed_centralpublicity'; break;
                    case 'Alert':                    self.template = 'user_newsfeed_alert'; self.model.set('sitename', $('meta[name=izniksitename]').attr("content")); break;
                    case 'Story':                    self.template = 'user_newsfeed_story'; break;
                }

                if (self.template) {
                    var msg = self.model.get('message');

                    var preview = self.model.get('preview');
                    if (preview) {
                        // Don't allow previews which are too long.
                        if (preview.title) {
                            preview.title = ellipsical(strip_tags(preview.title), 120);
                        }

                        if (preview.description) {
                            preview.description = ellipsical(strip_tags(preview.description), 255);
                        }
                        self.model.set('preview', preview);
                    }

                    p = Iznik.Views.User.Feed.Base.prototype.render.call(this, {
                        model: self.model
                    });

                    p.then(function() {
                        if (self.model.get('moremessage')) {
                            // Handle re-render.
                            self.model.set('message', self.model.get('moremessage'));
                        }

                        var message = self.model.get('message');

                        if (message) {
                            if (message.length > self.morelimit) {
                                var ellip = ellipsical(message, self.morelimit);
                                self.$('.js-moremessage').show();
                                self.model.set('moremessage', message);
                                self.model.set('message', ellip);
                            }

                            self.$('.js-message').html(_.escape(self.model.get('message')));
                            if (self.$('.js-message').length) {
                                twemoji.parse(self.$('.js-message').get()[0]);
                            }
                        }

                        if (self.model.get('eventid')) {
                            var v = new Iznik.Views.User.CommunityEvent({
                                model: new Iznik.Model(self.model.get('communityevent'))
                            });

                            v.render().then(function() {
                                self.$('.js-eventsumm').html(v.$el);
                            });
                        }

                        if (self.model.get('volunteeringid')) {
                            var v = new Iznik.Views.User.Volunteering({
                                model: new Iznik.Model(self.model.get('volunteering'))
                            });

                            v.render().then(function() {
                                self.$('.js-volunteeringsumm').html(v.$el);
                            });
                        }

                        var replies = self.model.get('replies')
                        self.replies = new Iznik.Collections.Replies(replies);
                        self.contributors = [];
                        _.each(replies, function(reply) {
                            self.contributors[reply.user.id] = reply.user.displayname
                        });

                        var replyel = self.$('.js-replies');

                        if (replyel.length) {
                            self.collectionView = new Backbone.CollectionView({
                                el: replyel,
                                modelView: Iznik.Views.User.Feed.Reply,
                                visibleModelsFilter: _.bind(self.visible, self),
                                modelViewOptions: {
                                    highlight: self.options.highlight,
                                    contributors: self.contributors
                                },
                                collection: self.replies,
                                processKeyEvents: false
                            });

                            self.collectionView.render();

                            if (self.replies.length > 10) {
                                self.$('.js-showearlier').show();
                            }
                        }

                        self.loves = new Iznik.Views.User.Feed.Loves({
                            model: self.model
                        });

                        self.loves.template = self.lovetemplate;
                        self.loves.render().then(function() {
                            self.$('.js-itemloves').html(self.loves.$el);
                        });

                        // Each reply can ask us to focus on the reply box.
                        self.listenTo(self.replies, 'reply', _.bind(self.reply, self));
                        self.startCheck();

                        self.listenToOnce(FBLoad(), 'fbloaded', function () {
                            if (!FBLoad().isDisabled()) {
                                self.$('.js-sharefb').show();
                            }
                        });

                        FBLoad().render();
                    });
                }
            }

            return(p);
        }
    });

    Iznik.Views.User.Feed.Reply = Iznik.Views.User.Feed.Base.extend({
        template: 'user_newsfeed_reply',
        lovetemplate: 'user_newsfeed_replyloves',
        lovesel: '.js-replyloves',
        morelimit: 512,

        events: {
            'click .js-reply': 'reply',
            'click .js-replyprofile': 'showProfile',
            'click .js-moremessage': 'moreMessage'
        },

        moreMessage: function(e) {
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            self.$('.js-message').html(self.model.get('moremessage'));
            if (self.$('.js-message').length) {
                twemoji.parse(self.$('.js-message').get()[0]);
            }
            self.$('.js-moremessage').hide();
        },

        reply: function() {
            this.model.collection.trigger('reply', this.model.get('user'));
        },

        render: function() {
            var self = this;

            var type = self.model.get('type');

            self.model.set('sitename', $('meta[name=izniksitename]').attr("content"));

            switch (type) {
                case 'ReferToWanted': {
                    self.template = 'user_newsfeed_refertowanted';
                    break;
                }

                case 'ReferToOffer': {
                    self.template = 'user_newsfeed_refertooffer';
                    break;
                }

                case 'ReferToTaken': {
                    self.template = 'user_newsfeed_refertotaken';
                    break;
                }

                case 'ReferToReceived': {
                    self.template = 'user_newsfeed_refertoreceived';
                    break;
                }

                default: {}
            }

            var preview = self.model.get('preview');
            if (preview) {
                // Don't allow previews which are too long.
                if (preview.title) {
                    preview.title = ellipsical(strip_tags(preview.title), 120);
                }

                if (preview.description) {
                    preview.description = ellipsical(strip_tags(preview.description), 255);
                }
                self.model.set('preview', preview);
            }

            var p = Iznik.Views.User.Feed.Base.prototype.render.call(this, {
                model: self.model
            });

            p.then(function() {
                if (self.model.get('moremessage')) {
                    // Handle re-render.
                    self.model.set('message', self.model.get('moremessage'));
                }

                var message = self.model.get('message');

                if (message) {
                    if (message.length > self.morelimit) {
                        var ellip = ellipsical(message, self.morelimit);
                        self.$('.js-moremessage').show();
                        self.model.set('moremessage', message);
                        self.model.set('message', ellip);
                    }

                    var msg = _.escape(self.model.get('message'));
                    msg = self.highlightMentions(msg);
                    self.$('.js-message').html(msg);

                    if (self.$('.js-message').length) {
                        twemoji.parse(self.$('.js-message').get()[0]);
                    }
                }

                if (self.model.get('id') == self.options.highlight) {
                    // Make sure it's visible.
                    $(window).scrollTo(self.$el);

                    // Set the initial background colour and then fade to normal.  This draws the eye to the
                    // item we've clicked to see.
                    self.$el.addClass('highlightin');
                    _.delay(function() {
                        self.$el.addClass('highlightout');
                    }, 5000);
                }
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.Report = Iznik.Views.Modal.extend({
        template: 'user_newsfeed_report',

        events: {
            'click .js-report': 'report'
        },

        report: function() {
            var self = this;
            var reason = self.$('.js-reason').val();

            if (reason.length > 0) {
                self.model.report(reason);
                self.trigger('reported');
            }
        }
    });

    Iznik.Views.User.Feed.CommunityEvent = Iznik.Views.User.CommunityEvent.Editable.extend({
        template: 'user_newsfeed_addevent',
        parentClass: Iznik.View,
        closeAfterSave: false,

        afterSave: function() {
            var self = this;
            (new Iznik.Views.User.CommunityEvent.Confirm()).render();
            self.$('.js-addblock').hide();
            self.$('.js-postadd').show();
            $("body").animate({ scrollTop: 0 }, "fast");
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.User.CommunityEvent.Editable.prototype.render.call(this);
            self.listenToOnce(self, 'saved', _.bind(self.afterSave, self));
        }
    });

    Iznik.Views.User.Feed.Volunteering = Iznik.Views.User.Volunteering.Editable.extend({
        template: 'user_newsfeed_addvolunteer',
        parentClass: Iznik.View,
        closeAfterSave: false,

        afterSave: function() {
            var self = this;
            (new Iznik.Views.User.Volunteering.Confirm()).render();
            self.$('.js-addblock').hide();
            self.$('.js-postadd').show();
            $("body").animate({ scrollTop: 0 }, "fast");
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.User.Volunteering.Editable.prototype.render.call(this);
            self.listenToOnce(self, 'saved', _.bind(self.afterSave, self));
        }
    });

    Iznik.Views.User.Feed.InlinePost = Iznik.View.extend({
        events: {
            'click .js-getloc': 'getLocation',
            'typeahead:change .js-postcode': 'locChange'
        },

        getLocation: function() {
            var self = this;
            self.wait = new Iznik.Views.PleaseWait();
            self.wait.render();

            navigator.geolocation.getCurrentPosition(_.bind(this.gotLocation, this));
        },

        gotLocation: function(position) {
            var self = this;

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }, success: function(ret) {
                    if (ret.ret == 0 && ret.location) {
                        self.recordLocation(ret.location, true);

                        // Add some eye candy to make them spot the location.
                        self.$('.js-postcode').tooltip('destroy');
                        self.$('.js-postcode').tooltip({
                            'placement': 'top',
                            'title': "Your device thinks you're here.  If it's wrong, please change it."});
                        self.$('.js-postcode').tooltip('show');
                        _.delay(function() {
                            self.$('.js-postcode').tooltip('destroy');
                        }, 20000);
                    }
                }, complete: function() {
                    if (self.wait) {
                        self.wait.close();
                    }
                }
            });
        },

        locChange: function() {
            var self = this;

            var loc = this.$('.js-postcode').typeahead('val');

            $.ajax({
                type: 'GET',
                url: API + 'locations',
                data: {
                    typeahead: loc
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        self.recordLocation(ret.locations[0]);
                    }
                }
            });
        },

        recordLocation: function(location) {
            var self = this;

            if (!_.isUndefined(location)) {
                try {
                    self.$('.js-postcode').typeahead('val', location.name);
                    var l = location;

                    // Show the select for groups we could use on this site.
                    self.$('.js-groups').empty();
                    _.each(l.groupsnear, function(group) {
                        if (group.onhere && group.type == 'Freegle') {
                            self.$('.js-groups').append('<option value="' + group.id + '">' + group.namedisplay + '</option>');
                        }
                    });

                    // Don't store the groups, too long.
                    delete l.groupsnear;
                    Iznik.Session.setSetting('mylocation', l);
                    Storage.set('mylocation', JSON.stringify(l))
                } catch (e) {
                    console.log("Exception", e.message);
                };
            }
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
                    });

                    asyncResults(matches);

                    _.delay(function() {
                        self.$('.js-postcode').tooltip('destroy');
                    }, 10000);

                    if (matches.length == 0) {
                        self.$('.js-postcode').tooltip({'trigger':'focus', 'title': 'Please use a valid UK postcode (including the space)'});
                        self.$('.js-postcode').tooltip('show');
                    } else {
                        self.firstMatch = matches[0];
                    }
                }
            })
        },

        itemSource: function (query, syncResults, asyncResults) {
            var self = this;

            if (query.length >= 2) {
                $.ajax({
                    type: 'GET',
                    url: API + 'item',
                    data: {
                        typeahead: query
                    }, success: function (ret) {
                        var matches = [];
                        _.each(ret.items, function (item) {
                            if (item.hasOwnProperty('item')) {
                                matches.push(item.item.name);
                            }
                        });

                        asyncResults(matches);
                    }
                })
            }
        },

        getItem: function () {
            var val = this.$('.js-item').typeahead('val');
            if (!val) {
                val = this.$('.js-item').val();
            }
            return(val);
        },

        postIt: function () {
            var self = this;

            self.pleaseWait = new Iznik.Views.PleaseWait();
            self.pleaseWait.render();

            var email = Iznik.Session.get('me').email;

            // First check we have an item
            var item = self.getItem();
            if (item.length == 0) {
                self.$('.js-item').focus();
                self.$('.js-item').addClass('error-border');
            } else {
                // Get the location - we should have got it in local storage from updating the postcode box.
                var locationid = null;

                var loc = Storage.get('mylocation');
                locationid = loc ? JSON.parse(loc).id : null;

                if (!locationid) {
                    self.$('.js-postcode').focus();
                    self.$('.js-postcode').addClass('error-border');
                } else {
                    // Now check we have a group
                    var groupid = self.$('.js-groups').val();

                    if (!groupid) {
                        self.$('.js-groups').addClass('error-border');
                    } else {
                        // Get any photos.
                        var attids = [];
                        self.photos.each(function (photo) {
                            attids.push(photo.get('id'))
                        });

                        if (attids.length == 0 && self.$('.js-description').val().trim().length == 0) {
                            // Want a description or a photo.
                            self.$('.js-description').focus();
                            self.$('.js-description').addClass('error-border');
                        } else {
                            // Now create a draft.
                            var data = {
                                collection: 'Draft',
                                locationid: locationid,
                                messagetype: self.msgType,
                                item: item,
                                textbody: self.$('.js-description').val(),
                                attachments: attids,
                                groupid: groupid
                            };

                            $.ajax({
                                type: 'PUT',
                                url: API + 'message',
                                data: data,
                                success: function (ret) {
                                    if (ret.ret == 0) {
                                        // Created the draft - submit it.
                                        var id = ret.id;

                                        $.ajax({
                                            type: 'POST',
                                            url: API + 'message',
                                            data: {
                                                action: 'JoinAndPost',
                                                email: email,
                                                id: id
                                            }, success: function (ret) {
                                                self.pleaseWait.close();
                                                self.$('.js-prepost').hide();
                                                self.$('.js-posted').fadeIn('slow');

                                                (new Iznik.Views.User.Feed.InlineConfirm()).render();

                                                // No FOP for this method at the moment.
                                                var m = new Iznik.Models.Message({
                                                    id: id
                                                });

                                                m.fetch().then(function() {
                                                    m.setFOP(0);
                                                });
                                            }
                                        });
                                    }
                                }
                            });
                        }
                    }
                }
            }
        },

        render: function() {
            var self = this;

            self.photos = new Iznik.Collection();
            self.draftPhotos = new Iznik.Views.User.Message.Photos({
                collection: self.photos,
                message: null,
                showAll: true
            });

            var p = Iznik.View.prototype.render.call(self);

            p.then(function() {
                // We might have switched from composing a discussion post to here.
                var msg = $('#js-discussmessage').val();
                self.$('.js-description').val(msg);

                self.$('.js-postcode').typeahead({
                    minLength: 3,
                    hint: false,
                    highlight: true
                }, {
                    name: 'postcodes',
                    source: _.bind(self.postcodeSource, self)
                });

                var mylocation = Storage.get('mylocation');

                if (!mylocation) {
                    mylocation = Iznik.Session.getSetting('mylocation', null);
                } else {
                    mylocation = JSON.parse(mylocation);
                }

                if (mylocation) {
                    var postcode = mylocation.name;

                    if (postcode) {
                        self.$('.js-postcode').typeahead('val', postcode);

                        // Fetch it so that we can get the list of groups.
                        $.ajax({
                            type: 'GET',
                            url: API + 'locations',
                            data: {
                                typeahead: postcode
                            }, success: function(ret) {
                                if (ret.ret == 0 && ret.locations.length > 0) {
                                    self.recordLocation(ret.locations[0]);
                                }
                            }
                        });
                    }
                }

                self.typeahead = self.$('.js-item').typeahead({
                    minLength: 2,
                    hint: false,
                    highlight: true,
                    autoselect: false
                }, {
                    name: 'items',
                    source: self.itemSource,
                    limit: 3
                });

                // Close the suggestions after 30 seconds in case people are confused.
                self.$('.js-item').bind('typeahead:open', function() {
                    _.delay(function() {
                        self.$('.js-item').typeahead('close');
                    }, 30000);
                });

                // File upload
                self.$(self.photoId).fileinput({
                    uploadExtraData: {
                        imgtype: 'Message',
                        identify: false
                    },
                    showUpload: false,
                    allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                    uploadUrl: API + 'image',
                    resizeImage: true,
                    maxImageWidth: 800,
                    browseIcon: '<span class="glyphicon glyphicon-plus" />&nbsp;',
                    browseLabel: 'Add photos',
                    browseClass: 'btn btn-primary nowrap',
                    showCaption: false,
                    showRemove: false,
                    showUploadedThumbs: false,
                    dropZoneEnabled: false,
                    buttonLabelClass: '',
                    fileActionSettings: {
                        showZoom: false,
                        showRemove: false,
                        showUpload: false
                    },
                    layoutTemplates: {
                        footer: '<div class="file-thumbnail-footer">\n' +
                        '    {actions}\n' +
                        '</div>'
                    }
                });

                // Upload as soon as photos have been resized.
                self.$(self.photoId).on('fileimageresized', function (event) {
                    self.$(self.photoId).fileinput('upload');

                    // We don't seem to be able to hide this control using the options.
                    self.$('.fileinput-remove').hide();
                });

                // Watch for all uploaded
                self.$(self.photoId).on('fileuploaded', function (event, data) {
                    // Add the photo to our list
                    var mod = new Iznik.Models.Message.Attachment({
                        id: data.response.id,
                        path: data.response.path,
                        paththumb: data.response.paththumb,
                        mine: true
                    });

                    self.photos.add(mod);

                    // Show the uploaded thumbnail and hackily remove the one provided for us.
                    self.draftPhotos.render().then(function() {
                        self.$('.js-draftphotos').html(self.draftPhotos.el);
                    });

                    _.delay(function() {
                        self.$('.file-preview').remove();
                        self.$('.file-preview-frame').remove();
                        self.$('.js-draftphotos').show();
                    }, 500);
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Feed.InlineOffer = Iznik.Views.User.Feed.InlinePost.extend({
        template: 'user_newsfeed_inlineoffer',
        msgType: 'Offer',
        photoId: '#offerphoto',
        events: {
            'click .js-postoffer': 'postIt'
        }
    });

    Iznik.Views.User.Feed.InlineWanted = Iznik.Views.User.Feed.InlinePost.extend({
        template: 'user_newsfeed_inlinewanted',
        msgType: 'Wanted',
        photoId: '#wantedphoto',
        events: {
            'click .js-postwanted': 'postIt'
        }
    });

    Iznik.Views.User.Feed.InlineConfirm = Iznik.Views.Modal.extend({
        template: "user_newsfeed_inlineconfirm"
    });

    Iznik.Views.User.Feed.Help = Iznik.Views.Modal.extend({
        template: "user_newsfeed_help"
    });
});
