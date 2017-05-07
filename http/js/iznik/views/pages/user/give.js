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

        fop: function () {
            var self = this;
            var fop = self.$('.js-fop').is(':checked') ? 1 : 0;

            try {
                self.id = Storage.get('lastpost');
            } catch (e) {
            }

            if (self.id) {
                var message = new Iznik.Models.Message({id: self.id});
                message.fetch().then(function () {
                    message.setFOP(fop);

                    try {
                        Storage.set('FOP', fop ? 1 : 0);
                    } catch (e) {
                    }
                });
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.User.Pages.WhatNext.prototype.render.call(this);
            p.then(function () {
                try {
                    var fop = Storage.get('FOP');
                    if (fop !== null) {
                        self.$('.js-fop').prop('checked', parseInt(fop) ? true : false);

                        if (!parseInt(fop)) {
                            // FOP defaults on, so make sure that it's off.
                            self.fop();
                        }
                    }
                } catch (e) {
                }

                try {
                    var v = new Iznik.Views.User.Pages.Give.Share({
                        model: new Iznik.Models.Message({
                            id: Storage.get('lastpost')
                        })
                    });

                    v.model.fetch().then(function() {
                        v.render();
                    });

                } catch (e) {
                }
            });

            return (p);
        }
    });

    Iznik.Views.User.Pages.Give.Share = Iznik.Views.User.Pages.WhatNext.Share.extend({
        template: "user_give_share"
    });
});