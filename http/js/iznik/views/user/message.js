define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'clipboard',
    'iznik/views/infinite'
], function($, _, Backbone, Iznik, moment, Clipboard) {
    Iznik.Views.User.Message = Iznik.View.extend({
        className: "marginbotsm botspace",

        events: {
            'click .js-caret': 'carettoggle',
            'click .js-fop': 'fop',
            'click .js-sharefb': 'sharefb'
        },

        sharefb: function() {
            var self = this;
            var params = {
                method: 'share',
                href: window.location.protocol + '//' + window.location.host + '/message/' + self.model.get('id') + '?src=fbshare',
                image: self.image
            };

            FB.ui(params, function (response) {
                self.$('.js-fbshare').fadeOut('slow');

                ABTestAction('messagebutton', 'Facebook Share');
            });
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

                if (self.unread > 0) {
                    this.$('.js-unreadcountholder').removeClass('reallyHide');
                } else {
                    this.$('.js-unreadcountholder').addClass('reallyHide');
                }

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
            if (this.expanded) {
                this.$('.js-snippet').slideDown();
            } else {
                this.$('.js-snippet').slideUp();
            }
            this.caretshow();
            this.expanded = !this.expanded;
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
            self.unread = 0;

            // We might or might not have the chats, depending on whether we're logged in at this point.
            if (Iznik.Session.hasOwnProperty('chats')) {
                var fetch = false;

                Iznik.Session.chats.each(function(chat) {
                    var refmsgids = chat.get('refmsgids');
                    _.each(refmsgids, function(refmsgid) {
                        if (refmsgid == self.model.get('id')) {
                            // This message is referenced in a chat.
                            var thisun = chat.get('unseen');
                            // console.log("Found message", refmsgid, chat.get('id'), chat.get('unseen'));
                            self.unread += thisun;

                            if (thisun > 0) {
                                // This chat might indicate a new replier we've not got listed.  Get the replies
                                // to make sure.
                                // TODO Could make this perform better than doing a full fetch.
                                fetch = true;
                            }
                        }
                    });
                });

                if (fetch) {
                    self.model.fetch().then(function() {
                        self.replies.add(self.model.get('replies'));
                        self.updateReplies();
                    });
                }
            }

            if (self.unread > 0) {
                this.$('.js-unreadcount').html(self.unread);
                this.$('.js-unreadcountholder').removeClass('reallyHide');
            } else {
                this.$('.js-unreadcountholder').addClass('reallyHide');
            }
        },

        watchChatRooms: function() {
            var self = this;
            // console.log("watchChatRooms for msg", self.model.get('id'));

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

            // console.log("Render message", self.model.get('id'), self.rendering, self.model);

            if (!self.rendering) {
                var replies = self.model.get('replies');
                self.replies = new Iznik.Collection(replies);

                // Make safe and decent for display.
                this.model.stripGumf('textbody');
                this.model.set('textbody', strip_tags(this.model.get('textbody')));

                // The server will have returned us a snippet.  But if we've stripped out the gumf and we have something
                // short, use that instead.
                var tb = this.model.get('textbody');
                if (tb.length < 60) {
                    this.model.set('snippet', tb);
                }

                self.rendering = new Promise(function(resolve, reject) {
                    Iznik.View.prototype.render.call(self).then(function() {
                        if (Iznik.Session.hasFacebook()) {
                            require(['iznik/facebook'], function(FBLoad) {
                                self.listenToOnce(FBLoad(), 'fbloaded', function () {
                                    if (!FBLoad().isDisabled()) {
                                        self.$('.js-sharefb').show();
                                    }
                                });

                                FBLoad().render();
                            });
                        }

                        if (self.expanded) {
                            self.$('.panel-collapse').collapse('show');
                            self.$('.js-snippet').hide();
                            self.$('.js-caretdown').parent().hide();
                        } else {
                            self.$('.panel-collapse').collapse('hide');
                            self.$('.js-snippet').show();
                        }

                        var groups = self.model.get('groups');
                        self.$('.js-groups').empty();

                        // We want to know whether a message is visible on the group, because this affects which
                        // buttons we should show.
                        var approved = false;
                        var rejected = false;
                        var pending = false;
                        self.$('.js-groups').empty();

                        _.each(groups, function(group) {
                            if (group.collection == 'Approved') {
                                approved = true;
                            }
                            if (group.collection == 'Pending' || group.collection == 'QueuedYahooUser') {
                                pending = true;
                            }
                            if (group.collection == 'Rejected') {
                                rejected = true;
                            }

                            var v = new Iznik.Views.User.Message.Group({
                                model: new Iznik.Model(group)
                            });
                            v.render().then(function() {
                                self.$('.js-groups').append(v.el);
                            });
                        });

                        // Repost time.
                        var repost = self.model.get('canrepostat');

                        if (repost && self.$('.js-repostat').length > 0) {
                            if (moment().diff(repost) >=  0) {
                                // Autorepost due.
                                self.$('.js-repostat').html('soon');
                            } else {
                                self.$('.js-repostat').html(moment(repost).fromNow());
                            }
                        }

                        // Show when it was first posted - some people like to use this to decide when to give up.
                        var postings = self.model.get('postings');
                        if (postings && postings.length > 1) {
                            self.$('.js-firstdate').html((new moment(postings[0].date)).format('DD-MMM-YY'));
                            self.$('.js-firstpost').show();
                        }

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
                            message: self.model
                        });
                        v.render().then(function() {
                            self.$('.js-attlist').append(v.el);
                        });

                        if (self.$('.js-replies').length > 0) {
                            if (replies && replies.length > 0) {
                                // Show and update the reply details.
                                if (replies.length > 0) {
                                    self.$('.js-noreplies').hide();
                                    self.$('.js-replies').empty();

                                    // If we get new replies, we want to re-render, as we want to show them, update the count
                                    // and so on.
                                    self.listenTo(self.model, 'change:replies', self.render);
                                    self.updateReplies();

                                    self.repliesView = new Backbone.CollectionView({
                                        el: self.$('.js-replies'),
                                        modelView: Iznik.Views.User.Message.Reply,
                                        modelViewOptions: {
                                            collection: self.replies,
                                            message: self.model,
                                            offers: self.options.offers
                                        },
                                        collection: self.replies,
                                        processKeyEvents: false
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

                        // We want to keep an eye on chat messages, because those which are in conversations referring to our
                        // message should affect the counts we display.  This will call updateUnread.
                        self.watchChatRooms();

                        // If the number of promises changes, then we want to update what we display.
                        self.listenTo(self.model, 'change:promisecount', self.render);

                        self.laughsAndLikes = new Iznik.Views.User.Message.LaughsAndLikes({
                            model: self.model
                        });

                        self.laughsAndLikes.render().then(function() {
                            self.$('.js-laughsandlikes').html(self.laughsAndLikes.el)
                        });

                        // By adding this at the end we avoid border flicker.
                        self.$el.addClass('panel panel-info');
                        
                        resolve();
                        self.rendering = null;
                    });
                });
            } else {
                // We're already rendering.  Queue a second render, as it's possible we have fetched new server
                // data which we would otherwise fail to display.
                //
                // Don't tight loop by using then().
                console.log("Already rendering - wait");
                _.delay(_.bind(self.render, self), 200);
            }

            return(self.rendering);
        }
    });

    Iznik.Views.User.Message.LaughsAndLikes = Iznik.View.extend({
        template: 'user_message_laughsandlikes',

        className: 'inline',

        events: {
            'click .js-love': 'love',
            'click .js-unlove': 'unlove',
            'click .js-laugh': 'laugh',
            'click .js-unlaugh': 'unlaugh'
        },

        love: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                self.model.love().then(_.bind(self.render, self));
            });

            Iznik.Session.forceLogin();
        },

        unlove: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                self.model.unlove().then(_.bind(self.render, self));
            });

            Iznik.Session.forceLogin();
        },

        laugh: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                self.model.laugh().then(_.bind(self.render, self));
            });

            Iznik.Session.forceLogin();
        },

        unlaugh: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function (loggedIn) {
                self.model.unlaugh().then(_.bind(self.render, self));
            });

            Iznik.Session.forceLogin();
        }
    });

    Iznik.Views.User.Message.Group = Iznik.View.Timeago.extend({
        template: "user_message_group"
    });

    Iznik.Views.User.Message.Photo = Iznik.View.extend({
        tagName: 'li',

        events: {
            'click img': 'zoom',
            'click .js-rotateright': 'rotateRight',
            'click .js-rotateleft': 'rotateLeft',
            'click .js-delete': 'deleteMe'
        },
        
        template: 'user_message_photo',

        deleteMe: function() {
            var self = this;

            if (self.options.message) {
                // Get the attachments in the message and remove this one.
                var atts = self.options.message.get('attachments');
                var newatts = _.reject(atts, function(att) {
                    return(att.id == self.model.get('id'));
                });

                // We need the list of ids.
                var attids = [];
                _.each(newatts, function(att) {
                    attids.push(att.id);
                });

                // Make the modification.
                $.ajax({
                    url: API + 'message',
                    type: 'PATCH',
                    data: {
                        id: self.options.message.get('id'),
                        attachments: attids
                    },
                    success: function(ret) {
                        if (ret.ret === 0) {
                            self.$el.fadeOut('slow', function() {
                                if (self.collection) {
                                    self.collection.remove(self.model);
                                }
                                self.destroyIt();
                            });
                        }
                    }
                });
            } else {
                // No server side message yet.
                if (self.collection) {
                    self.collection.remove(self.model);
                }
                self.destroyIt();
            }
        },

        rotateRight: function() {
            this.rotate(-90);
        },

        rotateLeft: function() {
            this.rotate(90);
        },

        rotate: function(deg) {
            var self = this;

            $.ajax({
                url: API + 'image',
                type: 'POST',
                data: {
                    id: self.model.get('id'),
                    rotate: deg,
                    bust: (new Date()).getTime()
                },
                success: function(ret) {
                    var t = (new Date()).getTime();

                    if (ret.ret === 0) {
                        // Force the image to reload.
                        var url = self.$('img').attr('src');
                        var p = url.indexOf('?');
                        url =  p === -1 ? (url + '?t=' + t) : (url + '&t' + t + '=' + t);
                        self.$('img').attr('src', url);
                    }
                }
            })
        },

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
                self.$('.js-photos').empty();
                self.collection.each(function(att) {
                    if (self.options.message) {
                        // We might not have one, e.g. when posting.
                        att.set('subject', self.options.message.get('subject'));
                        att.set('mine', self.options.message.get('mine'));
                    }

                    var v = new Iznik.Views.User.Message.Photo({
                        model: att,
                        message: self.options.message,
                        collection: self.collection
                    });
                    v.render().then(function() {
                        self.$('.js-photos').append(v.$el);
                    });

                    self.photos.push(v.$el);

                    if (!self.options.showAll) {
                        if (self.photos.length > 1) {
                            v.$el.hide();
                        } else {
                            self.currentPhoto = v.$el;
                        }
                    }
                });

                if (!self.options.showAll) {
                    if (self.photos.length > 1) {
                        _.delay(_.bind(self.nextPhoto, self), 10000);
                    }
                }
            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Reply = Iznik.View.Timeago.extend({
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
                ChatHolder().fetchAndRestore(chat.id);
            });
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

        gotChat: function() {
            var self = this;
            self.model.set('chat', self.chat.toJSON2());
            self.model.set('unseen', self.chat.get('unseen'));

            Iznik.View.prototype.render.call(self).then(function(self) {
                // If the number of unseen messages in this chat changes, update this view so that the count is
                // displayed here.
                self.listenToOnce(self.chat, 'change:unseen', self.render);
                p = Iznik.View.Timeago.prototype.render.call(self);

                // We might promise to this person from a chat.
                self.listenTo(self.chat, 'promised', _.bind(self.chatPromised, self));
            });
        },

        render: function() {
            var self = this;

            self.model.set('me', Iznik.Session.get('me'));
            self.model.set('message', self.options.message.toJSON2());

            var chat = Iznik.Session.chats.get({
                id: self.model.get('chatid')
            });

            var p;

            // We might not find this chat, most commonly if we've not yet fetched it from the server and it's in
            // our cache.  If not, fetch it.
            if (!_.isUndefined(chat)) {
                self.chat = chat;
                p = resolvedPromise(self);
            } else {
                self.chat = new Iznik.Models.Chat.Room({
                    id: self.model.get('chatid')
                });

                p = self.chat.fetch();
            }

            p.then(_.bind(self.gotChat, self));

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
                self.options.offers.each(function(offer) {
                    self.$('.js-offers').append('<option value="' + offer.get('id') + '" />');
                    self.$('.js-offers option:last').html(offer.get('subject'));
                });

                var msg = self.model.get('message');
                if (msg) {
                    self.$('.js-offers').val(msg.id);
                }
            });

            return(p);
        }
    });

    Iznik.Views.User.Message.Replyable = Iznik.Views.User.Message.extend({
        template: 'user_message_replyable',

        triggerRender: true,

        events: {
            'click .js-send': 'send',
            'click .js-profile': 'showProfile',
            'click .js-mapzoom': 'mapZoom'
        },

        initialize: function(){
            this.events = _.extend(this.events, Iznik.Views.User.Message.prototype.events);
        },

        showProfile: function(e) {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(self.model.get('fromuser'))
                });

                v.render();
            });

            e.preventDefault();
            e.stopPropagation();
        },

        showMap: function() {
            var self = this;
            var loc = null;

            if (self.model.get('location')) {
                loc = self.model.get('location');
            } else if (self.model.get('area')) {
                loc = self.model.get('area');
            }

            if (loc) {
                self.$('.js-mapzoom .js-map').attr('src', "https://maps.google.com/maps/api/staticmap?size=110x110&zoom=" + self.model.get('mapzoom') + "&center=" + loc.lat + "," + loc.lng + "&maptype=roadmap&markers=icon:" + self.model.get('mapicon') + "|" + loc.lat + "," + loc.lng + "&sensor=false&key=AIzaSyCdTSJKGWJUOx2pq1Y0f5in5g4kKAO5dgg");
                self.$('.js-mapzoom').show();
            }
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
                                self.wait.close();
                                self.$('.js-replybox').slideUp();

                                require(['iznik/views/chat/chat'], function(ChatHolder) {
                                    ChatHolder().fetchAndRestore(chatid);
                                });

                                // If we were replying, we might have forced a login and shown the message in
                                // isolation, in which case we need to return to where we were.  But fetch
                                // the chat messages first, as otherwise we might have a cached version which
                                // doesn't have our latest one in it which we then display.
                                try {
                                    var messages = new Iznik.Collections.Chat.Messages({
                                        roomid: chatid
                                    });
                                    messages.fetch({
                                        remove: true
                                    }).then(function () {
                                        var ret = Storage.get('replyreturn');
                                        console.log("Return after reply", ret);

                                        if (ret) {
                                            Storage.remove('replyreturn');
                                            Router.navigate(ret, true);
                                        }
                                    });
                                } catch (e) {};
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
                        // Remove local storage so that we don't get stuck sending the same message, for example if we reload the
                        // page.
                        try {
                            Storage.remove('replyto');
                            Storage.remove('replytext');
                        } catch (e) {}

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
                            self.startChat();
                        }
                    } else {
                        // We are not logged in, and will have to do so.  This may result in a page reload - so save
                        // off details of our reply in local storage.
                        try {
                            Storage.set('replyto', self.model.get('id'));
                            Storage.set('replytext', replytext);
                            Storage.set('replyreturn', Backbone.history.getFragment());
                        } catch (e) {
                            console.error("Failed to set up for reply", e.message);
                        }

                        // Set the route to the individual message.  This will spot the local storage, force us to
                        // log in, and then send it.  This also means that when the page is reloaded because of a login,
                        // we don't have issues with not seeing/needing to scroll to the message of interest.
                        //
                        // We might already be on this page, so we can't always call navigate as usual.
                        var url = '/message/' + self.model.get('id');
                        console.log("Compare url", url, Backbone.history.getFragment());
                        if ('/' + Backbone.history.getFragment() == url) {
                            Backbone.history.loadUrl(url);
                        } else {
                            Router.navigate(url, {
                                trigger: true
                            });
                        }
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
                    mylocation = Storage.get('mylocation');

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
                    var matched = self.model.get('matchedon');
                    if (matched) {
                        self.$('.js-subject').html(self.wordify(self.model.get('subject')));
                        self.$('.js-subject span').each(function () {
                            if ($(this).html().toLowerCase().indexOf(matched.word) != -1) {
                                $(this).addClass('searchmatch');
                                $(this).prop('title', 'Match type: ' + matched.type);
                            }
                        });
                    }

                    if (self.model.get('mine')) {
                        // Stop people replying to their own messages.
                        self.$('.panel-footer').hide();
                    } else {
                        // We might have been trying to reply.
                        try {
                            var replyto = Storage.get('replyto');
                            var replytext = Storage.get('replytext');
                            var thisid = self.model.get('id');

                            if (replyto == thisid) {
                                self.continueReply.call(self, replytext);
                            }
                        } catch (e) {console.log("Failed", e)}
                    }

                    self.$el.css('visibility', 'visible');

                    // Show the map on expand.  This reduces costs
                    self.$('.panel').on('shown.bs.collapse', function() {
                        self.showMap();
                    });

                    self.clipboard = new Clipboard('#js-clip-' + self.model.id, {
                        text: _.bind(function() {
                            var url = this.model.get('url');
                            return url;
                        }, self)
                    });

                    self.clipboard.on('success', function(e) {
                        ABTestAction('messagebutton', 'Copy Link');
                        self.close();
                    });
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