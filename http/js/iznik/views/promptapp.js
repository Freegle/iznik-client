define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/views/modal'
], function($, _, Backbone, Iznik) {
    Iznik.Views.PromptApp = Iznik.Views.Modal.extend({
        template: 'promptapp',

        events: {
            'click .js-android': 'clickAndroid',
            'click .js-ios': 'clickAndroid',
            'click .js-close': 'clickClose'

        },

        clickAndroid: function () {
            Iznik.ABTestAction('PromptApp', 'Android');
        },

        clickIOS: function () {
            Iznik.ABTestAction('PromptApp', 'IOS');
        },

        clickClose: function () {
            Iznik.ABTestAction('PromptApp', 'Close');
            this.close();
        },

        render: function () {
            var p = Iznik.Views.Modal.prototype.render.call(this);

            p.then(function () {
                Iznik.ABTestShown('PromptApp', 'Android');
                Iznik.ABTestShown('PromptApp', 'IOS');
            });

            return (p);
        }
    });
});