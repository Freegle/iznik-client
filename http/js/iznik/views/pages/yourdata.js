define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
], function ($, _, Backbone, Iznik) {
    Iznik.Views.YourData = Iznik.Views.Page.extend({
        template: 'yourdata_main',

        modtools: MODTOOLS,

        noGoogleAds: true,

        render: function() {
            var self = this;

            var v = new Iznik.Views.PleaseWait({
                label: 'chat openChat'
            });
            v.render();

            $.ajax({
                url: API + 'user',
                data: {
                    id: Iznik.Session.get('me').id,
                    export: true
                },
                success: function(ret) {
                    if (ret.ret === 0 && ret.export) {
                        var user = new Iznik.Model(ret.export.user);
                        self.model = user;

                        var p = Iznik.Views.Page.prototype.render.call(self);

                        p.then(function() {
                            self.$('.js-date').each((function() {
                                var date = new Date($(this).html().trim());
                                $(this).html(date.toLocaleString());
                            }));

                            v.close();
                        });
                    }
                }
            });

            return(p);
        }
    });
});