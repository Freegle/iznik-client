define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'autosize',
    'moment',
    'iznik/models/chat/chat',
    'iznik/views/pages/pages',
    'iznik/views/group/select',
    'iznik/views/postaladdress',
    'iznik/views/user/schedule',
    'iznik/views/user/message',
    'jquery-resizable',
    'jquery-visibility',
    'fileinput'
], function ($, _, Backbone, Iznik, autosize, moment, ChatHolder) {
    Iznik.Views.Chat.Page = Iznik.Views.Page.extend({
        noback: true,

        noEmailOk: true,

        filter: null,

        searchKey: function (e) {
            var self = this;
            self.filter = $(e.target).val();

            // Apply the filter immediately - if we get matches on the name or snippet that will look zippy.
            self.chatsCV1.reapplyFilter('visibleModels');
            self.chatsCV2.reapplyFilter('visibleModels');

            if (self.filter.length > 2) {
                // Now search on the sever.  But delay this to allow for extra keystrokes - avoids hitting
                // the server many times.
                if (self.searchTimer) {
                    clearTimeout(self.searchTimer);
                }

                self.searchTimer = setTimeout(function() {
                    self.chats.fetch({
                        data: {
                            search: self.filter
                        }
                    }).then(function() {
                        self.chatsCV1.reapplyFilter('visibleModels');
                        self.chatsCV2.reapplyFilter('visibleModels');
                    });
                }, 500);
            }
        },

        searchFilter: function (model) {
            var self = this;
            var ret = true;

            if (self.filter) {
                var filt = self.filter.toLowerCase();
                var snippet = model.get('snippet') ? model.get('snippet') : '';

                var ret = (self.filter.length === 0 ||
                snippet.toLowerCase().indexOf(filt) !== -1 ||
                model.get('name').toLowerCase().indexOf(filt) !== -1);

                if (!ret && self.searchChats) {
                    ret = self.searchChats.get(model.get('id'));
                }
            }

            return (ret);
        },

        fetchedChats: function() {
            // Select a default chat.
            var self = this;

            if (!self.selectedFirst) {
                self.selectedFirst = true;

                var first = null;

                if (self.options.chatid) {
                    // We've been asked to select a specific chat.
                    first = Iznik.Session.chats.get(self.options.chatid);
                }

                if (!first) {
                    // Select the most recent.
                    first = Iznik.Session.chats.first();
                }

                if (first) {
                    self.chatsCV1.setSelectedModel(first);
                }
            }
        },

        loadChat: function(chat) {
            // We have selected a chat.  Mark it as selected.
            var self = this;

            if (chat)
            {
                self.selectedModel = chat;
                self.activeChat = new Iznik.Views.Chat.Page.Pane({
                    model: self.selectedModel
                });
                self.activeChat.render().then(function() {
                    $('#js-msgpane').html(self.activeChat.$el);

                    try {
                        var lastchatmsg = Storage.get('lastchatmsg');
                        var lastchatid = Storage.get('lastchatid');

                        if (lastchatid == chat.get('id')) {
                            self.$('.js-message').val(lastchatmsg);
                            Storage.clear('lastchatmsg');
                            Storage.clear('lastchatid');
                        }
                    } catch (e) {}
                })
            }
        },

        allseen: function() {
            this.chats.allseen();
        },

        render: function () {
            var self = this;

            self.template = self.modtools ? 'chat_page_mainmodtools' : 'chat_page_mainuser';

            // For user, we put it in js-leftsidebar - which (hackily) may be a visible left sidebar for larger
            // screens or the central pane for xs.
            self.listContainer = self.modtools ? '#js-chatlistholder' : '.js-leftsidebar';

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function () {
                // We need the space.
                $('#botleft').hide();

                // We use a single global collection for our chats.
                self.chats = Iznik.Session.chats;

                // When something happens on the chat, we want to sort the collection, which will then sort the
                // collection view, resulting in unread messages being at the top.
                self.listenTo(self.chats, 'somethinghappened', function(chatid) {
                    // Only sort if this chat is not already at the top or open in a popup window.
                    var first = self.chats.first();
                    var mod = self.chats.get(chatid);
                    var view = Iznik.openChats.viewManager.findByModel(mod);

                    if (first && first.get('id') != chatid && (!view || view.minimised)) {
                        self.chats.sort();
                    }
                });

                templateFetch('chat_page_list').then(function() {
                    $(self.listContainer).html(window.template('chat_page_list'));
                    $(self.listContainer).addClass('chat-list-holder');

                    // Now set up a collection view to list the chats.  First one is for the left sidebar, which
                    // then loads the chat in the centre panel.
                    self.chatsCV1 = new Backbone.CollectionView({
                        el: $('#js-chatlist1'),
                        modelView: Iznik.Views.Chat.Page.One,
                        collection: self.chats,
                        visibleModelsFilter: _.bind(self.searchFilter, self),
                        processKeyEvents: false
                    });

                    self.chatsCV1.render();

                    // When we click to select, we want to load that chat.
                    self.chatsCV1.on('selectionChanged', function(selected) {
                        if (selected.length > 0) {
                            self.loadChat(selected[0]);
                        }
                    });

                    // Second one is for the centre panel, which shows the chat list or the actual messages.
                    if (!self.options.chatid) {
                        // Specific chats have the chat in the centre - not the list.
                        self.$('#js-msgpane').addClass('hidden-xs hidden-sm');
                        self.$('.js-chatsearchholder').removeClass('hidden-xs hidden-sm')
                        self.chatsCV2 = new Backbone.CollectionView({
                            el: $('#js-chatlist2'),
                            modelView: Iznik.Views.Chat.Page.One,
                            collection: self.chats,
                            visibleModelsFilter: _.bind(self.searchFilter, self),
                            processKeyEvents: false
                        });

                        self.chatsCV2.render();

                        // When we click on this one, we want to route to the chat/id.  This is so that the user
                        // can use the back button to return to the chat list.
                        self.chatsCV2.on('selectionChanged', function(selected) {
                            Router.navigate((self.modtools ? '/modtools' : '') + '/chat/' + selected[0].get('id'), true);
                        });
                    }

                    self.selectedFirst = false;
                    self.chats.fetch().then(_.bind(self.fetchedChats, self));

                    $('.js-search').on('keyup', _.bind(self.searchKey, self));
                    $('.js-allseen').on('click', _.bind(self.allseen, self));
                });
            });

            return (p);
        }
    });

    Iznik.Views.Chat.Page.One = Iznik.View.Timeago.extend({
        template: 'chat_page_one',

        className: 'hoverDiv clickme',

        tagName: 'li',

        allseen: function () {
            var self = this;

            if (self.model.get('unseen') > 0) {
                // We have to get the messages to find out which the last one is.
                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });
                self.messages.fetch({
                    remove: true
                }).then(function () {
                    if (self.messages.length > 0) {
                        var lastmsgseen = self.messages.at(self.messages.length - 1).get('id');
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

        updateCount: function () {
            var self = this;
            var unseen = self.model.get('unseen');
            var current = self.$('.js-count').html();

            // Don't do DOM manipulations unless we need to as that's a performance killer.
            if (unseen != current) {
                if (unseen > 0) {
                    self.$('.js-count').html(unseen).show();
                } else {
                    self.$('.js-count').html(unseen).hide();
                }
            }

            self.trigger('countupdated', unseen);
        },

        render: function () {
            var self = this;
            self.model.set('modtools', self.options.modtools);
            // console.log("Render chat", self.model.get('id'), self.model.get('icon'), self.model.attributes, self.chats);

            var p = Iznik.View.Timeago.prototype.render.call(this);
            p.then(function (self) {
                self.updateCount();

                // If the unread message count changes, we want to update it.
                if (!self.unseenListen) {
                    self.unseenListen = true;
                    self.listenTo(self.model, 'change:unseen', self.updateCount);
                }

                if (!self.snippetListen) {
                    self.snippetListen = true;
                    self.listenTo(self.model, 'change:snippet', self.render);
                }
            });

            return (p);
        }
    });

    Iznik.Views.Chat.Page.Pane = Iznik.View.extend({
        template: 'chat_page_pane',

        className: 'chat-page-pane bordleft bordright',

        maxAdjustDelay: 300,
        currentAdjustDelay: 10,
        shownAddress: false,

        events: {
            'click .js-report, touchstart .js-report': 'report',
            'click .js-enter': 'enter',
            'focus .js-message': 'messageFocused',
            'click .js-promise': 'promise',
            'click .js-address': 'address',
            'click .js-nudge': 'nudge',
            'click .js-schedule': 'schedule',
            'click .js-info': 'info',
            'click .js-photo': 'photo',
            'click .js-send': 'send',
            'click .js-large': 'large',
            'click .js-small': 'small',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status',
            'click .js-remove': 'removeIt',
            'click .js-block': 'blockIt',
            'click .js-popup': 'popup'
        },

        popup: function(){
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                var chatid = self.model.get('id');
                ChatHolder().fetchAndRestore(chatid);
            });
        },

        enter: function(e) {
            var v = new Iznik.Views.Chat.Enter();
            v.render();
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        },

        removeIt: function (e) {
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
                var v = new Iznik.Views.PleaseWait({
                    label: 'chat openChat'
                });
                v.render();

                self.model.close().then(function() {
                    // Reload.  Bit clunky but it'll do.
                    Iznik.Session.chats.fetch().then(function() {
                        window.location.reload();
                    });
                });
            });

            v.render();
        },

        blockIt: function (e) {
            var self = this;
            e.preventDefault();
            e.stopPropagation();

            var v = new Iznik.Views.Confirm({
                model: self.model
            });
            v.template = 'chat_block';

            self.listenToOnce(v, 'confirmed', function () {
                // This will block the chat, which means it won't show in our list again.
                var v = new Iznik.Views.PleaseWait({
                    label: 'chat openChat'
                });
                v.render();

                self.model.block().then(function() {
                    // Reload.  Bit clunky but it'll do.
                    Iznik.Session.chats.fetch().then(function() {
                        window.location.reload();
                    });
                });
            });

            v.render();
        },

        keyUp: function (e) {
            var self = this;
            var enterSend = null;
            try {
                enterSend = Storage.get('chatentersend');
                if (enterSend !== null) {
                    enterSend = parseInt(enterSend);
                }
            } catch (e) {};

            if (e.which === 13) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (e.altKey || e.shiftKey || enterSend === 0) {
                    // They've used the alt/shift trick, or we know they don't want to send.
                    self.$('.js-message').val(self.$('.js-message').val() + "\n");
                } else  {
                    if (enterSend !== 0 && enterSend !== 1) {
                        // We don't know what they want it to do.  Ask them.
                        var v = new Iznik.Views.Chat.Enter();
                        self.listenToOnce(v, 'modalClosed', function() {
                            // Now we should know.
                            try {
                                enterSend = parseInt(Storage.get('chatentersend'));
                            } catch (e) {};

                            if (enterSend) {
                                self.send();
                            } else {
                                self.$('.js-message').val(self.$('.js-message').val() + "\n");
                            }
                        });
                        v.render();
                    } else {
                        self.send();
                    }
                }
            }
        },

        checkAddress: function() {
            var self = this;

            if (!self.shownAddress && self.inDOM()) {
                var msg = self.$('.js-message').val();

                if (msg.indexOf('address') !== -1) {
                    self.$('.js-address').tooltip('show');
                    self.shownAddress = true;
                    _.delay(_.bind(function() {
                        this.$('.js-address').tooltip('hide');
                    }, self), 10000);
                }

                _.delay(_.bind(self.checkAddress, self), 1000);
            }
        },

        getLatestMessages: function() {
            var self = this;

            if (!self.fetching) {
                self.fetching = true;
                self.fetchAgain = false;

                // Get the full set of messages back.  This will replace any temporary
                // messages added, and also ensure we don't miss any that arrived while we
                // were sending ours.
                self.messages.fetch({
                    remove: true
                }).then(function () {
                    self.fetching = false;
                    if (self.fetchAgain) {
                        // console.log("Fetch messages again");
                        self.getLatestMessages();
                    } else {
                        // console.log("Fetched and no more");
                        self.scrollBottom();
                    }
                });
            } else {
                // We are currently fetching, but would like to do so again.  Queue another fetch to happen
                // once this completes.  That avoids a car crash of fetches happening when there are a lot of
                // messages being sent and we're not keeping up.
                // console.log("Fetch again later");
                self.fetchAgain = true;
            }
        },

        send: function () {
            var self = this;
            var message = this.$('.js-message').val();

            // Don't allow people to send > as it will lead to the message being stripped as a possible reply.
            // TODO Allow this by recording the origin of the message as being on the platform.
            message = message.replace('>', '');

            if (message.length > 0) {
                // We get called back when the message has actually been sent to the server.
                self.listenToOnce(this.model, 'sent', function () {
                    self.getLatestMessages();
                });

                self.model.send(message);

                // Create another model with a fake id and add it to the collection.  This will populate our view
                // views while we do the real save in the background.  Makes us look fast.
                var prelast = self.messages.last();
                var nextid = prelast ? (prelast.get('id') + 1) : 1;
                var tempmod = new Iznik.Models.Chat.Message({
                    id: nextid,
                    chatid: self.model.get('id'),
                    message: message,
                    date: (new Date()).toISOString(),
                    sameaslast: true,
                    sameasnext: true,
                    seenbyall: 0,
                    type: 'Default',
                    user: Iznik.Session.get('me')
                });

                self.messages.add(tempmod);

                // We have initiated the send, so set up for the next one.
                self.$('.js-message').val('');
                self.messageFocus();

                // If we've grown the textarea, shrink it.
                self.$('textarea').css('height', '');
            }
        },

        lsID: function () {
            return ('chat-' + this.model.get('id'));
        },

        zapViews: function () {
            Iznik.Session.chats.remove({
                id: this.model.get('id')
            });
        },

        noop: function () {
        },

        promise: function () {
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
            }).then(function () {
                if (self.offers.length > 0) {
                    // The message we want to suggest as the one to promise is any last message mentioned in this chat.
                    var msgid = null;
                    _.each(self.model.get('refmsgids'), function(m) {
                        msgid = m;
                    });

                    var msg = null;
                    self.offers.each(function (offer) {
                        if (offer.get('id') == msgid) {
                            msg = offer;
                        }
                    });

                    var v = new Iznik.Views.User.Message.Promise({
                        model: new Iznik.Model({
                            message: msg ? msg.toJSON2() : null,
                            user: self.model.get('user1').id != Iznik.Session.get('me').id ?
                                self.model.get('user1') : self.model.get('user2')
                        }),
                        offers: self.offers
                    });

                    self.listenToOnce(v, 'promised', function () {
                        self.model.trigger('promised');
                        self.messages.fetch();
                    });

                    v.render();
                }
            });
        },

        address: function() {
            var self = this;

            var v = new Iznik.Views.PostalAddress.Modal();

            self.listenToOnce(v, 'address', function(id) {
                var tempmod = new Iznik.Models.Chat.Message({
                    roomid: self.model.get('id'),
                    addressid: id
                });

                tempmod.save().then(function() {
                    // Fetch the messages again to pick up this new one.
                    self.messages.fetch();
                });
            });

            v.render();
        },

        nudge: function() {
            var self = this;

            self.model.nudge().then(function() {
                self.messages.fetch();
            });
        },

        schedule: function() {
            var self = this;

            var other = this.model.otherUser();

            // See if we have an outstanding schedule.

            var m = new Iznik.Models.ModTools.User({
                id: other
            });

            m.fetch().then(function() {
                var v = new Iznik.Views.User.Schedule.Modal({
                    model: m,
                    id: null,
                    schedule: null,
                    other: false,
                    otherid: null,
                    slots: null
                });

                self.listenToOnce(v, 'modalClosed', function () {
                    self.messages.fetch();
                });

                v.render();
            });
        },

        info: function () {
            var self = this;

            require([ 'iznik/views/user/user' ], function() {
                var v = new Iznik.Views.UserInfo({
                    model: new Iznik.Model(self.model.get('user1').id != Iznik.Session.get('me').id ?
                        self.model.get('user1') : self.model.get('user2'))
                });

                v.render();
            });
        },

        messageFocused: function () {
            var self = this;

            // We've seen all the messages.
            self.model.allseen();

            this.updateCount();
        },

        messageFocus: function() {
            this.$('.js-message').focus();
        },

        report: function () {
            var groups = Iznik.Session.get('groups');

            if (groups.length > 0) {
                // We only take reports from a group member, so that we have somewhere to send it.
                // TODO Give an error or pass to support?
                (new Iznik.Views.Chat.Report({
                    chatid: this.model.get('id')
                })).render();
            }
        },

        scrollTimer: null,
        scrollTo: 0,
        scrolledToBottomOnce: false,

        scrollBottom: function () {
            // Tried using .animate(), but it seems to be too expensive for the browser, so leave that for now.
            var self = this;
            var scroll = self.$('.js-scroll');
            // console.log("Scrollbottom", scroll);

            if (scroll.length > 0) {
                var height = scroll[0].scrollHeight;

                if (self.scrollTimer && self.scrollTo < height) {
                    // We have a timer outstanding to scroll to somewhere less far down that we now want to.  No point
                    // in doing that.
                    // console.log("Clear old scroll timer",  self.model.get('id'), self.scrollTo, height);
                    clearTimeout(self.scrollTimer);
                    self.scrollTimer = null;
                    self.scrollToStopAt = null;
                }

                // We want to scroll immediately, and gradually over the next few seconds for when things haven't quite
                // finished rendering yet.
                scroll.scrollTop(height);

                self.scrollTo = height;

                if (!self.scrolledToBottomOnce) {
                    // We want to scroll immediately, and gradually over the next few seconds for when things haven't quite
                    // finished rendering yet.
                    self.scrollToStopAt = self.scrollToStopAt ? self.scrollToStopAt : ((new Date()).getTime() + 5000);

                    if ((new Date()).getTime() < self.scrollToStopAt) {
                        self.scrollTimer = setTimeout(_.bind(self.scrollBottom, self), 1000);
                    } else {
                        self.scrolledToBottomOnce = true;
                    }
                }
            }
        },

        status: function () {
            // We can override appearing online to show something else.
            var status = this.$('.js-status').val();
            try {
                Storage.set('mystatus', status);
            } catch (e) {
            }

            Iznik.Session.chats.updateRoster(status, true);
        },

        countHidden: true,

        updateCount: function () {
            var self = this;
            var unseen = self.model.get('unseen');

            if (self.inDOM()) {
                // For performance reasons we avoid doing show/hide unless we need to.
                if (unseen > 0) {
                    self.$('.js-count').html(unseen).show();
                    self.countHidden = false;

                    if (self.messages) {
                        self.messages.fetch({
                            remove: true
                        });
                    }
                } else if (!self.countHidden) {
                    // When we call this from render, it's already hidden.
                    self.$('.js-count').html(unseen).hide();
                    self.countHidden = true;
                }
            }

            self.trigger('countupdated', unseen);
        },

        photoUpload: function() {
            var self = this;

            // Photo upload button
            self.$('.js-photopicker').fileinput({
                uploadExtraData: {
                    imgtype: 'ChatMessage',
                    chatmessage: 1
                },
                showUpload: false,
                allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                uploadUrl: API + 'image',
                resizeImage: true,
                maxImageWidth: 800,
                browseIcon: '',
                browseLabel: '',
                browseClass: 'clickme glyphicons glyphicons-camera text-muted gi-2x',
                showCaption: false,
                showRemove: false,
                showCancel: false,
                showPreview: true,
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

            self.$('.js-photo').on('fileimagesresized', function (event) {
                // Upload as soon as we have it.  Add an entry for the progress bar.
                $('.file-preview, .kv-upload-progress').hide();
                var prelast = self.messages.last();
                var nextid = prelast ? (prelast.get('id') + 1) : 1;
                nextid = _.isNaN(nextid) ? 1 : nextid;
                var tempmod = new Iznik.Models.Chat.Message({
                    id: nextid,
                    roomid: self.model.get('id'),
                    date: (new Date()).toISOString(),
                    type: 'Progress',
                    user: Iznik.Session.get('me')
                });

                self.messages.add(tempmod);
                self.$('.js-photopicker').fileinput('upload');
            });

            self.$('.js-photopicker').on('fileuploaded', function (event, data) {
                console.log("Uploaded", event, data);
                var ret = data.response;

                // Create a chat message to hold it.
                var tempmod = new Iznik.Models.Chat.Message({
                    roomid: self.model.get('id'),
                    imageid: ret.id
                });

                tempmod.save().then(function() {
                    // Fetch the messages again to pick up this new one.
                    self.messages.fetch();
                });
            });
        },

        adjust: function() {
            var self = this;
            self.adjustTimerRunning = false;

            if (self.inDOM()) {
                var windowInnerHeight = $(window).innerHeight();
                var bodyMargin = parseInt($('body').css('margin-top').replace('px', ''));
                var chatWarningHeight = (self.$('.js-chatwarning') && self.$('.js-chatwarning').is(':visible')) ? self.$('.js-chatwarning').outerHeight() : 0;
                var chatHeaderHeight = self.$('.js-chatheader').is(':visible') ? self.$('.js-chatheader').outerHeight() : 0;
                var footerHeight = self.$('.js-chatfooter').outerHeight();
                var chatSearchHolderHeight = self.$('.js-chatsearchholder').is(':visible') ? self.$('.js-chatsearchholder').outerHeight() : 0;

                var height = windowInnerHeight - bodyMargin - chatWarningHeight - chatSearchHolderHeight - chatHeaderHeight - footerHeight;
                var str = "Heights " + height + " " + windowInnerHeight + " " + bodyMargin + " " + chatSearchHolderHeight + " " + chatWarningHeight + " " + chatHeaderHeight + " " + footerHeight;
                var currHeight = self.$('.js-scroll').css('height').replace('px', '');

                // Don't adjust 1px differences immediately - leads to jittering.
                if (currHeight != height && (self.currentAdjustDelay > 10 || Math.abs(currHeight - height) > 1)) {
                    self.$('.js-scroll').css('height', height + 'px');
                    self.currentAdjustDelay = 10;
                    // console.log(str);
                } else {
                    self.currentAdjustDelay *= 2;
                    self.currentAdjustDelay = Math.min(self.currentAdjustDelay, self.maxAdjustDelay);
                }

                // self.$('.js-message').val(str);

                if (!self.adjustTimerRunning) {
                    self.adjustTimerRunning = true;
                    _.delay(_.bind(self.adjust, self), self.currentAdjustDelay);
                }
            } else {
                console.log("Not in DOM");
            }
        },

        rendered: false,

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function (self) {
                if (!self.options.modtools) {
                    self.$('.js-privacy').hide();
                } else {
                    self.$('.js-promise').hide();
                }

                self.$('.js-tooltip').tooltip();

                self.messages = new Iznik.Collections.Chat.Messages({
                    roomid: self.model.get('id')
                });

                var v = new Iznik.Views.PleaseWait({
                    label: 'chat restore'
                });
                v.render();

                self.messages.fetch({
                    remove: true
                }).then(function () {
                    // If the last message was a while ago, remind them about nudging.
                    var age = ((new Date()).getTime() - (new Date(self.model.get('lastdate')).getTime())) / (1000 * 60 * 60);

                    if (age > 24 && !self.shownNudge) {
                        self.$('.js-nudge').tooltip('show');
                        self.shownNudge = true;

                        _.delay(_.bind(function() {
                            this.$('.js-nudge').tooltip('hide');
                        }, self), 10000);
                    }

                    // Encourage people to use the info button.
                    if (!self.shownInfo && !self.shownNudge) {
                        self.$('.js-tooltip.js-info').tooltip('show');
                        self.shownInfo = true;

                        _.delay(_.bind(function() {
                            this.$('.js-tooltip.js-info').tooltip('hide');
                        }, self), 10000);
                    }

                    v.close();
                    self.scrollBottom();
                });

                // Show any warning for a while.
                self.$('.js-chatwarning').show();
                window.setTimeout(_.bind(function () {
                    self.$('.js-chatwarning').slideUp('slow');
                }, self), 30000);

                // Set any roster status.
                try {
                    var status = Storage.get('mystatus');

                    if (status) {
                        self.$('.js-status').val(status);
                    }
                } catch (e) {
                }

                self.updateCount();

                if (!self.rendered) {
                    self.rendered = true;

                    // Input text autosize
                    autosize(self.$('textarea'));

                    self.waitDOM(self, function() {
                        self.adjust();
                    });

                    self.listenTo(self.model, 'change:unseen', self.updateCount);

                    // If the snippet changes, we have new messages to pick up.
                    self.listenTo(self.model, 'change:snippet', self.getLatestMessages);
                }

                self.messageViews = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages,
                    chatView: self,
                    comparator: 'id',
                    modelViewOptions: {
                        chatView: self,
                        chatModel: self.model
                    },
                    processKeyEvents: false
                });

                // As new messages are added, we want to show them.  This also means when we first render, we'll
                // scroll down to the latest messages.
                self.listenTo(self.messageViews, 'add', function (modelView) {
                    self.listenToOnce(modelView, 'rendered', function () {
                        self.scrollBottom();
                    });
                });

                self.messageViews.render();

                self.photoUpload();

                _.delay(_.bind(self.checkAddress, self), 1000);
            });

            return (p);
        }
    });

    Iznik.Views.Chat.External = Iznik.Views.Page.extend({
        noback: true,

        noEmailOk: true,

        template: 'chat_page_external',

        events: {
            'click .js-next': 'login'
        },

        login: function() {
            var self = this;

            self.listenToOnce(Iznik.Session, 'loggedIn', function () {
                self.render();
            });

            Iznik.Session.forceLogin();
        },

        render: function() {
            var self = this;

            var msg = new Iznik.Models.Message({
                id: self.options.msgid
            });

            var p = msg.fetch().then(function() {
                self.listenToOnce(Iznik.Session, 'isLoggedIn', function (loggedIn) {
                    if (loggedIn) {
                        // Ok, we're logged in.  Possibly as the user for this chat, possibly not.
                        var chat = new Iznik.Models.Chat.Room({
                            id: self.options.chatid
                        });

                        chat.fetch().then(function() {
                            var myid = Iznik.Session.get('me').id;

                            if (chat.get('user1').id == myid || chat.get('user2').id == myid) {
                                // Yes, this is for us.
                                //
                                // We might need to sign up for the group.  Find the last referenced message,
                                // which tells us which group we may need to join.
                                var messages = new Iznik.Collections.Chat.Messages({
                                    roomid: self.options.chatid
                                });

                                messages.fetch().then(function() {
                                    var refmsgid = null;
                                    messages.each(function(message) {
                                        var refmsg = message.get('refmsg');
                                        if (refmsg) {
                                            // We've found a message
                                            var msggroups = refmsg.groups;
                                            if (msggroups.length > 0) {
                                                var groupid = msggroups[0].groupid;
                                                var already = false;
                                                Iznik.Session.get('groups').each(function(group) {
                                                    if (group.get('id') == groupid) {
                                                        already = true;
                                                    }
                                                });

                                                if (!already) {
                                                    // Finally!  We're not a member yet, so join us up.
                                                    $.ajax({
                                                        url: API + 'memberships',
                                                        type: 'PUT',
                                                        data: {
                                                            groupid: groupid
                                                        }, complete: function () {
                                                            // Now that we've joined, proceed to the chat
                                                            Router.navigate('/chat/' + self.options.chatid, true);
                                                        }
                                                    });
                                                } else {
                                                    // Just go to the chat.
                                                    Router.navigate('/chat/' + self.options.chatid, true);
                                                }
                                            }
                                        }
                                    });
                                });

                                var groups = Iznik.Session.get('groups');

                            } else {
                                // No, someone else has clicked on this link.  Just show them the message and
                                // let them proceed from there if they want.
                                Router.navigate('/message/' + self.options.msgid, true);
                            }
                        });
                    } else {
                        // We're not logged in yet.  Display the explanation page.
                        self.model = msg;
                        Iznik.Views.Page.prototype.render.call(self);
                    }
                });

                Iznik.Session.testLoggedIn();
            });

            return(p);
        }
    });
});