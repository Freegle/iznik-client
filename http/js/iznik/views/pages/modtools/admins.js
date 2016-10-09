define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    "iznik/modtools",
    "iznik/models/admin",
    'iznik/views/pages/pages'
], function($, _, Backbone, moment, Iznik) {
    Iznik.Views.ModTools.Pages.Admins = Iznik.Views.Page.extend({
        modtools: true,

        template: "modtools_admins_main",

        events: {
            'click .js-send': 'send'
        },

        send: function(e) {
            e.preventDefault();
            e.stopPropagation();

            var admin = new Iznik.Models.Admin({
                groupid: this.groupSelect.get(),
                subject: this.$('#js-subject').val(),
                text: this.$('#js-text').val()
            });

            console.log("Modal", admin);

            if (admin.get('groupid') && admin.get('subject') && admin.get('text')) {
                admin.save().then(function() {
                    (new Iznik.Views.ModTools.Pages.Admins.Sent()).render();
                })
            }
        },

        render: function () {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                self.groupSelect = new Iznik.Views.Group.Select({
                    systemWide: false,
                    all: false,
                    mod: true,
                    choose: true,
                    id: 'adminGroupSelect'
                });

                self.groupSelect.render().then(function () {
                    self.$('.js-groupselect').html(self.groupSelect.el);
                });
            });

            return (p);
        }
    });

    Iznik.Views.ModTools.Pages.Admins.Sent = Iznik.Views.Modal.extend({
        template: 'modtools_admins_sent'
    });
});