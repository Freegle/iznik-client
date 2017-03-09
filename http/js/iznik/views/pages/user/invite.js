define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/message',
    'iznik/models/user/search',
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
            });

            return(p);
        }
    });

    Iznik.Views.User.Invited = Iznik.Views.Modal.extend({
        template: 'user_home_invited'
    });
});