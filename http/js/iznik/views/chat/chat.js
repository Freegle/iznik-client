define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'iznik/models/chat/chat',
    'jquery-resizable',
    'jquery-visibility'
], function($, _, Backbone, Iznik, autosize) {
    // This is a singleton view.
    var instance;

    Iznik.Views.Chat.Holder = Iznik.View.extend({
        template: 'chat_holder',

        id: "chatHolder",

        bulkUpdateRunning: false,

        tabActive: true,

        minimiseall: function() {
            Iznik.activeChats.viewManager.each(function(chat) {
                chat.minimise();
            });
        },

        allseen: function() {
            console.log("allseen");
            Iznik.minimisedChats.viewManager.each(function(chat) {
                chat.allseen();
            });
        },

        waitError: function () {
            // This can validly happen when we switch pages, because we abort outstanding requests
            // and hence our long poll.  So before restarting, check that this view is still in the
            // DOM.
            console.log("Wait error", this);
            if (this.inDOM()) {
                // Probably a network glitch.  Retry later.
                this.wait();
            } else {
                this.destroyIt();
            }
        },

        wait: function() {
            // We have a long poll open to the server, which when it completes may prompt us to do some work on a
            // chat.  That way we get zippy messaging.
            //
            // TODO use a separate domain name to get round client-side limits on the max number of HTTP connections
            // to a single host.  We use a single connection rather than a per chat one for the same reason.
            var self = this;

            if (self.inDOM()) {
                // This view is still in the DOM.  If not, then we need to die.
                var me = Iznik.Session.get('me');
                var myid = me ? me.id : null;

                if (!myid) {
                    // Not logged in, try later;
                    _.delay(self.wait, 5000);
                } else {
                    var chathost = $('meta[name=iznikchat]').attr("content");

                    $.ajax({
                        url: window.location.protocol + '//' + chathost + '/subscribe/' + myid,
                        global: false, // don't trigger ajaxStart
                        success: function (ret) {
                            var waiting = false;
                            //console.log("Received notif", ret);
                            if (ret && ret.hasOwnProperty('text')) {
                                var data = ret.text;

                                if (data) {
                                    if (data.hasOwnProperty('newroom')) {
                                        // We have been notified that we are now in a new chat.  Pick it up.
                                        Iznik.Session.chats.fetch({
                                            modtools: self.options.modtools
                                        }).then(function() {
                                            // Now that we have the chat, update our status in it.
                                            var chat = Iznik.Session.chats.get(data.newroom);

                                            // If the unread message count changes in the new chat, we want to update.
                                            self.listenTo(chat, 'change:unseen', self.updateCounts);
                                            self.updateCounts();

                                            if (chat) {
                                                var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                                                chatView.updateRoster(chatView.statusWithOverride('Online'), chatView.noop);
                                            }

                                            Iznik.Session.chats.trigger('newroom', data.newroom);
                                        });
                                    } else if (data.hasOwnProperty('roomid')) {
                                        // Activity on this room.  If the chat is active, then we refetch the mesages
                                        // within it so that they are displayed.  If it's not, then we don't want
                                        // to keep fetching messages - the notification count will get updated by
                                        // the roster poll.
                                        Iznik.Session.chats.fetch({
                                            modtools: self.options.modtools
                                        }).then(function() {
                                            var chat = Iznik.Session.chats.get(data.roomid);

                                            // It's possible that we haven't yet fetched the model for this chat.
                                            if (chat) {
                                                var chatView = Iznik.activeChats.viewManager.findByModel(chat);

                                                if (!chatView.minimised) {
                                                    waiting = true;
                                                    chatView.messages.fetch().then(function () {
                                                        // Wait for the next one.  Slight timing window here but the fallback
                                                        // protects us from losing messages forever.
                                                        self.wait();

                                                        // Also fetch the chat, because the number of unread messages in it will
                                                        // update counts in various places.
                                                        chat.fetch().then(function () {
                                                        });
                                                    });
                                                }
                                            }
                                        });
                                    }
                                }
                            }

                            if (!waiting) {
                                self.wait();
                            }
                        }, error: _.bind(self.waitError, self)
                    });
                }
            } else {
                self.destroyIt();
            }
        },
        
        fallbackInterval: 30000,

        fallback: function() {
            // Although we should be notified of new chat messages via the wait() function, this isn't guaranteed.  So
            // we have a fallback poll to pick up any lost messages.
            //
            // Don't want to fetch them all in a single blat, though, as that is mean to the server.
            var self = this;
            self.fallbackFetch = [];
            var delay = 3000;

            if (self.inDOM()) {
                Iznik.Session.chats.fetch({
                    modtools: self.options.modtools
                }).then(function() {
                    self.updateCounts();

                    // For some reason we don't quite understand yet, the element can get detached so make sure it's
                    // there.
                    var el = Iznik.minimisedChats.$el;
                    if (el.closest('body').length == 0) {
                        console.log("Chats detached");
                        self.createMinimised();
                    }

                    Iznik.minimisedChats.render();

                    var i = 0;

                    (function fallbackOne() {
                        if (i < Iznik.Session.chats.length) {
                            Iznik.Session.chats.at(i).fetch();
                            i++;
                            _.delay(fallbackOne, delay);
                        } else {
                            // Reached end.
                            _.delay(_.bind(self.fallback, self), self.fallbackInterval);
                        }
                    })();
                });
            } else {
                self.destroyIt();
            }
        },

        bulkUpdateRoster: function() {
            var self = this;

            if (self.tabActive) {
                var updates = [];
                Iznik.Session.chats.each(function (chat) {
                    var status = chat.get('rosterstatus');

                    if (status && status != 'Away') {
                        // There's no real need to tell the server that we're in Away status - it will time us out into
                        // that anyway.  This saves a lot of update calls in the case where we're loading the page
                        // and minimising many chats, e.g. if we're a mod on many groups.
                        updates.push({
                            id: chat.get('id'),
                            status: status,
                            lastmsgseen: chat.get('lastmsgseen')
                        });
                    }
                });

                if (updates.length > 0) {
                    $.ajax({
                        url: API + 'chatrooms',
                        type: 'POST',
                        data: {
                            'rosters': updates
                        }, success: function (ret) {
                            // Update the returned roster into each active chat.
                            Iznik.activeChats.viewManager.each(function (chat) {
                                var roster = ret.rosters[chat.model.get('id')];
                                if (!_.isUndefined(roster)) {
                                    chat.lastRoster = roster;
                                }
                            });
                        }, complete: function () {
                            _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                        }
                    });
                }
            } else {
                _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
            }
        },

        organise: function() {
            // This organises our chat windows so that:
            // - they're at the bottom, padded at the top to ensure that
            // - they're not wider or taller than the space we have.
            //
            // The code is a bit complex
            // - partly because the algorithm is a bit complicated
            // - partly because for performance reasons we need to avoid using methods like width(), which are
            //   expensive, and use the CSS properties instead - which aren't, but which are returned with a
            //   px we need to trim.
            //
            // This approach speeds up this function by at least a factor of ten.
            var self = this;
            var start = (new Date()).getMilliseconds();
            var minimised = 0;
            var totalOuter = 0;
            var totalWidth = 0;
            var totalMax = 0;
            var maxHeight = 0;
            var minHeight = 1000;

            var windowInnerHeight = $(window).innerHeight();
            var navbarOuterHeight = $('.navbar').outerHeight();

            if (Iznik.activeChats) {
                Iznik.activeChats.viewManager.each(function(chat) {
                    if (chat.minimised) {
                        // Not much to do - either just count, or create if we're asked to.
                        minimised++;
                    } else {
                        // We can get the properties we're interested in with a single call, which is quicker.  This also
                        // allows us to remove the px crud.
                        var cssorig = chat.$el.css(['height', 'width', 'margin-left', 'margin-right', 'margin-top']);
                        var css = [];

                        // Remove the px and make sure they're ints.
                        _.each(cssorig, function(val, prop) {
                            css[prop] = parseInt(val.replace('px', ''));
                        });

                        // We use this later to see if we need to shrink.
                        totalOuter += css.width + css['margin-left'] + css['margin-right'];
                        // console.log("Chat width", css.width, css['margin-left'], css['margin-right']);
                        totalWidth += css.width;
                        totalMax++;

                        // Make sure it's not stupidly tall or short.  We let the navbar show unless we're really short,
                        // which happens when on-screen keyboards open up.
                        // console.log("Consider height", css.height, windowInnerHeight, navbarOuterHeight, windowInnerHeight - navbarOuterHeight - 5);
                        height = Math.min(css.height, windowInnerHeight - (isVeryShort() ? 0 : navbarOuterHeight) - 5);
                        // console.log("Consider shortness", height, css.height, windowInnerHeight, isVeryShort() ? 0 : navbarOuterHeight, navbarOuterHeight);
                        height = Math.max(height, 100);
                        maxHeight = Math.max(height, maxHeight);
                        // console.log("Height", height, css.height, windowInnerHeight, navbarOuterHeight);

                        if (css.height != height) {
                            css.height = height;
                            chat.$el.css('height', height.toString() + 'px');
                        }
                    }
                });

                // console.log("Checked height", (new Date()).getMilliseconds() - start);

                var max = window.innerWidth - 50;

                // console.log("Consider width", totalOuter, max);

                if (totalOuter > max) {
                    // The chat windows we have open are too wide.  Make them narrower.
                    var reduceby = Math.round((totalOuter - max) / totalMax + 0.5);
                    // console.log("Chats too wide", max, totalOuter, totalWidth, reduceby);
                    var width = (Math.floor(totalWidth / totalMax + 0.5) - reduceby);
                    // console.log("New width", width);

                    Iznik.activeChats.viewManager.each(function(chat) {
                        if (!chat.minimised) {
                            if (chat.$el.css('width') != width) {
                                // console.log("Set new width ", chat.$el.css('width'), width);
                                chat.$el.css('width', width.toString() + 'px');
                            }
                        }
                    });
                }

                // console.log("Checked width", (new Date()).getMilliseconds() - start);
                // console.log("Got max height", (new Date()).getMilliseconds() - start);

                // Now consider changing the margins on the top to ensure the chat window is at the bottom of the
                // screen.
                Iznik.activeChats.viewManager.each(function(chat) {
                    if (!chat.minimised) {
                        var height = parseInt(chat.$el.css('height').replace('px', ''));
                        var newmargin = (maxHeight - height).toString() + 'px';
                        // console.log("Checked margin", (new Date()).getMilliseconds() - start);
                        // console.log("Consider new margin", maxHeight, height, chat.$el.css('height'), chat.$el.css('margin-top'), newmargin);

                        if (chat.$el.css('margin-top') != newmargin) {
                            chat.$el.css('margin-top', newmargin);
                        }
                    }
                });
            } else {
                console.log("No chats to organise");
            }
            
            // The drop-down menu needs to be scrollable, and so we put a max-height on it.
            $('#notifchatdropdown').css('max-height', windowInnerHeight - navbarOuterHeight);

            // console.log("Organised", (new Date()).getMilliseconds() - start);
        },

        updateCounts: function() {
            var self = this;
            var unseen = 0;
            if (Iznik.activeChats) {
                Iznik.Session.chats.each(function(chat) {
                    var chatView = Iznik.activeChats.viewManager.findByModel(chat);
                    unseen += chat.get('unseen');
                });
            }

            // We'll adjust the count in the window title.
            var title = document.title;
            var match = /\(.*\) (.*)/.exec(title);
            title = match ? match[1] : title;

            if (unseen > 0) {
                $('#dropdownmenu .js-totalcount').html(unseen).show();
                $('#js-notifchat .js-totalcount').html(unseen).show();
                document.title = '(' + unseen + ') ' + title;
            } else {
                $('#dropdownmenu .js-totalcount').html(unseen).hide();
                $('#js-notifchat .js-totalcount').html(unseen).hide();
                document.title = title;
            }

            this.showMin();
        },

        reportPerson: function(groupid, chatid, reason, message) {
            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    chattype: 'User2Mod',
                    groupid: groupid
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        Iznik.Session.chats.fetch({
                            modtools: self.options.modtools,
                            remove: false
                        }).then(function() {
                            // Now create a report message.
                            var msg = new Iznik.Models.Chat.Message({
                                roomid: ret.id,
                                message: message,
                                reportreason: reason,
                                refchatid: chatid
                            });
                            msg.save().then(function() {
                                // Now open the chat so that the user sees what's happening.
                                var chatmodel = Iznik.Session.chats.get(ret.id);
                                var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                chatView.restore();
                            });
                        });
                    }
                }
            });
        },

        openChatToMods: function(groupid) {
            var self = this;

            $.ajax({
                type: 'PUT',
                url: API + 'chat/rooms',
                data: {
                    chattype: 'User2Mod',
                    groupid: groupid
                }, success: function(ret) {
                    if (ret.ret == 0) {
                        Iznik.Session.chats.fetch({
                            modtools: self.options.modtools,
                            remove: false
                        }).then(function() {
                            // Defer to give the CollectionView time to respond.
                            _.defer(function() {
                                var chatmodel = Iznik.Session.chats.get(ret.id);
                                var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                chatView.restore();
                            })
                        });
                    }
                }
            });
        },

        openChat: function(userid) {
            var self = this;

            var v = new Iznik.Views.PleaseWait({
                label: 'chat openChat'
            });
            v.render();

            if (userid != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.
                $.ajax({
                    type: 'PUT',
                    url: API + 'chat/rooms',
                    data: {
                        userid: userid
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            Iznik.Session.chats.fetch({
                                modtools: self.options.modtools,
                                remove: false
                            }).then(function() {
                                // Defer to give the CollectionView time to respond.
                                _.defer(function() {
                                    var chatmodel = Iznik.Session.chats.get(ret.id);
                                    var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                                    v.close();
                                    chatView.restore();
                                })
                            });
                        } else {
                            v.close();
                        }
                    }
                })
            }
        },

        showMin: function() {
            // No point showing the chat icon if we've nothing to show - will just encourage people to click
            // on something which won't do anything.
            if (Iznik.Session.chats.length > 0) {
                $('#js-notifchat').show();
            } else {
                $('#js-notifchat').hide();
            }
        },

        createMinimised: function() {
            var self = this;

            Iznik.minimisedChats = new Backbone.CollectionView({
                el: $('#notifchatdropdownlist'),
                modelView: Iznik.Views.Chat.Minimised,
                collection: Iznik.Session.chats,
                modelViewOptions: {
                    organise: _.bind(self.organise, self),
                    updateCounts: _.bind(self.updateCounts, self),
                    modtools: self.options.modtools
                }
            });

            Iznik.minimisedChats.on('add', function(view) {
                // The collection view seems to get messed up, so re-render it to sort it out.
                Iznik.minimisedChats.render();
            });

            Iznik.minimisedChats.render();
        },

        render: function() {
            var self = this;
            var p;

            // We might already be rendered, as we're outside the body content that gets zapped when we move from
            // page to page.
            if ($('#chatHolder').length == 0) {
                self.$el.css('visibility', 'hidden');

                Iznik.Session.chats = new Iznik.Collections.Chat.Rooms({
                    modtools: Iznik.Session.get('modtools')
                });

                p = Iznik.View.prototype.render.call(self).then(function(self) {
                    $("#bodyEnvelope").append(self.$el);
                    Iznik.Session.chats.fetch({
                        modtools: Iznik.Session.get('modtools')
                    }).then(function () {
                        Iznik.Session.chats.each(function (chat) {
                            // If the unread message count changes, we want to update it.
                            self.listenTo(chat, 'change:unseen', self.updateCounts);
                        });

                        Iznik.activeChats = new Backbone.CollectionView({
                            el: self.$('.js-chats'),
                            modelView: Iznik.Views.Chat.Active,
                            collection: Iznik.Session.chats,
                            modelViewOptions: {
                                organise: _.bind(self.organise, self),
                                updateCounts: _.bind(self.updateCounts, self),
                                modtools: self.options.modtools
                            }
                        });

                        Iznik.activeChats.render();

                        self.waitDOM(self, function() {
                            self.createMinimised();
                            Iznik.Session.trigger('chatsfetched');
                            self.organise();
                            self.showMin();
                        });
                    });

                    // Not within this DOM.
                    $('.js-minimiseall').on('click', self.minimiseall);
                    $('.js-allseen').on('click', self.allseen);

                    if (!self.bulkUpdateRunning) {
                        // We update the roster for all chats periodically.
                        self.bulkUpdateRunning = true;
                        _.delay(_.bind(self.bulkUpdateRoster, self), 25000);
                    }

                    // Now ensure we are told about new messages.
                    self.wait();
                    _.delay(_.bind(self.fallback, self), self.fallbackInterval);
                });

                $(document).on('hide', function () {
                    self.tabActive = false;
                });

                $(document).on('show', function () {
                    self.tabActive = true;
                });
            } else {
                p = resolvedPromise(self);
            }

            return(p);
        }
    });

    Iznik.Views.Chat.Minimised = Iznik.View.Timeago.extend({
        template: 'chat_minimised',

        tagName: 'li',

        className: 'clickme padleftsm',

        events: {
            'click': 'click'
        },

        click: function() {
            // The maximised chat view is listening on this.
            this.model.trigger('restore', this.model.get('id'));
        },

        allseen: function() {
            var self = this;
            
            if (self.model.get('unseen') > 0) {
                // We have to get the messages to find out which the last one is.
                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });
                self.messages.fetch().then(function() {
                    console.log("Fetched", self.messages);
                    if (self.messages.length > 0) {
                        var lastmsgseen = self.messages.at(self.messages.length - 1).get('id');
                        console.log("Last seen", lastmsgseen);
                        $.ajax({
                            url: API + 'chat/rooms/' + self.model.get('id'),
                            type: 'POST',
                            data: {
                                lastmsgseen: lastmsgseen,
                                status: 'Away'
                            }
                        });
                    
                        self.model.set('unseen', 0);
                        self.model.set('lastmsgseen', lastmsgseen);
                    }
                });                
            }
        },

        updateCount: function() {
            var self = this;
            var unseen = self.model.get('unseen');

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();
            } else {
                self.$('.js-count').html(unseen).hide();
            }

            self.trigger('countupdated', unseen);
        },

        render: function() {
            var p = Iznik.View.Timeago.prototype.render.call(this);
            p.then(function(self) {
                self.updateCount();

                // If the unread message count changes, we want to update it.
                self.listenTo(self.model, 'change:unseen', self.updateCount);
            });

            return(p);
        }
    });

    Iznik.Views.Chat.Active = Iznik.View.extend({
        template: 'chat_active',

        tagName: 'li',

        className: 'chat-window nopad nomarginleft nomarginbot nomarginright col-xs-4 col-md-3 col-lg-2',

        events: {
            'click .js-remove, touchstart .js-remove': 'removeIt',
            'click .js-minimise, touchstart .js-minimise': 'minimise',
            'click .js-report, touchstart .js-report': 'report',
            'focus .js-message': 'messageFocus',
            'click .js-promise': 'promise',
            'click .js-send': 'send',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status'
        },

        removed: false,

        minimised: true,

        keyUp: function(e) {
            var self = this;
            if (e.which === 13 && e.altKey) {
                this.$('.js-message').val(this.$('.js-message').val() + "\n");
            } else if (e.which === 13) {
                this.send();
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        },

        send: function() {
            var self = this;
            var message = this.$('.js-message').val();
            if (message.length > 0) {
                // We get called back when the message has actually been sent to the server.
                self.listenToOnce(this.model, 'sent', function() {
                    // Get the full set of messages back.  This will replace any temporary
                    // messages added, and also ensure we don't miss any that arrived while we
                    // were sending ours.
                    self.messages.fetch({
                        remove: true
                    }).then(function() {
                        self.options.updateCounts();
                        self.scrollBottom();
                    });
                });

                self.model.send(message);

                // Create another model with a fake id and add it to the collection.  This will populate our view
                // views while we do the real save in the background.  Makes us look fast.
                var tempmod = new Iznik.Models.Chat.Message({
                    id: self.messages.last().get('id') + 1,
                    chatid: self.model.get('id'),
                    message: message,
                    date: (new Date()).toISOString(),
                    sameaslast: true,
                    sameasnext: false,
                    seenbyall: 0,
                    type: 'Default',
                    user: Iznik.Session.get('me')
                });

                self.messages.add(tempmod);

                // We have initiated the send, so set up for the next one.
                self.$('.js-message').val('');
                self.$('.js-message').focus();
                self.messageFocus();

                // If we've grown the textarea, shrink it.
                self.$('textarea').css('height', '');
            }
        },

        lsID: function() {
            return('chat-' + this.model.get('id'));
        },

        zapViews: function() {
            Iznik.Session.chats.remove({
                id: this.model.get('id')
            });
        },

        removeIt: function(e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'chat_remove';

            self.listenToOnce(v, 'confirmed', function () {
                // This will close the chat, which means it won't show in our list until we recreate it.  The messages
                // will be preserved.
                self.removed = true;
                self.$el.hide();
                self.updateRoster('Closed', _.bind(self.zapViews, self), true);
            });

            v.render();

        },

        focus: function() {
            this.$('.js-message').click();
        },

        noop: function() {

        },

        promise: function() {
            // Promise a message to someone.
            var self = this;

            // Get our offers.
            self.offers = new Iznik.Collections.Message(null, {
                collection: 'Approved',
                modtools: false
            });

            self.offers.fetch({
                data: {
                    fromuser: Iznik.Session.get('me').id,
                    types: ['Offer'],
                    limit: 100
                }
            }).then(function() {
                if (self.offers.length > 0) {
                    // The message we want to suggest as the one to promise is any last message mentioned in this chat.
                    var msgid = _.last(self.model.get('refmsgids'));

                    var msg = null;
                    self.offers.each(function(offer) {
                        if (offer.get('id') == msgid) {
                            msg = offer;
                        }
                    });

                    var v = new Iznik.Views.User.Message.Promise({
                        model: new Iznik.Model({
                            message: msg ? msg.toJSON2() : null,
                            user: self.model.get('user1').id != Iznik.Session.get('me').id ?
                                self.model.get('user1'): self.model.get('user2')
                        }),
                        offers: self.offers
                    });

                    self.listenToOnce(v, 'promised', function() {
                        msg.fetch();
                        self.model.trigger('promised');
                    });

                    v.render();
                }
            });
        },

        allseen: function() {
            if (this.messages.length > 0) {
                this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
                // console.log("Now seen chat message", this.messages.at(this.messages.length - 1).get('id'));
            }
            this.model.set('unseen', 0);
        },

        messageFocus: function() {
            var self = this;

            // We've seen all the messages.
            self.allseen();

            // Tell the server now, in case they navigate away before the next roster timer.
            self.updateRoster(self.statusWithOverride('Online'), self.noop, true);

            // New messages are in bold - keep them so for a few seconds, to make it easy to see new stuff,
            // then revert.
            _.delay(function() {
                self.$('.chat-message-unseen').removeClass('chat-message-unseen');
            }, 5000)
            this.updateCount();
        },

        minimise: function() {
            var self = this;
            _.defer(function() {
                self.$el.hide();
            });
            this.minimised = true;
            this.waitDOM(self, self.options.organise);
            this.options.updateCounts();

            self.updateRoster('Away', self.noop);

            try {
                localStorage.setItem(this.lsID() + '-minimised', 1);
            } catch (e) { console.error(e.message)};

            this.trigger('minimised');
        },

        report: function() {
            var groups = Iznik.Session.get('groups');

            if (groups.length > 0) {
                // We only take reports from a group member, so that we have somewhere to send it.
                // TODO Give an error or pass to support?
                (new Iznik.Views.Chat.Report({
                    chatid: this.model.get('id')
                })).render();
            }
        },

        adjust: function() {
            var self = this;
            // The text area shouldn't grow too high, but should go above a single line if there's room.
            var maxinpheight = self.$el.innerHeight() - this.$('.js-chatheader').outerHeight();
            var mininpheight = Math.round(self.$el.innerHeight() * .15);
            self.$('textarea').css('max-height', maxinpheight);
            self.$('textarea').css('min-height', mininpheight);

            var newHeight = this.$el.innerHeight() - this.$('.js-chatheader').outerHeight() - this.$('.js-chatfooter').outerHeight() - this.$('.js-modwarning').outerHeight() - 20 ;
            // console.log("Height", newHeight, this.$el.innerHeight() ,this.$('.js-chatheader'), this.$('.js-chatheader').outerHeight() , this.$('.js-chatfooter input').outerHeight());
            this.$('.js-leftpanel, .js-roster').height(newHeight);

            var width = self.$el.width();

            if (self.model.get('chattype') == 'Group') {
                // Group chats have a roster.
                var lpwidth = self.$('.js-leftpanel').width();
                lpwidth = self.$el.width() - 60 < lpwidth ? (width - 60) : lpwidth;
                lpwidth = Math.max(self.$el.width() - 250, lpwidth);
                self.$('.js-leftpanel').width(lpwidth);
            } else {
                // Others
                self.$('.js-leftpanel').width('100%');
            }

            self.checkSmall(width);
        },

        checkSmall: function(width) {
            if (width < 640) {
                this.$el.addClass('chatsmall');
            } else {
                this.$el.removeClass('chatsmall');
            }
        },

        setSize: function() {
            var self = this;

            try {
                // Restore any saved height
                //
                // On mobile we maximise the chat window, as the whole resizing thing is too fiddly.
                var height = localStorage.getItem('chat-' + self.model.get('id') + '-height');
                var width = localStorage.getItem('chat-' + self.model.get('id') + '-width');
                // console.log("Narrow?", isNarrow(), $(window).innerWidth());
                if (isNarrow()) {
                    // Just maximise it.
                    width = $(window).innerWidth();
                }

                // console.log("Short?", isShort(), $(window).innerHeight(), $('.navbar').outerHeight(), $('#js-notifchat').outerHeight());
                if (isShort()) {
                    // Maximise it.
                    height = $(window).innerHeight();
                }

                if (height && width) {
                    // console.log("Set size", width, height);
                    self.$el.height(height);
                    self.$el.width(width);
                    self.checkSmall(width);
                }

                var lpwidth = localStorage.getItem('chat-' + self.model.get('id') + '-lp');
                lpwidth = self.$el.width() - 60 < lpwidth ? (self.$el.width() - 60) : lpwidth;

                if (lpwidth) {
                    // console.log("Restore chat width to", lpwidth);
                    self.$('.js-leftpanel').width(lpwidth);
                }
            } catch (e) {}
        },

        restore: function(large) {
            var self = this;
            self.minimised = false;

            // Hide the chat list if it's open.
            $('#notifchatdropdown').hide();

            if (large) {
                // We want a larger and more prominent chat.
                try {
                    localStorage.setItem(this.lsID() + '-height', Math.floor(window.innerHeight * 2 / 3));
                    localStorage.setItem(this.lsID() + '-width', Math.floor(window.innerWidth * 2 / 3));
                } catch (e) {}
            }

            // Restore the window first, so it feels zippier.
            self.setSize();
            this.waitDOM(self, self.options.organise);
            this.options.updateCounts();

            _.defer(function() {
                self.$el.css('visibility', 'visible');
                self.$el.show();
                self.adjust();
            });

            self.updateRoster(self.statusWithOverride('Online'), self.noop);

            try {
                localStorage.setItem(self.lsID() + '-minimised', 0);
            } catch (e) {
            }

            // We fetch the messages when restoring - no need before then.
            var v = new Iznik.Views.PleaseWait({
                label: 'chat restore'
            });
            v.render();
            self.messages.fetch().then(function() {
                // We've just opened this chat - so we have had a decent chance to see any unread messages.
                v.close();
                self.messageFocus();
                self.scrollBottom();
                self.trigger('restored');
            });
        },

        scrollTimer: null,
        scrollTo: 0,
        
        scrollBottom: function() {
            // Tried using .animate(), but it seems to be too expensive for the browser, so leave that for now.
            var self = this;
            var msglist = self.$('.js-messages');

            if (msglist.length > 0) {
                var height = msglist[0].scrollHeight;

                if (self.scrollTimer && self.scrollTo < height) {
                    // We have a timer outstanding to scroll to somewhere less far down that we now want to.  No point
                    // in doing that.
                    // console.log("Clear old scroll timer",  self.model.get('id'), self.scrollTo, height);
                    clearTimeout(self.scrollTimer);
                    self.scrollTimer = null;
                }

                // We want to scroll immediately, and add a fallback a few seconds later for when things haven't quite
                // finished rendering yet.
                msglist.scrollTop(height);
                // console.log("Scroll now to ", self.model.get('id'), height);

                if (!self.scrollTimer) {
                    // We don't already have a fallback scroll running.
                    self.scrollTo = height;
                    self.scrollTimer = setTimeout(_.bind(function() {
                        // console.log("Scroll later", this);
                        var msglist = this.$('.js-messages');
                        var height = msglist[0].scrollHeight;
                        msglist.scrollTop(height);
                        // console.log("Scroll later to ", this.model.get('id'), height);
                    }, self), 5000);
                }
            }
        },

        dragend: function(event, el, opt) {
            var self = this;

            this.options.organise();
            self.trigger('resized');
            self.adjust();
            self.scrollBottom();

            // Save the new height to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-height', self.$el.height());
                localStorage.setItem(this.lsID() + '-width', self.$el.width());
            } catch (e) {}
        },

        drag: function(event, el, opt) {
            var now = (new Date()).getMilliseconds();

            // We don't want to allow the resize 

            if (now - this.lastdrag > 20) {
                // We will need to remargin any other chats.  Don't do this too often as it makes dragging laggy.
                this.options.organise();
            }

            this.lastdrag = (new Date()).getMilliseconds();

        },

        panelSize: function(event, el, opt) {
            var self = this;

            // Save the new left panel width to local storage so that we can restore it next time.
            try {
                localStorage.setItem(this.lsID() + '-lp', self.$('.js-leftpanel').width());
            } catch (e) {}

            self.adjust();
            self.scrollBottom();
        },

        status: function() {
            // We can override appearing online to show something else.
            var status = this.$('.js-status').val();
            try {
                localStorage.setItem('mystatus', status);
            } catch (e) {}

            this.updateRoster(status, this.noop);
        },

        updateRoster: function(status, callback, force) {
            var self = this;
            // console.log("Update roster", self.model.get('id'), status, force);

            if (force) {
                // We want to make sure the server knows right now.
                $.ajax({
                    url: API + 'chat/rooms/' + self.model.get('id'),
                    type: 'POST',
                    data: {
                        lastmsgseen: self.model.get('lastmsgseen'),
                        status: status
                    }, success: function(ret) {
                        if (ret.ret === 0) {
                            self.lastRoster = ret.roster;
                        }

                        callback(ret);
                    }
                });
            } else {
                // Save the current status in the chat for the next bulk roster update to the server.
                // console.log("Set roster status", status, self.model.get('id'));
                self.model.set('rosterstatus', status);

               // console.log("Suppress update", self.lastRoster);
                callback({
                    ret: 0,
                    status: 'Update delayed',
                    roster: self.lastRoster
                });
            }
        },

        statusWithOverride: function(status) {
            if (status == 'Online') {
                // We are online, but may have overridden this to appear something else.
                try {
                    var savestatus = localStorage.getItem('mystatus');
                    status = savestatus ? savestatus : status;
                } catch (e) {}
            }

            return(status);
        },

        openChat: function(chatid) {
            Iznik.Session.chats.fetch({
                modtools: Iznik.Session.get('modtools')
            }).then(function() {
                var chatmodel = Iznik.Session.chats.get(chatid);
                var chatView = Iznik.activeChats.viewManager.findByModel(chatmodel);
                chatView.restore();
                chatView.focus();
            });
        },

        rosterUpdated: function(ret) {
            var self = this;

            if (ret.ret === 0) {
                self.$('.js-roster').empty();
                _.each(ret.roster, function(rost) {
                    var mod = new Iznik.Model(rost);
                    var v = new Iznik.Views.Chat.RosterEntry({
                        model: mod,
                        modtools: self.options.modtools
                    });
                    self.listenTo(v, 'openchat', self.openChat);
                    v.render().then(function(v) {
                        self.$('.js-roster').append(v.el);
                    })
                });

                self.model.set('unseen', ret.unseen);
            }

            _.delay(_.bind(self.roster, self), 30000);
        },

        roster: function() {
            // We update our presence and get the roster for the chat regularly if the chat is open.  If it's
            // minimised, we don't - the server will time us out as away.  We'll still; pick up any new messages on
            // minimised chats via the long poll, and the fallback.
            var self = this;

            if (!self.removed && !self.minimised) {
                self.updateRoster(self.statusWithOverride('Online'),
                    _.bind(self.rosterUpdated, self));
            }
        },

        updateCount: function() {
            var self = this;
            var unseen = self.model.get('unseen');
            // console.log("Update count", unseen);

            if (unseen > 0) {
                self.$('.js-count').html(unseen).show();

                if (self.messages) {
                    self.messages.fetch({
                        remove: true
                    });
                }
            } else {
                self.$('.js-count').html(unseen).hide();
            }

            self.trigger('countupdated', unseen);
        },

        render: function () {
            var self = this;
            // console.log("Render chat", self.model.get('id')); console.trace();

            self.$el.attr('id', 'chat-' + self.model.get('id'));
            self.$el.addClass('chat-' + self.model.get('name'));

            self.$el.css('visibility', 'hidden');

            self.messages = new Iznik.Collections.Chat.Messages({
                roomid: self.model.get('id')
            });

            var p = Iznik.View.prototype.render.call(self);
            p.then(function(self) {
                // Input text autosize
                autosize(self.$('textarea'));

                if (!self.options.modtools) {
                    self.$('.js-privacy').hide();
                } else {
                    self.$('.js-promise').hide();
                }

                try {
                    var status = localStorage.getItem('mystatus');

                    if (status) {
                        self.$('.js-status').val(status);
                    }
                } catch (e) {
                }

                self.updateCount();

                // If the unread message count changes, we want to update it.
                self.listenTo(self.model, 'change:unseen', self.updateCount);

                // If the window size changes, we will need to adapt.
                $(window).resize(function () {
                    self.setSize();
                    self.adjust();
                    self.options.organise();
                    self.scrollBottom();
                });

                var narrow = isNarrow();
                var minimise = true;

                try {
                    // On mobile we start them all minimised as there's not much room.
                    //
                    // Default to minimised, which is what we get if the key is missing and returns null.
                    var lsval = localStorage.getItem(self.lsID() + '-minimised');
                    lsval = lsval === null ? lsval : parseInt(lsval);

                    if (lsval === null || lsval || narrow) {
                        minimise = true;
                    } else {
                        minimise = false;
                    }
                } catch (e) {
                }

                self.messageViews = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages,
                    chatView: self,
                    modelViewOptions: {
                        chatView: self,
                        chatModel: self.model
                    }
                });

                // As new messages are added, we want to show them.  This also means when we first render, we'll
                // scroll down to the latest messages.
                self.listenTo(self.messageViews, 'add', function(modelView) {
                    self.listenToOnce(modelView, 'rendered', function() {
                        self.scrollBottom();
                        // _.delay(_.bind(self.scrollBottom, self), 5000);
                    });
                });

                self.messageViews.render();

                self.$el.resizable({
                    handleSelector: '#chat-' + self.model.get('id') + ' .js-grip',
                    resizeWidthFrom: 'left',
                    resizeHeightFrom: 'top',
                    onDrag: _.bind(self.drag, self),
                    onDragEnd: _.bind(self.dragend, self)
                });

                self.$(".js-leftpanel").resizable({
                    handleSelector: ".splitter",
                    resizeHeight: false,
                    onDragEnd: _.bind(self.panelSize, self)
                });

                minimise ? self.minimise() : self.restore();

                // The minimised chat can signal to us that we should restore.
                self.listenTo(self.model, 'restore', self.restore);

                self.trigger('rendered');

                // Get the roster to see who's there.
                self.roster();
            });

            return(p);
        }
    });

    Iznik.Views.Chat.Message = Iznik.View.extend({
        events: {
            'click .js-viewchat': 'viewChat'
        },

        viewChat: function() {
            var self = this;

            var chat = new Iznik.Models.Chat.Room({
                id: self.model.get('refchatid')
            });

            chat.fetch().then(function() {
                var v = new Iznik.Views.Chat.Modal({
                    model: chat
                });

                v.render();
            });
        },

        render: function() {
            var self = this;
            var p;

            if (this.model.get('id')) {
                var message = this.model.get('message');
                if (message) {
                    // Remove duplicate newlines.
                    message = message.replace(/\n\s*\n\s*\n/g, '\n\n');
                    
                    // Strip HTML tags
                    message = strip_tags(message, '<a>');

                    // Insert some wbrs to allow us to word break long words (e.g. URLs).
                    // It might have line breaks in if it comes originally from an email.
                    message = this.model.set('message', wbr(message, 20).replace(/(?:\r\n|\r|\n)/g, '<br />'));
                }

                var group = this.options.chatModel.get('group');
                var myid = Iznik.Session.get('me').id;
                this.model.set('group', group);
                this.model.set('myid', myid);

                // Decide if this message should be on the left or the right.
                //
                // For group messages, our messages are on the right.
                // For conversations:
                // - if we're one of the users then our messages are on the right
                // - otherwise user1 is on the left and user2 on the right.
                var userid = this.model.get('user').id;
                var u1 = this.options.chatModel.get('user1');
                var user1 = u1 ? u1.id : null;
                var u2 = this.options.chatModel.get('user1');
                var user2 = u2 ? u2.id : null;

                if (group) {
                    this.model.set('left', userid != myid);
                } else if (myid == user1 || myid == user2) {
                    this.model.set('left', userid != myid);
                } else {
                    this.model.set('left', userid == user1);
                }

                //console.log("Consider left", userid, myid, user1, user2, this.model.get('left'));

                this.model.set('lastmsgseen', this.options.chatModel.get('lastmsgseen'));

                // This could be a simple chat message, or something more complex.
                var tpl;

                switch (this.model.get('type')) {
                    case 'ModMail': tpl = this.model.get('refmsg') ? 'chat_modmail' : 'chat_message'; break;
                    case 'Interested': tpl = this.model.get('refmsg') ? 'chat_interested' : 'chat_message'; break;
                    case 'Promised': tpl = this.model.get('refmsg') ? 'chat_promised' : 'chat_message'; break;
                    case 'Reneged': tpl = this.model.get('refmsg') ? 'chat_reneged' : 'chat_message'; break;
                    case 'ReportedUser': tpl = 'chat_reported'; break;
                    default: tpl = 'chat_message'; break;
                }

                this.template = tpl;

                p = Iznik.View.prototype.render.call(this);
                p.then(function(self) {
                    if (self.model.get('type') == 'ModMail' && self.model.get('refmsg')) {
                        // ModMails may related to a message which has been rejected.  If so, add a button to
                        // edit and resend.
                        var msg = self.model.get('refmsg');
                        var groups = msg.groups;

                        _.each(groups, function(group) {
                            if (group.collection == 'Rejected') {
                                self.$('.js-rejected').show();
                            }
                        });
                    }
                    self.$('.timeago').timeago();
                    self.$('.timeago').show();
                    self.$el.fadeIn('slow');
                });
            } else {
                p = resolvedPromise(this);
            }

            return(p);
        }
    });

    Iznik.Views.Chat.Roster = Iznik.View.extend({
        template: 'chat_roster'
    });

    Iznik.Views.Chat.RosterEntry = Iznik.View.extend({
        template: 'chat_rosterentry',
        
        events: {
            'click .js-click': 'dm'
        },

        dm: function() {
            var self = this;

            if (self.model.get('id') != Iznik.Session.get('me').id) {
                // We want to open a direct message conversation with this user.
                $.ajax({
                    type: 'PUT',
                    url: API + 'chat/rooms',
                    data: {
                        userid: self.model.get('userid')
                    }, success: function(ret) {
                        if (ret.ret == 0) {
                            self.trigger('openchat', ret.id);
                        }
                    }
                })
            }
        }
    });

    Iznik.Views.Chat.Report = Iznik.Views.Modal.extend({
        template: 'chat_report',

        events: {
            'click .js-report': 'report'
        },

        report: function() {
            var self = this;
            var reason = self.$('.js-reason').val();
            var message = self.$('.js-message').val();
            var groupid = self.groupSelect.get();

            if (reason != '' && message != '') {
                instance.reportPerson(groupid, self.options.chatid, reason, message);
                self.close();
            }
        },

        render: function() {
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                var groups = Iznik.Session.get('groups');

                if (groups.length >= 0) {
                    self.groupSelect = new Iznik.Views.Group.Select({
                        systemWide: false,
                        all: false,
                        mod: false,
                        choose: true,
                        id: 'reportGroupSelect'
                    });

                    self.groupSelect.render().then(function() {
                        self.$('.js-groupselect').html(self.groupSelect.el);
                    });
                }
            });

            return (p);
        }
    });

    Iznik.Views.Chat.Modal = Iznik.Views.Modal.extend({
        template: 'chat_modal',

        render: function () {
            // Open a modal containing the chat messages.
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages,
                    modelViewOptions: {
                        chatModel: self.model
                    }
                });

                self.collectionView.render();
                self.messages.fetch({
                    remove: true
                });
            });

            return (p);
        }
    });

    return function(options) {
        if (!instance) {
            instance = new Iznik.Views.Chat.Holder(options);
        }

        return instance;
    }
});
