define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Message = Iznik.View.extend({
        className: "panel panel-info marginbotsm",

        events: {
            'click .js-caret': 'carettoggle'
        },

        carettoggle: function() {
            if (this.$('.js-caretdown').css('display') != 'none') {
                this.$('.js-replycount').fadeOut('slow');
                this.$('.js-unreadcountholder').fadeOut('slow');
                this.$('.js-caretdown').hide();
                this.$('.js-caretup').show();
            } else {
                this.$('.js-replycount').fadeIn('slow');
                this.$('.js-unreadcountholder').fadeIn('slow');
                this.$('.js-caretdown').show();
                this.$('.js-caretup').hide();
            }
        },

        updateReplies: function() {
            this.replies = new Iznik.Collection(this.model.get('replies'));
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
                        unread += chat.get('unseen');
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
            }

            // If the number of unread messages relating to this message changes, we want to flag it in the count.  So
            // look for chats which refer to this message.  Note that chats can refer to multiple.
            Iznik.Session.chats.each(function(chat) {
                var refmsgids = chat.get('refmsgids');
                _.each(refmsgids, function(refmsgid) {
                    if (refmsgid == self.model.get('id')) {
                        self.listenTo(chat, 'change:unseen', self.updateUnread);
                    }
                })
            });
            self.updateUnread();

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

            // If the number of unseen messages in this chat changes, update this view so that the count is
            // displayed here.
            self.listenToOnce(chat, 'change:unseen', self.render);
            self.model.set('unseen', chat.get('unseen'));
            Iznik.View.prototype.render.call(self);
            self.$('.timeago').timeago();

            return(this);
        }
    });
});