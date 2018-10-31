define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/modal'
], function($, _, Backbone, Iznik) {
    Iznik.Views.PromptPhone = Iznik.Views.Modal.extend({
        template: 'promptphone',

        events: {
            'click .js-savephone': 'clickSave',
            'click .js-close': 'clickClose'
        },

        clickSave: function () {
            var self = this;

            Iznik.ABTestAction('PromptPhone', 'Save');
            Iznik.Session.savePhone(self.$('.js-phone').val()).then(function() {
                Iznik.Session.testLoggedIn([
                    'me',
                    'phone'
                ]);
                self.close();
            });
        },

        clickClose: function () {
            Iznik.ABTestAction('PromptPhone', 'Close');
            this.close();
        },

        render: function () {
            var p = Iznik.resolvedPromise();

            if (!Storage.get('promptphone')) {
                Storage.set('promptphone', true);

                p = Iznik.Views.Modal.prototype.render.call(this);

                p.then(function () {
                    Iznik.ABTestShown('PromptPhone', 'Save');
                    Iznik.ABTestShown('PromptPhone', 'Close');
                });
            }

            return (p);
        }
    });
});