define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/infinite'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Message = Iznik.View.extend({
        className: "marginbotsm botspace",

        events: {
            'click .js-caret': 'carettoggle',
            'click .js-fop': 'fop'
        },

        expanded: false,

        caretshow: function() {
            if (!this.expanded) {
                this.$('.js-replycount').addClass('reallyHide');
                this.$('.js-unreadcountholder').addClass('reallyHide');
                this.$('.js-promised').addClass('reallyHide');
                this.$('.js-caretdown').show();
                this.$('.js-caretup').hide();
            } else {
                this.$('.js-replycount').removeClass('reallyHide');
                this.$('.js-unreadcountholder').removeClass('reallyHide');
                this.$('.js-promised').removeClass('reallyHide');
                this.$('.js-caretdown').hide();
                this.$('.js-caretup').show();
            }
        },

        expand: function() {
            this.$('.js-caretdown').click();
        },

        continueReply: function(text) {
            // This is when we were in the middle of replying to a message.
            var self = this;
            this.$('.js-replytext').val(text);

            // Remove local storage so that we don't get stuck sending the same message, for example if we reload the
            // page.
            try {
                localStorage.removeItem('replyto');
                localStorage.removeItem('replytext');
            } catch (e) {}

            // We might get called back twice because of the html, body selector (which we need for browser compatibility)
            // so make sure we only actually click send once.
            self.readyToSend = true;

            $('html, body').animate({
                    scrollTop: self.$('.js-replytext').offset().top
                },
                2000,
                function() {
                    if (self.readyToSend) {
                        self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                            // Now send it.
                            self.readyToSend = false;
                            self.$('.js-send').click();
                        });

                        Iznik.Session.forceLogin();
                    }
                }
            );
        },

        carettoggle: function() {
            this.expanded = !this.expanded;
            if (this.expanded) {
                this.$('.js-snippet').slideUp();
            } else {
                this.$('.js-snippet').slideDown();
            }
            this.caretshow();
        },

        fop: function() {
            var v = new Iznik.Views.Modal();
            v.open('user_home_fop');
        },

        updateReplies: function() {
            if (this.replies.length == 0) {
                this.$('.js-noreplies').fadeIn('slow');
            } else {
                this.$('.js-noreplies').hide();
            }
        },

        updateUnread: function() {
            var self = this;
            var unread = 0;

            // We might or might not have the chats, depending on whether we're logged in at this point.
            if (Iznik.Session.hasOwnProperty('chats')) {
                Iznik.Session.chats.each(function(chat) {
                    var refmsgids = chat.get('refmsgids');
                    _.each(refmsgids, function(refmsgid) {
                        if (refmsgid == self.model.get('id')) {
                            var thisun = chat.get('unseen');
                            unread += thisun;

                            if (thisun > 0) {
                                // This chat might indicate a new replier we've not got listed.
                                // TODO Could make this perform better than doing a full fetch.
                                self.model.fetch().then(function() {
                                    self.replies.add(self.model.get('replies'));
                                    self.updateReplies();
                                });
                            }
                        }
                    });
                });
            }

            if (unread > 0) {
                this.$('.js-unreadcount').html(unread);
                this.$('.js-unreadcountholder').show();
            } else {
                this.$('.js-unreadcountholder').hide();
            }
        },

        watchChatRooms: function() {
            var self = this;

            if (this.inDOM() && Iznik.Session.hasOwnProperty('chats')) {
                // If the number of unread messages relating to this message changes, we want to flag it in the count.  So
                // look for chats which refer to this message.  Note that chats can refer to multiple.
                Iznik.Session.chats.each(function (chat) {
                    self.listenTo(chat, 'change:unseen', self.updateUnread);
                });

                self.updateUnread();

                self.listenToOnce(Iznik.Session.chats, 'newroom', self.watchChatRooms);
            }
        },

        render: function() {
            var self = this;

            var outcomes = self.model.get('outcomes');
            if (outcomes && outcomes.length > 0) {
                // Hide completed posts by default.
                // TODO option to show
                self.$el.hide();
            }

            // Make safe and decent for display.
            this.model.stripGumf('textbody');
            this.model.set('textbody', strip_tags(this.model.get('textbody')));

            // The server will have returned us a snippet.  But if we've stripped out the gumf and we have something
            // short, use that instead.
            var tb = this.model.get('textbody');
            if (tb.length < 60) {
                this.model.set('snippet', tb);
            }

            var p = Iznik.View.prototype.render.call(self);
            p.then(function() {
                if (self.expanded) {
                    self.$('.panel-collapse').collapse('show');
                } else {
                    self.$('.panel-collapse').collapse('hide');
                }

                var groups = self.model.get('groups');
                self.$('.js-groups').empty();

                // We want to know whether a message is visible on the group, because this affects which
                // buttons we should show.
                var approved = false;
                var rejected = false;
                var pending = false;

                _.each(groups, function(group) {
                    if (group.collection == 'Approved') {
                        approved = true;
                    }
                    if (group.collection == 'Pending') {
                        pending = true;
                    }
                    if (group.collection == 'Rejected') {
                        rejected = true;
                    }

                    var v = new Iznik.Views.User.Message.Group({
                        model: new Iznik.Model(group)
                    });
                    v.render();
                    self.$('.js-groups').append(v.el);
                });

                if (approved || pending) {
                    self.$('.js-taken').show();
                    self.$('.js-received').show();
                }

                if (rejected) {
                    self.$('.js-rejected').show();
                }

                self.$('.js-attlist').empty();
                var photos = self.model.get('attachments');

                var v = new Iznik.Views.User.Message.Photos({
                    collection: new Iznik.Collection(photos),
                    subject: self.model.get('subject')
                });
                v.render().then(function() {
                    self.$('.js-attlist').append(v.el);
                });

                if (self.$('.js-replies').length > 0) {
                    var replies = self.model.get('replies');
                    self.replies = new Iznik.Collection(replies);

                    if (replies && replies.length > 0) {
                        // Show and update the reply details.
                        if (replies.length > 0) {
                            self.$('.js-noreplies').hide();
                            self.$('.js-replies').empty();
                            self.listenTo(self.model, 'change:replies', self.updateReplies);
                            self.updateReplies();

                            self.repliesView = new Backbone.CollectionView({
                                el: self.$('.js-replies'),
                                modelView: Iznik.Views.User.Message.Reply,
                                modelViewOptions: {
                                    collection: self.replies,
                                    message: self.model,
                                    offers: self.options.offers
                                },
                                collection: self.replies
                            });

                            self.repliesView.render();

                            // We might have been asked to open up one of these messages because we're showing the corresponding
                            // chat.
                            if (self.options.chatid ) {
                                var model = self.replies.get(self.options.chatid);
                                if (model) {
                                    var view = self.repliesView.viewManager.findByModel(model);
                                    // Slightly hackily jump up to find the owning message and click to expand.
                                    view.$el.closest('.panel-heading').find('.js-caret').click();
                                }
                            }
                        } else {
                            self.$('.js-noreplies').show();
                        }
                    }
                }

                self.updateUnread();

                // We want to keep an eye on chat messages, because those which are in conversations referring to our
                // message should affect the counts we display.
                self.watchChatRooms();

                // If the number of promises changes, then we want to update what we display.
                self.listenTo(self.model, 'change:promisecount', self.render);

                // By adding this at the end we avoid border flicker.
                self.$el.addClass('panel panel-info');
            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Group = Iznik.View.extend({
        template: "user_message_group",

        render: function() {
            var self = this;
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                self.$('.timeago').timeago();

                if (self.model.get('autorepostallowed')) {
                    // console.log("Repost at", self.model.get('autorepostat'), (moment(self.model.get('autorepostat')).fromNow()));
                    self.$('.js-autodue').html((moment(self.model.get('autorepostat')).fromNow()));
                }
            });
            return(p);
        }
    });

    Iznik.Views.User.Message.Photo = Iznik.View.extend({
        tagName: 'li',

        events: {
            'click': 'zoom'
        },
        
        template: 'user_message_photo',

        zoom: function (e) {
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.User.Message.PhotoZoom({
                model: this.model
            });
            v.render();
        }
    });

    Iznik.Views.User.Message.PhotoZoom = Iznik.Views.Modal.extend({
        template: 'user_message_photozoom'
    });

    Iznik.Views.User.Message.Photos = Iznik.View.extend({
        template: 'user_message_photos',

        offset: 0,

        nextPhoto: function() {
            var self = this;
            self.currentPhoto.fadeOut('slow', function() {
                self.offset++;
                self.offset = self.offset % self.photos.length;
                self.currentPhoto = self.photos[self.offset];
                self.currentPhoto.fadeIn('slow', function() {
                    _.delay(_.bind(self.nextPhoto, self), 10000);
                })
            })
        },

        render: function() {
            var self = this;
            var len = self.collection.length;

            // If we have multiple photos, then we cycle through each of them, fading in and out.  This reduces the
            // screen space, but still allows people to see all of them.
            var p = Iznik.View.prototype.render.call(this);
            p.then(function() {
                self.photos = [];
                self.collection.each(function(att) {
                    att.set('subject', self.options.subject);

                    var v = new Iznik.Views.User.Message.Photo({
                        model: att
                    });
                    v.render().then(function() {
                        self.$('.js-photos').append(v.$el);
                    });

                    self.photos.push(v.$el);

                    if (self.photos.length > 1) {
                        v.$el.hide();
                    } else {
                        self.currentPhoto = v.$el;
                    }
                });

                if (self.photos.length > 1) {
                    _.delay(_.bind(self.nextPhoto, self), 10000);
                }
            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Reply = Iznik.View.extend({
        tagName: 'li',

        template: 'user_message_reply',

        className: 'message-reply',

        events: {
            'click .js-chat': 'dm',
            'click .js-chatmods': 'chatMods',
            'click .js-promise': 'promise',
            'click .js-renege': 'renege'
        },

        dm: function() {
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                var chat = self.model.get('chat');
                var myid = Iznik.Session.get('me').id;
                var user = chat.user1.id != myid ? chat.user1.id : chat.user2.id;
                ChatHolder().openChat(user);
            })
        },

        chatMods: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            require(['iznik/views/chat/chat'], function(ChatHolder) {
                var chatid = self.model.get('chatid');

                var chat = Iznik.Session.chats.get({
                    id: chatid
                });

                var groupid = chat.get('group').id;
                ChatHolder().openChatToMods(groupid);
            });
        },

        promise: function() {
            var self = this;

            var v = new Iznik.Views.User.Message.Promise({
                model: new Iznik.Model({
                    message: self.options.message.toJSON2(),
                    user: self.model.get('user')
                }),
                offers: self.options.offers
            });

            self.listenToOnce(v, 'promised', function() {
                self.options.message.fetch().then(function() {
                    self.render.call(self, self.options);
                })
            });

            v.render();
        },

        renege: function() {
            var self = this;

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'user_message_renege';

            self.listenToOnce(v, 'confirmed', function() {
                $.ajax({
                    url: API + 'message/' + self.options.message.get('id'),
                    type: 'POST',
                    data: {
                        action: 'Renege',
                        userid: self.model.get('user').id
                    }, success: function() {
                        self.options.message.fetch().then(function() {
                            self.render.call(self, self.options);
                        });
                    }
                })
            });

            v.render();
        },

        chatPromised: function() {
            var self = this;
            self.model.set('promised', true);
            self.render();
        },

        render: function() {
            var self = this;

            self.model.set('me', Iznik.Session.get('me'));

            var chat = Iznik.Session.chats.get({
                id: self.model.get('chatid')
            });

            // We might not find this chat if the user has closed it.
            if (!_.isUndefined(chat)) {
                self.model.set('chat', chat.toJSON2());
                self.model.set('unseen', chat.get('unseen'));
                self.model.set('message', self.options.message.toJSON2());
            }

            var p = Iznik.View.prototype.render.call(self).then(function(self) {
                // If the number of unseen messages in this chat changes, update this view so that the count is
                // displayed here.
                self.listenToOnce(chat, 'change:unseen', self.render);
                p = Iznik.View.prototype.render.call(self).then(function() {
                    self.$('.timeago').timeago();
                });

                // We might promise to this person from a chat.
                self.listenTo(chat, 'promised', _.bind(self.chatPromised, self));
            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Promise = Iznik.Views.Confirm.extend({
        template: 'user_message_promise',

        promised: function() {
            var self = this;

            $.ajax({
                url: API + 'message/' + self.model.get('message').id,
                type: 'POST',
                data: {
                    action: 'Promise',
                    userid: self.model.get('user').id
                }, success: function() {
                    self.trigger('promised')
                }
            })
        },

        render: function() {
            var self = this;
            this.listenToOnce(this, 'confirmed', this.promised);
            var p = this.open(this.template);
            p.then(function() {
                var msgid = self.model.get('message').id;

                self.options.offers.each(function(offer) {
                    self.$('.js-offers').append('<option value="' + offer.get('id') + '" />');
                    self.$('.js-offers option:last').html(offer.get('subject'));
                });

                self.$('.js-offers').val(msgid);

            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Replyable = Iznik.Views.User.Message.extend({
        template: 'user_message_replyable',

        events: {
            'click .js-send': 'send',
            'click .js-mapzoom': 'mapZoom'
        },

        initialize: function(){
            this.events = _.extend(this.events, Iznik.Views.User.Message.prototype.events);
        },

        mapZoom: function(e) {
            e.preventDefault();
            e.stopPropagation();

            var self = this;
            var v = new Iznik.Views.User.Message.Map({
                model: self.model
            });

            v.render();
        },

        wordify: function (str) {
            str = str.replace(/\b(\w*)/g, "<span>$1</span>");
            return (str);
        },

        startChat: function() {
            // We start a conversation with the sender.
            var self = this;

            self.wait = new Iznik.Views.PleaseWait({
                label: "message startChat"
            });
            self.wait.render();

            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    userid: self.model.get('fromuser').id
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        var chatid = ret.id;
                        var msg = self.$('.js-replytext').val();

                        $.ajax({
                            type: 'POST',
                            url: API + 'chat/rooms/' + chatid + '/messages',
                            data: {
                                message: msg,
                                refmsgid: self.model.get('id')
                            }, complete: function() {
                                // Ensure the chat is opened, which shows the user what will happen next.
                                Iznik.Session.chats.fetch().then(function() {
                                    self.wait.close();
                                    self.$('.js-replybox').slideUp();
                                    var chatmodel = Iznik.Session.chats.get(chatid);
                                    var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                    chatView.restore();

                                    // If we were replying, we might have forced a login and shown the message in
                                    // isolation, in which case we need to return to where we were.
                                    try {
                                        var ret = localStorage.getItem('replyreturn');
                                        console.log("Return after reply", ret);

                                        if (ret) {
                                            Router.navigate(ret, true);
                                        }
                                    } catch (e) {};
                                });
                            }
                        });
                    }
                }
            })
        },

        send: function() {
            var self = this;
            var replytext = self.$('.js-replytext').val();
            console.log("Send reply", replytext);

            if (replytext.length == 0) {
                self.$('.js-replytext').addClass('error-border').focus();
            } else {
                self.$('.js-replytext').removeClass('error-border');

                // If we're not already logged in, we want to be.
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedin) {
                    console.log("Send; logged in?", loggedin);
                    if (loggedin) {
                        // We are logged in and can proceed.
                        //
                        // When we reply to a message on a group, we join the group if we're not already a member.
                        var memberofs = Iznik.Session.get('groups');
                        var member = false;
                        var tojoin = null;

                        if (memberofs) {
                            memberofs.each(function (memberof) {
                                var msggroups = self.model.get('groups');
                                _.each(msggroups, function (msggroup) {
                                    if (memberof.id == msggroup.groupid) {
                                        member = true;
                                    }
                                });
                            });
                        }

                        console.log("Already a member?", member);

                        if (!member) {
                            // We're not a member of any groups on which this message appears.  Join one.  Doesn't much
                            // matter which.
                            var tojoin = self.model.get('groups')[0].id;
                            console.log("To join", tojoin);
                            $.ajax({
                                url: API + 'memberships',
                                type: 'PUT',
                                data: {
                                    groupid: tojoin
                                }, success: function (ret) {
                                    if (ret.ret == 0) {
                                        // We're now a member of the group.  Fetch the message back, because we'll see more
                                        // info about it now.
                                        self.model.fetch().then(function () {
                                            self.startChat();
                                        })
                                    } else {
                                        // TODO
                                    }
                                }, error: function () {
                                    // TODO
                                }
                            })
                        } else {
                            console.log("We're already a member");
                            self.startChat();
                        }
                    } else {
                        // We are not logged in, and will have to do so.  This may result in a page reload - so save
                        // off details of our reply in local storage.
                        try {
                            localStorage.setItem('replyto', self.model.get('id'));
                            localStorage.setItem('replytext', replytext);
                            localStorage.setItem('replyreturn', Backbone.history.getFragment());
                        } catch (e) {}

                        // Set the route to the individual message.  This will spot the local storage, force us to
                        // log in, and then send it.  This also means that when the page is reloaded because of a login,
                        // we don't have issues with not seeing/needing to scroll to the message of interest.
                        //
                        // We might already be on this page, so we can't call navigate as usual.
                        Backbone.history.loadUrl('/message/' + self.model.get('id'));
                    }
                });

                Iznik.Session.testLoggedIn({
                    modtools: false
                });
            }
        },

        render: function() {
            var self = this;
            var p;

            if (self.rendered) {
                p = resolvedPromise(self);
            } else {
                self.rendered = true;
                var mylocation = null;
                try {
                    mylocation = localStorage.getItem('mylocation');

                    if (mylocation) {
                        mylocation = JSON.parse(mylocation);
                    }
                } catch (e) {
                }

                this.model.set('mylocation', mylocation);

                // Static map custom markers don't support SSL.
                this.model.set('mapicon', 'http://' + window.location.hostname + '/images/mapareamarker.png');

                // Get a zoom level for the map.
                var zoom = 12;
                _.each(self.model.get('groups'), function (group) {
                    zoom = group.settings.hasOwnProperty('map') ? group.settings.map.zoom : 12;
                });

                self.model.set('mapzoom', zoom);

                // Hide until we've got a bit into the render otherwise the border shows.
                this.$el.css('visibility', 'hidden');
                p = Iznik.Views.User.Message.prototype.render.call(this);

                p.then(function() {
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

                    if (self.model.get('mine')) {
                        // Stop people replying to their own messages.
                        self.$('.panel-footer').hide();
                    } else {
                        // We might have been trying to reply.
                        try {
                            var replyto = localStorage.getItem('replyto');
                            var replytext = localStorage.getItem('replytext');
                            var thisid = self.model.get('id');

                            if (replyto == thisid) {
                                self.continueReply.call(self, replytext);
                            }
                        } catch (e) {console.log("Failed", e)}
                    }

                    self.$el.css('visibility', 'visible');
                })
            }

            return(p);
        }
    });

    Iznik.Views.User.Message.Map = Iznik.Views.Modal.extend({
        template: 'user_message_mapzoom',

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function() {
                require(['gmaps'], function() {
                    self.waitDOM(self, function(self) {
                        // Set map to be square - will have height 0 when we open.
                        var map = self.$('.js-map');
                        var mapWidth = map.width();
                        map.height(mapWidth);

                        var location = self.model.get('location');
                        var area = self.model.get('area');
                        var centre = null;

                        if (location) {
                            centre = new google.maps.LatLng(location.lat, location.lng);
                        } else if (area) {
                            centre = new google.maps.LatLng(area.lat, area.lng);
                            self.$('.js-vague').show();
                        }

                        var mapOptions = {
                            mapTypeControl      : false,
                            streetViewControl   : false,
                            center              : centre,
                            panControl          : mapWidth > 400,
                            zoomControl         : mapWidth > 400,
                            zoom                : self.model.get('zoom') ? self.model.get('zoom') : 16
                        };

                        self.map = new google.maps.Map(map.get()[0], mapOptions);

                        var icon = {
                            url: '/images/user_logo.png',
                            scaledSize: new google.maps.Size(50, 50),
                            origin: new google.maps.Point(0,0),
                            anchor: new google.maps.Point(0, 0)
                        };

                        var marker = new google.maps.Marker({
                            position: centre,
                            icon: icon,
                            map: self.map
                        });
                    });
                });
            });

            return(p);
        }
    });
});