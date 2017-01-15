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
            var p;

            if (self.options.id) {
                // Called for a specific group
                self.model = new Iznik.Models.Group({
                    id: self.options.id
                });

                p = self.model.fetch();
            } else {
                // We want all of them.
                var sitename = $('meta[name=izniksitename]').attr("content");
                var sitedesc = $('meta[name=izniksitedesc]').attr("content");

                self.model = new Iznik.Model({
                    'namedisplay': sitename,
                    'tagline': sitedesc
                });

                p = resolvedPromise(self);
            }

            p.then(function() {
                Iznik.Views.Page.prototype.render.call(self, {
                    model: self.model
                }).then(function () {
                    var count = self.model.get('membercount');
                    if (count) {
                        self.$('.js-membercount').html(count.toLocaleString());
                    }

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

                    var v = new Iznik.Views.PleaseWait({
                        timeout: 1
                    });
                    v.render();

                    $.ajax({
                        url: API + 'dashboard',
                        data: {
                            group: self.model.get('id'),
                            start: '13 months ago',
                            grouptype: 'Freegle',
                            systemwide: self.options.id ? false : true
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

                                if (ret.dashboard.hasOwnProperty('ApprovedMemberCount')) {
                                    self.$('.js-membergraphholder').show();
                                    var coll = new Iznik.Collections.DateCounts(ret.dashboard.ApprovedMemberCount);
                                    var graph = new Iznik.Views.DateGraph({
                                        target: self.$('.js-membergraph').get()[0],
                                        data: coll,
                                        title: 'Members'
                                    });

                                    graph.render();
                                }

                                // We only want to show Offer vs Wanted.
                                delete ret.dashboard.MessageBreakdown.Admin;
                                delete ret.dashboard.MessageBreakdown.Other;
                                delete ret.dashboard.MessageBreakdown.Received;
                                delete ret.dashboard.MessageBreakdown.Taken;

                                // Make sure they're percentages so that people don't try to cross-reference
                                // with the activity numbers, which wouldn't be possible.
                                var total = ret.dashboard.MessageBreakdown.Offer + ret.dashboard.MessageBreakdown.Wanted;
                                ret.dashboard.MessageBreakdown.Offer = Math.round(100 * ret.dashboard.MessageBreakdown.Offer / total);
                                ret.dashboard.MessageBreakdown.Wanted = Math.round(100 * ret.dashboard.MessageBreakdown.Wanted / total);

                                graph = new Iznik.Views.TypeChart({
                                    target: self.$('.js-balancechart').get()[0],
                                    data: ret.dashboard.MessageBreakdown,
                                    title: 'Message Balance'
                                });

                                graph.colours = [
                                    'green',
                                    'blue'
                                ];

                                graph.render();
                                
                                // Success
                                // Make sure they're percentages so that people don't try to cross-reference
                                // with the activity numbers, which wouldn't be possible.
                                var offer = ret.dashboard.Outcomes.Offer;
                                var total = taken = offerwithdrawn = 0;
                                _.each(offer, function(val) {
                                    total += val.count;
                                    if (val.outcome == 'Taken') {
                                        taken = val.count;
                                    } else if (val.outcome == 'Withdrawn') {
                                        offerwithdrawn = val.count;
                                    }
                                });

                                var data = {
                                    'Taken': Math.round((100 * taken) / total),
                                    'Withdrawn': Math.round((100 * offerwithdrawn) / total)
                                };
                                
                                graph = new Iznik.Views.TypeChart({
                                    target: self.$('.js-offeroutcome').get()[0],
                                    data: data,
                                    title: 'Offer Outcome'
                                });

                                graph.colours = [
                                    'green',
                                    'blue'
                                ];

                                graph.render();

                                var wanted = ret.dashboard.Outcomes.Wanted;
                                var total = received = wantedwithdrawn = 0;
                                _.each(wanted, function(val) {
                                    total += val.count;
                                    if (val.outcome == 'Received') {
                                        received = val.count;
                                    } else if (val.outcome == 'Withdrawn') {
                                        wantedwithdrawn = val.count;
                                    }
                                });

                                var data = {
                                    'Received': Math.round((100 * received) / total),
                                    'Withdrawn': Math.round((100 * wantedwithdrawn) / total)
                                };

                                graph = new Iznik.Views.TypeChart({
                                    target: self.$('.js-wantedoutcome').get()[0],
                                    data: data,
                                    title: 'Wanted Outcome'
                                });

                                graph.colours = [
                                    'green',
                                    'blue'
                                ];

                                graph.render();

                                // Weights - show per month.
                                var months = {};

                                _.each(ret.dashboard.Weight, function(ent) {
                                    var date = new moment(ent.date);
                                    var key = date.format('01 MMM YYYY');
                                    if (ent.count > 0) {
                                        if (key in months) {
                                            months[key] = months[key] + ent.count;
                                        } else {
                                            months[key] = ent.count;
                                        }
                                    }
                                });

                                var data = [];

                                for (var key in months) {
                                    data.push({
                                        date: key,
                                        count: months[key]
                                    });
                                }

                                var graph = new Iznik.Views.DateBar({
                                    target: self.$('.js-weightgraph').get()[0],
                                    data: new Iznik.Collections.DateCounts(data),
                                    title: 'Weights (kg)',
                                    hAxisFormat: 'MMM yyyy'
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

