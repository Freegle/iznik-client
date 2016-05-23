define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/pages/pages',
    'iznik/views/pages/user/pages'
], function($, _, Backbone, Iznik) {
    console.log("Iznik is ", Iznik);
    Iznik.Views.User.Pages.Alert = {};

    Iznik.Views.User.Pages.Alert.Viewed = Iznik.Views.Page.extend({
        template: "user_alerts_viewed",

        render: function() {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                $('.js-signin, .js-home').hide();
            });

            $.ajax({
                type: 'POST',
                url: API + 'alert',
                data: {
                    action: 'clicked',
                    trackid: this.model.get('id')
                }
            });
            
            return(p);
        }
    });
});