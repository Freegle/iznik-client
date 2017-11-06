define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    "iznik/modtools",
    "iznik/views/pages/modtools/settings",
    'iznik/views/pages/pages'
], function($, _, Backbone, Iznik) {
        Iznik.Views.ModTools.Pages.Landing = Iznik.Views.Page.extend({
            modtools: true,

            template: "modtools_landing_main",

            selected: null,

            updateGraphs: function() {
                var data = {};
                var showYahoo = true;

                if (this.selected == -2) {
                    data.systemwide = true;
                } else if (this.selected == -1) {
                    data.allgroups = true;
                } else {
                    data.group = this.selected;
                    var group = Iznik.Session.getGroup(data.group);
                    if (!group.get('onyahoo')) {
                        showYahoo = false;
                    }
                }

                var statsGroupType = $('#statsGroupType');
                if (statsGroupType.val() != 'null') {
                    data.grouptype = statsGroupType.val();
                }

                var v = new Iznik.Views.PleaseWait();
                v.render();

                $.ajax({
                    url: API + 'dashboard',
                    data: data,
                    success: function(ret) {
                        v.close();

                        if (ret.dashboard) {
                            if (Iznik.Session.isAdminOrSupport()) {
                                var pre = ret.dashboard.prerender;
                                var age = (new Date()).getTime() - (new Date(pre)).getTime();

                                if (age > 24 * 60 * 60 * 1000) {
                                    self.$('.js-prerenderwrapper').fadeIn('slow');
                                }
                            }

                            if (Iznik.Session.isFreegleMod() &&
                                (ret.dashboard.Happy.length + ret.dashboard.Unhappy.length + ret.dashboard.Fine.length > 0)) {
                                self.$('.js-freegleonly').show();
                                var happy = new Iznik.Collections.DateCounts(ret.dashboard.Happy);
                                var fine = new Iznik.Collections.DateCounts(ret.dashboard.Fine);
                                var unhappy = new Iznik.Collections.DateCounts(ret.dashboard.Unhappy);
                                var graph = new Iznik.Views.StackGraph({
                                    target: self.$('.js-happinessgraph').get()[0],
                                    data: [
                                        {
                                            tag: 'Happy',
                                            data: happy,
                                            colour: 'green'
                                        },
                                        {
                                            tag: 'Fine',
                                            data: fine,
                                            colour: 'lightblue'
                                        },
                                        {
                                            tag: 'Unhappy',
                                            data: unhappy,
                                            colour: 'red'
                                        }
                                    ]
                                });

                                graph.render();

                                var coll = new Iznik.Collections.DateCounts(ret.dashboard.SupportQueries);
                                var graph = new Iznik.Views.DateGraph({
                                    target: self.$('.js-supportqueriesgraph').get()[0],
                                    data: coll,
                                    title: 'Support Queries'
                                });

                                graph.render();
                            } else {
                                self.$('.js-freegleonly').hide();
                            }

                            var coll = new Iznik.Collections.DateCounts(ret.dashboard.ApprovedMessageCount);
                            var graph = new Iznik.Views.DateGraph({
                                target: self.$('.js-messagegraph').get()[0],
                                data: coll,
                                title: 'Message History'
                            });

                            graph.render();

                            coll = new Iznik.Collections.DateCounts(ret.dashboard.SpamMessageCount);
                            graph = new Iznik.Views.DateGraph({
                                target: self.$('.js-spammessagegraph').get()[0],
                                data: coll,
                                title: 'Spam Detection'
                            });

                            graph.render();

                            coll = new Iznik.Collections.DateCounts(ret.dashboard.SpamMemberCount);
                            graph = new Iznik.Views.DateGraph({
                                target: self.$('.js-spammembergraph').get()[0],
                                data: coll,
                                title: 'Spammer Detection'
                            });

                            graph.render();

                            graph = new Iznik.Views.TypeChart({
                                target: self.$('.js-typechart').get()[0],
                                data: ret.dashboard.MessageBreakdown,
                                title: 'Message Balance'
                            });

                            graph.render();

                            if (showYahoo) {
                                graph = new Iznik.Views.DeliveryChart({
                                    target: self.$('.js-deliverychart').get()[0],
                                    data: ret.dashboard.YahooDeliveryBreakdown,
                                    title: 'How Yahoo users get mail (excludes FD/TN)'
                                });

                                graph.render();

                                graph = new Iznik.Views.PostingChart({
                                    target: self.$('.js-postingchart').get()[0],
                                    data: ret.dashboard.YahooPostingBreakdown,
                                    title: 'Yahoo users\' posting status'
                                });

                                graph.render();
                            } else {
                                self.$('.js-deliverychart').empty();
                                self.$('.js-postingchart').empty();
                            }

                            graph = new Iznik.Views.SourceChart({
                                target: self.$('.js-sourcechart').get()[0],
                                data: ret.dashboard.PostMethodBreakdown,
                                title: 'How people send messages'
                            });

                            graph.render();

                            if (ret.dashboard.hasOwnProperty('modinfo')) {
                                self.$('.js-modlist').empty();
                                self.$('.js-modinfo').show();
                                _.each(ret.dashboard.modinfo, function(modinfo) {
                                    if (modinfo.lastactive) {
                                        var v = new Iznik.Views.ModTools.Pages.Landing.ModInfo({
                                            model: new Iznik.Model(modinfo)
                                        });
                                        v.render().then(function() {
                                            self.$('.js-modlist').append(v.el);
                                        });
                                    }
                                })
                            } else {
                                self.$('.js-modinfo').hide();
                            }
                        }
                    }
                })
            },

            render: function() {
                var messagetitle, spamtitle, domaintitle;
                var p = Iznik.Views.Page.prototype.render.call(this);
                p.then(function(self) {
                    // var v = new Iznik.Views.ModTools.Pages.Landing.FreeStock();
                    // v.render().then(function(v) {
                    //     self.$('.js-freestock').html(v.el);
                    // });
                    //
                    // Get Yahoo login info
                    new majax({
                        type: "GET",
                        url: "https://groups.yahoo.com/neo",
                        success: function (ret) {
                            var re =/data-userid="(.*?)"/g;
                            var matches = re.exec(ret);

                            if (matches && matches.length > 0 && matches[0].length > 0) {
                                var yid = matches[1];
                                var p = yid.indexOf('@');
                                yid = p == -1 ? yid : yid.substring(0, p);
                                self.$('.js-yahooinfo').html("You're logged in to Yahoo as " + yid + ".");
                                Iznik.Session.set('loggedintoyahooas', yid);

                            } else {
                                self.$('.js-yahooinfo').html("You aren't logged in to Yahoo.");
                                Iznik.Session.unset('loggedintoyahooas');
                            }
                        }, error: function() {
                            self.$('.js-yahooinfo').html("You don't have the browser plugin installed.");
                            Iznik.Session.unset('loggedintoyahooas');
                        }
                    });

                    self.$('.js-grouptype').selectPersist();
                    self.$('.js-grouptype').change(function() {
                        self.updateGraphs.apply(self);
                    });

                    var v = new Iznik.Views.Group.Select({
                        systemWide: true,
                        all: true,
                        mod: true,
                        id: 'landingGroupSelect'
                    });

                    self.listenTo(v, 'selected', function(selected) {
                        self.selected = selected;
                        self.updateGraphs();
                    });

                    // Render after the listen to as they are called during render.
                    v.render().then(function(v) {
                        self.$('.js-groupselect').html(v.el);
                    });

                    // Add any warnings to prompt action.
                    var v = new Iznik.Views.ModTools.Settings.MissingProfile();
                    v.render().then(function() {
                        self.$('.js-missingprofile').html(v.el);
                    });
                    var w = new Iznik.Views.ModTools.Settings.MissingFacebook();
                    w.render().then(function() {
                        self.$('.js-missingfacebook').html(w.el);
                    });
                    var x = new Iznik.Views.ModTools.Settings.MissingTwitter();
                    x.render().then(function() {
                        self.$('.js-missingtwitter').html(x.el);
                    });
                    var w = new Iznik.Views.ModTools.Settings.MissingFacebookBuySell();
                    w.render().then(function() {
                        self.$('.js-missingfacebookbuysell').html(w.el);
                    });
                });

                return(p);
            }
        });

        Iznik.Views.ModTools.Pages.Landing.ModInfo = Iznik.View.Timeago.extend({
            template: 'modtools_landing_modinfo'
        });

        Iznik.Views.ModTools.Pages.Landing.FreeStock = Iznik.Views.Help.Box.extend({
            template: 'modtools_landing_freestock',

            events: {
                'click .js-info': 'info'
            },

            info: function() {
                var v = new Iznik.Views.ModTools.Pages.Landing.FreeStock.Info();
                v.render();
            }
        });

        Iznik.Views.ModTools.Pages.Landing.FreeStock.Info = Iznik.Views.Modal.extend({
            template: 'modtools_landing_freestockinfo'
        });
});