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
        template: "user_give_whereami",
        title: "Give away something"
    });

    Iznik.Views.User.Pages.Give.WhatIsIt = Iznik.Views.User.Pages.WhatIsIt.extend({
        msgType: 'Offer',
        template: "user_give_whatisit",
        whoami: '/give/whoami',
        title: "Give away something"
    });

    Iznik.Views.User.Pages.Give.WhoAmI = Iznik.Views.User.Pages.WhoAmI.extend({
        template: "user_give_whoami",
        whatnext: '/give/whatnext',
        title: "Give away something"
    });
    
    Iznik.Views.User.Pages.Give.WhatNext = Iznik.Views.User.Pages.WhatNext.extend({
        template: "user_give_whatnext",

        title: "Give away something",

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

                    try {
                        localStorage.setItem('FOP', fop ? 1 : 0);
                    } catch (e) {}
                });
            }
        },

        render: function() {
            var self = this;

            var p = Iznik.Views.User.Pages.WhatNext.prototype.render.call(this);
            p.then(function() {
                try {
                    var fop = localStorage.getItem('FOP');
                    if (fop !== null) {
                        self.$('.js-fop').prop('checked', parseInt(fop) ? true : false);
                    }
                } catch (e) {}
            });

            return(p);
        }
    });
});