define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/chat/chat'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Message = Iznik.View.extend({
        className: "panel panel-info marginbotsm",

        events: {
            'click .js-caret': 'carettoggle'
        },

        carettoggle: function() {
            if (this.$('.js-caretdown').css('display') != 'none') {
                this.$('.js-replycount').addClass('reallyHide');
                this.$('.js-unreadcountholder').addClass('reallyHide');
                this.$('.js-caretdown').hide();
                this.$('.js-caretup').show();
            } else {
                this.$('.js-replycount').removeClass('reallyHide');
                this.$('.js-unreadcountholder').removeClass('reallyHide');
                this.$('.js-caretdown').show();
                this.$('.js-caretup').hide();
            }
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
            Iznik.Session.chats.each(function(chat) {
                var refmsgids = chat.get('refmsgids');
                _.each(refmsgids, function(refmsgid) {
                    if (refmsgid == self.model.get('id')) {
                        var thisun = chat.get('unseen');
                        console.log("Got chat unseen", thisun);
                        unread += thisun;

                        if (thisun > 0) {
                            // This chat might indicate a new replier we've not got listed.
                            // TODO Could make this perform better than doing a full fetch.
                            self.model.fetch().then(function() {
                                console.log("Fetched");
                                console.log("add new replies", self.model.get('replies'));
                                self.replies.add(self.model.get('replies'));
                                self.updateReplies();
                            });
                        }
                    }
                });
            });

            if (unread > 0) {
                this.$('.js-unreadcount').html(unread);
                this.$('.js-unreadcountholder').show();
            } else {
                this.$('.js-unreadcountholder').hide();
            }
        },

        watchChatRooms: function() {
            var self = this;

            if (this.inDOM()) {
                // If the number of unread messages relating to this message changes, we want to flag it in the count.  So
                // look for chats which refer to this message.  Note that chats can refer to multiple.
                console.log("watchChatRooms");
                Iznik.Session.chats.fetch().then(function() {
                    console.log("Fetched chats");
                    Iznik.Session.chats.each(function (chat) {
                        self.listenTo(chat, 'change:unseen', self.updateUnread);
                    });

                    self.updateUnread();

                    self.listenToOnce(Iznik.Session.chats, 'newroom', self.watchChatRooms);
                });
            }
        },

        render: function() {
            var self = this;

            // Make sure any URLs in the message break.
            this.model.set('textbody', wbr(this.model.get('textbody'), 20));

            Iznik.View.prototype.render.call(self);
            var groups = self.model.get('groups');

            _.each(groups, function(group) {
                var v = new Iznik.Views.User.Message.Group({
                    model: new Iznik.Model(group)
                });
                self.$('.js-groups').append(v.render().el);
            });

            _.each(self.model.get('attachments'), function (att) {
                var v = new Iznik.Views.User.Message.Photo({
                    model: new Iznik.Model(att)
                });

                self.$('.js-attlist').append(v.render().el);
            });

            // Show and update the reply details.
            if ( self.$('.js-replies').length > 0) {
                self.$('.js-noreplies').hide();
                self.replies = new Iznik.Collection(self.model.get('replies'));
                self.listenTo(self.model, 'change:replies', self.updateReplies);
                self.updateReplies();

                self.repliesView = new Backbone.CollectionView({
                    el: self.$('.js-replies'),
                    modelView: Iznik.Views.User.Message.Reply,
                    modelViewOptions: {
                        collection: self.replies
                    },
                    collection: self.replies
                });

                self.repliesView.render();
            } else {
                self.$('.js-noreplies').show();
            }

            self.updateUnread();

            // We want to keep an eye on chat messages, because those which are in conversations referring to our
            // message should affect the counts we display.
            self.watchChatRooms();

            self.$('.timeago').timeago();

            return(this);
        }
    });

    Iznik.Views.User.Message.Group = Iznik.View.extend({
        template: "user_message_group",

        render: function() {
            Iznik.View.prototype.render.call(this);
            this.$('.timeago').timeago();
            return(this);
        }
    });

    Iznik.Views.User.Message.Photo = Iznik.View.extend({
        tagName: 'li',

        template: 'user_message_photo'
    });

    Iznik.Views.User.Message.Reply = Iznik.View.extend({
        tagName: 'li',

        template: 'user_message_reply',
        
        events: {
            'click': 'dm'
        },
        
        dm: function() {
            var self = this;
            require(['iznik/views/chat/chat'], function(ChatHolder) {
                ChatHolder().openChat(self.model.get('user').id);
            })
        },

        render: function() {
            var self = this;

            var chat = Iznik.Session.chats.get({
                id: self.model.get('chatid')
            });

            // We might not find this chat if the user has closed it.
            if (!_.isUndefined(chat)) {
                // If the number of unseen messages in this chat changes, update this view so that the count is
                // displayed here.
                self.listenToOnce(chat, 'change:unseen', self.render);
                self.model.set('unseen', chat.get('unseen'));
                Iznik.View.prototype.render.call(self);
                self.$('.timeago').timeago();
            }

            return(this);
        }
    });
});