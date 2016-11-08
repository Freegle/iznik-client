define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/models/group',
    'iznik/views/group/info',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages',
    'iznik/views/pages/user/post'
], function($, _, Backbone, Iznik) {
    Iznik.Views.User.Pages.Give.WhereAmI = Iznik.Views.User.Pages.WhereAmI.extend({
        template: "user_give_whereami"
    });

    Iznik.Views.User.Pages.Give.WhatIsIt = Iznik.Views.User.Pages.WhatIsIt.extend({
        msgType: 'Offer',
        template: "user_give_whatisit",
        whoami: '/give/whoami'
    });

    Iznik.Views.User.Pages.Give.WhoAmI = Iznik.Views.User.Pages.WhoAmI.extend({
        template: "user_give_whoami",
        whatnext: '/give/whatnext'
    });
    
    Iznik.Views.User.Pages.Give.WhatNext = Iznik.Views.User.Pages.WhatNext.extend({
        template: "user_give_whatnext",

        events: {
            'click .js-fop': 'fop'
        },

        fop: function() {
            var self = this;
            var fop = self.$('.js-fop').is(':checked');

            try {
                self.id = localStorage.getItem('lastpost');
            } catch (e) {}

            if (self.id) {
                var message = new Iznik.Models.Message({ id: self.id });
                message.fetch().then(function() {
                    message.setFOP(fop);
                });
            }
        }
    });
});