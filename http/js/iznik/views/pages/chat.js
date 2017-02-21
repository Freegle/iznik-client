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
    'jquery-resizable',
    'jquery-visibility',
    'fileinput'
], function ($, _, Backbone, Iznik, autosize, moment) {
    Iznik.Views.Chat.Page = Iznik.Views.Page.extend({
        template: 'chat_page_main',

        noback: true,

        filter: null,

        fullheight: true,

        searchKey: function () {
            var self = this;
            self.filter = $('.js-leftsidebar').find('.js-search').val();
            console.log("Search filter", self.filter);

            // Apply the filter immediately - if we get matches on the name or snippet that will look zippy.
            self.chatsCV.reapplyFilter('visibleModels');

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
                        self.chatsCV.reapplyFilter('visibleModels');
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
                    self.chatsCV.setSelectedModel(first);
                }
            }
        },

        loadChat: function(chat) {
            // We have selected a chat.  Mark it as selected.
            var self = this;

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
                    }
                } catch (e) {}

                self.activeChat.messageFocus();
            })
        },

        changeDropdown: function() {
            var self = this;
            var val = self.$('#js-chatdropdown').val();
            var chat = self.chats.get(val);

            if (chat) {
                self.loadChat(chat);
            }
        },

        setupDropdown: function() {
            var self = this;
            var sel = self.$('#js-chatdropdown');
            sel.empty();

            self.chats.each(function(chat) {
                var title = chat.get('name');
                var unseen = chat.get('unseen');

                if (unseen) {
                    title = '(' + unseen + ') ' + title;
                }

                sel.append('<option value="' + chat.get('id') + '" />');
                var last = sel.find('option:last');
                last.html(title);

                if (chat.get('id') == self.options.chatid) {
                    last.attr('selected', true);
                } else {
                    last.removeAttr('selected');
                }
            });
        },

        allseen: function() {
            this.chats.allseen();
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function () {
                // We need the space.
                $('#botleft').hide();

                $('#js-notifchat').closest('li').addClass('active');

                // We use a single global collection for our chats.
                self.chats = Iznik.Session.chats;

                // There is a select drop-down to change chats.  This is only visible on mobile.
                self.setupDropdown();
                self.$('#js-chatdropdown').on('change', _.bind(self.changeDropdown, self));
                self.chats.on('add', _.bind(self.setupDropdown, self));

                // Left sidebar is the chat list.  It may not be visible on mobile, but we have it there anyway.
                templateFetch('chat_page_list').then(function() {
                    $('.js-leftsidebar').html(window.template('chat_page_list'));

                    // Now set up a collection view to list the chats.
                    self.chatsCV = new Backbone.CollectionView({
                        el: $('#js-chatlist'),
                        modelView: Iznik.Views.Chat.Page.One,
                        collection: self.chats,
                        visibleModelsFilter: _.bind(self.searchFilter, self)
                    });

                    self.chatsCV.render();

                    // When we click to select, we want to load that chat.
                    self.chatsCV.on('selectionChanged', function() {
                        console.log("selectionChanged");
                        var selectedModel = self.chatsCV.getSelectedModel();
                        self.loadChat(selectedModel);
                    });

                    self.selectedFirst = false;
                    self.chats.fetch({
                        cached: _.bind(self.fetchedChats, self)
                    }).then(_.bind(self.fetchedChats, self));

                    $('.js-leftsidebar .js-search').on('keyup', _.bind(self.searchKey, self));
                    $('.js-leftsidebar .js-allseen').on('click', _.bind(self.allseen, self));
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

        events: {
            'click .js-report, touchstart .js-report': 'report',
            'click .js-enter': 'enter',
            'focus .js-message': 'messageFocus',
            'click .js-promise': 'promise',
            'click .js-info': 'info',
            'click .js-photo': 'photo',
            'click .js-send': 'send',
            'click .js-large': 'large',
            'click .js-small': 'small',
            'keyup .js-message': 'keyUp',
            'change .js-status': 'status'
        },

        enter: function(e) {
            var v = new Iznik.Views.Chat.Enter();
            v.render();
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
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

        focus: function () {
            this.$('.js-message').click();
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
                        msg.fetch();
                        self.model.trigger('promised');
                    });

                    v.render();
                }
            });
        },

        info: function () {
            var self = this;

            var v = new Iznik.Views.Chat.UserInfo({
                model: new Iznik.Model(self.model.get('user1').id != Iznik.Session.get('me').id ?
                    self.model.get('user1') : self.model.get('user2'))
            });

            v.render();
        },

        allseen: function () {
            if (this.messages.length > 0) {
                this.model.set('lastmsgseen', this.messages.at(this.messages.length - 1).get('id'));
                // console.log("Now seen chat message", this.messages.at(this.messages.length - 1).get('id'));
            }
            this.model.set('unseen', 0);
        },

        messageFocus: function () {
            var self = this;

            var msg = self.$('.js-message');

            if (!msg.is(':focus')) {
                msg.focus();

                // We've seen all the messages.
                _.delay(_.bind(self.allseen, self), 30000);

                // Tell the server now, in case they navigate away before the next roster timer.
                Iznik.Session.chats.setStatus('Online', true);

                this.updateCount();
            }
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
                // console.log("Scroll now to ", self.model.get('id'), height);

                self.scrollTo = height;
                self.scrollToStopAt = self.scrollToStopAt ? self.scrollToStopAt : (new Date()).getTime() + 5000;

                if ((new Date()).getTime() < self.scrollToStopAt) {
                    self.scrollTimer = setTimeout(_.bind(self.scrollBottom, self), 1000);
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

            self.trigger('countupdated', unseen);
        },

        photoUpload: function() {
            var self = this;

            // Photo upload button
            self.$('.js-photo').fileinput({
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
                self.$('.js-photo').fileinput('upload');
            });

            self.$('.js-photo').on('fileuploaded', function (event, data) {
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

            var windowInnerHeight = $(window).innerHeight();
            var navbarOuterHeight = $('.navbar').outerHeight();
            var chatPaneHeight = $('.chat-page-pane').outerHeight();
            var pageContentTop = parseInt($('.pageContent').css('top').replace('px', ''));
            var chatHeaderOuterHeight = self.$('#js-chatheader').is(':visible') ? self.$('#js-chatheader').outerHeight() : 0;
            var chatDropdownHeight = $('#js-chatdropdown').outerHeight();
            var chatWarningHeight = (self.$('.js-chatwarning') && self.$('.js-chatwarning').is(':visible')) ? self.$('.js-chatwarning').outerHeight() : 0;
            var footerHeight = self.$('.js-chatfooter').outerHeight();

            var height = windowInnerHeight - navbarOuterHeight - chatDropdownHeight;
            var str = "Heights " + height + " " + windowInnerHeight + " " + navbarOuterHeight+ " " + " " + chatDropdownHeight;
            console.log(str);
            self.$('.js-msgpane').css('height', height + 'px');
            self.$('.js-message').val(str);

            _.delay(_.bind(self.adjust, self), 500);
        },

        render: function () {
            var self = this;

            var p = Iznik.View.prototype.render.call(self);
            p.then(function (self) {
                // Input text autosize
                autosize(self.$('textarea'));

                self.adjust();

                if (!self.options.modtools) {
                    self.$('.js-privacy').hide();
                } else {
                    self.$('.js-promise').hide();
                }

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
                    // We've just opened this chat - so we have had a decent chance to see any unread messages.
                    v.close();
                    self.messageFocus();
                    self.scrollBottom();
                });

                // Show any warning for a while.
                self.$('.js-chatwarning').show();
                window.setTimeout(_.bind(function () {
                    this.$('.js-chatwarning').slideUp('slow');
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
                self.listenTo(self.model, 'change:unseen', self.updateCount);

                self.messageViews = new Backbone.CollectionView({
                    el: self.$('.js-messages'),
                    modelView: Iznik.Views.Chat.Message,
                    collection: self.messages,
                    chatView: self,
                    comparator: 'id',
                    modelViewOptions: {
                        chatView: self,
                        chatModel: self.model
                    }
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
            });

            return (p);
        }
    });
});