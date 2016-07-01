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
    
    Iznik.Views.User.Pages.Give.WhatNext = Iznik.Views.Page.extend({
        template: "user_give_whatnext",

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function() {
                try {
                    var homegroup = localStorage.getItem('myhomegroup');

                    if (homegroup) {
                        var g = new Iznik.Models.Group({
                            id: homegroup
                        });

                        g.fetch().then(function() {
                            var v = new Iznik.Views.Group.Info({
                                model: g
                            });
                            v.render().then(function() {
                                self.$('.js-group').html(v.el)
                            });
                        });
                    }
                } catch (e) {};
            });

            return(p)
        }
    });
});