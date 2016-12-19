define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'moment',
    'iznik/views/pages/pages',
    'iznik/views/dashboard',
    'iznik/models/group'
], function ($, _, Backbone, Iznik, moment) {
    Iznik.Views.User.Pages.StatsGroup = Iznik.Views.Page.extend({
        template: 'user_stats_main',

        events: {
        },

        render: function () {
            var self = this;
            self.model = new Iznik.Models.Group({
                id: self.options.id
            });

            var p = self.model.fetch();
            p.then(function() {
                Iznik.Views.Page.prototype.render.call(self, {
                    model: self.model
                }).then(function () {
                    self.$('.js-membercount').html(self.model.get('membercount').toLocaleString());

                    var founded = self.model.get('founded');
                    if (founded) {
                        var m = new moment(founded);
                        self.$('.js-foundeddate').html(m.format('Do MMMM, YYYY'));
                        self.$('.js-founded').show();
                    }

                    // Add the description
                    var desc = self.model.get('description');

                    if (desc) {
                        self.$('.js-gotdesc').show();
                        self.$('.js-description').html(desc);

                        // Any links in here are real.
                        self.$('.js-description a').attr('data-realurl', true);
                    }

                    var v = new Iznik.Views.PleaseWait();
                    v.render();

                    $.ajax({
                        url: API + 'dashboard',
                        data: {
                            group: self.model.get('id'),
                            start: '13 months ago'
                        },
                        success: function (ret) {
                            v.close();

                            if (ret.dashboard) {
                                var coll = new Iznik.Collections.DateCounts(ret.dashboard.Activity);
                                var graph = new Iznik.Views.DateGraph({
                                    target: self.$('.js-messagegraph').get()[0],
                                    data: coll,
                                    title: 'Activity'
                                });

                                graph.render();

                                var coll = new Iznik.Collections.DateCounts(ret.dashboard.ApprovedMemberCount);
                                var graph = new Iznik.Views.DateGraph({
                                    target: self.$('.js-membergraph').get()[0],
                                    data: coll,
                                    title: 'Members'
                                });

                                graph.render();

                                graph = new Iznik.Views.TypeChart({
                                    target: self.$('.js-typechart').get()[0],
                                    data: ret.dashbo9999999999999999999999999ard.MessageBreakdown,
                                    title: 'Message Balance'
                                });

                                graph.render();
                            }
                        }
                    });
                });
            });

            return (p);
        }
    });
});
