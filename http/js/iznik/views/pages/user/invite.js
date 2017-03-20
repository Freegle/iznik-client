define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/message',
    'iznik/models/user/search',
    'iznik/models/invitation',
    'iznik/views/group/communityevents',
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Invite = Iznik.Views.Page.extend({
        template: "user_invite_main",

        events: {
            'click .js-invite': 'doInvite'
        },

        doInvite: function() {
            var self = this;
            var email = self.$('.js-inviteemail').val();

            if (isValidEmailAddress(email)) {
                $.ajax({
                    url: API + 'invitation',
                    type: 'PUT',
                    data: {
                        email: email
                    },
                    complete: function() {
                        self.$('.js-inviteemail').val('');
                        self.$('.js-showinvite').slideUp('slow');
                        (new Iznik.Views.User.Invited()).render();
                        self.invitations.fetch();
                        self.$('.js-invitewrapper').show();
                    }
                });
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this, {
                noSupporters: true
            });

            p.then(function(self) {
                // Left menu is community events
                var v = new Iznik.Views.User.CommunityEventsSidebar();
                v.render().then(function () {
                    $('#js-eventcontainer').append(v.$el);
                });

                // List invitations.
                self.invitations = new Iznik.Collections.Invitations();

                self.collectionView = new Backbone.CollectionView({
                    el: self.$('.js-list'),
                    modelView: Iznik.Views.User.Invitation,
                    collection: self.invitations,
                    processKeyEvents: false
                });

                self.collectionView.render();
                self.invitations.fetch().then(function() {
                    if (self.invitations.length > 0) {
                        self.$('.js-invitewrapper').fadeIn('slow');
                    }
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.Invited = Iznik.Views.Modal.extend({
        template: 'user_home_invited'
    });

    Iznik.Views.User.Invitation = Iznik.View.Timeago.extend({
        template: 'user_invite_one',
        tagName: 'li'
    })
});