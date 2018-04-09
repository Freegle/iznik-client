import 'bootstrap-fileinput';

var tpl = require('iznik/templateloader');
var template = tpl.template;
var templateFetch = tpl.templateFetch;

define([
    'jquery',
    'underscore',
    'backbone',
    'moment',
    'iznik/base',
    'backgrid',
    "iznik/modtools",
    'iznik/models/shortlinks',
    'iznik/views/pages/pages',
], function($, _, Backbone, moment, Iznik, Backgrid) {
    Iznik.Views.ModTools.Pages.Shortlinks = Iznik.Views.Page.extend({
        modtools: true,

        template: "modtools_shortlinks_main",

        noGoogleAds: true,

        render: function() {
            var self = this;

            var p = Iznik.Views.Page.prototype.render.call(this);

            p.then(function (self) {
                var v = new Iznik.Views.ModTools.Shortlinks.List();
                v.render().then(function() {
                    self.$('.js-shortlinks').html(v.$el);
                })
            });

            return (p)
        }
    });

    Iznik.Views.ModTools.Shortlinks = {};

    Iznik.Views.ModTools.Shortlinks.List = Iznik.View.extend({
        template: 'modtools_shortlinks_list',

        events: {
            'click .js-getlinks': 'getLinks'
        },

        getLinks: function () {
            var self = this;

            self.wait = new Iznik.Views.PleaseWait({
                timeout: 1
            });
            self.wait.render();

            self.allLinks = new Iznik.Collections.Shortlink();

            // Create a backgrid for the groups.
            self.columns = [{
                name: 'id',
                label: 'ID',
                editable: false,
                cell: Backgrid.IntegerCell
            }, {
                name: 'name',
                label: 'Name',
                editable: false,
                cell: 'string'
            }, {
                name: 'type',
                label: 'Type',
                editable: false,
                cell: 'string'
            }, {
                name: 'groupid',
                label: 'Group ID',
                editable: false,
                cell: 'integer'
            }, {
                name: 'nameshort',
                label: 'Group Name',
                editable: false,
                cell: 'string'
            }, {
                name: 'clicks',
                label: 'Clicks',
                editable: false,
                cell: 'integer'
            }, {
                name: 'url',
                label: 'URL',
                editable: false,
                cell: 'string'
            }];

            self.grid = new Backgrid.Grid({
                columns: self.columns,
                collection: self.allLinks
            });

            self.$(".js-shortlinkslist").html(self.grid.render().el);

            self.allLinks.fetch().then(function () {
                self.wait.close();
            });
        },
    });
});
