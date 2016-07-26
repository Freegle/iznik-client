define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    "iznik/modtools",
    'moment',
    'iznik/views/pages/pages',
    'iznik/views/pages/modtools/messages_approved',
    'iznik/models/user/alert',
    'iznik/views/user/user',
    'tinymce'
], function($, _, Backbone, Iznik, moment) {
    Iznik.Views.ModTools.Pages.Support = Iznik.Views.Page.extend({
        modtools: true,

        template: "modtools_support_main",

        events: {
            'click .js-searchuser': 'searchUser',
            'click .js-searchmsg': 'searchMessage',
            'keyup .js-searchuserinp': 'keyup',
            'click .js-sendalert': 'sendAlert',
            'click .js-getalerts': 'getAlerts'
        },

        keyup: function (e) {
            // Search on enter.
            if (e.which == 13) {
                this.$('.js-searchuser').click();
            }
        },

        searchUser: function () {
            var self = this;

            self.$('.js-loading').addClass('hidden');

            self.collection = new Iznik.Collections.Members.Search(null, {
                collection: 'Approved',
                search: this.$('.js-searchuserinp').val().trim()
            });

            self.collectionView = new Backbone.CollectionView({
                el: self.$('.js-searchuserres'),
                modelView: Iznik.Views.ModTools.Member.SupportSearch,
                collection: self.collection
            });

            var v = new Iznik.Views.PleaseWait({
                timeout: 1
            });
            v.render();

            self.collectionView.render();
            this.collection.fetch({
                remove: true,
                data: {
                    limit: 100
                },
                success: function (collection, response, options) {
                    v.close();

                    if (collection.length == 0) {
                        self.$('.js-none').fadeIn('slow');
                    }
                }
            });
        },

        searchMessage: function () {
            var self = this;

            self.messages = new Iznik.Collections.Messages.SearchAll(null, {
                modtools: true,
                searchmess: self.$('.js-searchmsginp').val(),
                collection: 'Approved'
            });

            self.messagesView = new Backbone.CollectionView({
                el: self.$('.js-searchmsgres'),
                modelView: Iznik.Views.ModTools.Message.SupportSearchResult,
                modelViewOptions: {
                    collection: self.messages,
                    page: self
                },
                collection: self.messages
            });

            self.messagesView.render();

            var v = new Iznik.Views.PleaseWait();
            v.render();

            self.messages.fetch({
                remove: true,
                data: {
                    search: self.$('.js-searchmsginp').val(),
                },
                success: function (collection, response, options) {
                    v.close();

                    if (collection.length == 0) {
                        self.$('.js-msgnone').fadeIn('slow');
                    } else {
                        self.$('.js-msgnone').hide();
                    }
                }
            });
        },

        sendAlert: function () {
            var self = this;
            var html = tinyMCE.activeEditor.getContent({format: 'raw'});

            // If we've not put anything in the HTML version we get some placeholder HTML.
            html = html == '<p><br data-mce-bogus="1"></p>' ? null : html;

            $.ajax({
                type: 'PUT',
                url: API + 'alert',
                data: {
                    groupid: self.$('.js-grouplist').val(),
                    from: self.$('.js-mailfrom').val(),
                    subject: self.$('.js-mailsubj').val(),
                    text: self.$('.js-mailtext').val(),
                    html: html,
                    askclick: self.$('.js-askclick').val(),
                    tryhard: self.$('.js-tryhard').val()
                }, success: function (ret) {
                    if (ret.ret == 0) {
                        self.$('.js-mailsuccess').fadeIn('slow');
                    } else {
                        self.$('.js-mailerror').fadeIn('slow');
                    }
                }, error: function () {
                    self.$('.js-mailerror').fadeIn('slow');
                }
            });
        },

        getAlerts: function() {
            var self = this;
            self.$('.js-getalerts').hide();
            self.$('.js-alertshdr').fadeIn('slow');
            self.$('.js-alerts').show();

            $.ajax({
                url: API + 'alert',
                type: 'GET',
                success: function(ret) {
                    var coll = new Iznik.Collection(ret.alerts);
                    var alerts = new Backbone.CollectionView({
                        el: self.$('.js-alerts'),
                        modelView: Iznik.Views.ModTools.Alert,
                        collection: coll
                    });

                    alerts.render();
                }
            })
        },

        render: function () {
            var p = Iznik.Views.Page.prototype.render.call(this);
            p.then(function(self) {
                // TODO This should be more generic, but it's really part of hosting multiple networks on the same
                // server, which we don't do.
                var type = Iznik.Session.isAdmin() ? null : 'Freegle';
                type = 'Freegle';
                $.ajax({
                    url: API + 'groups',
                    data: {
                        'grouptype': type
                    }, success: function (ret) {
                        ret.groups = _.sortBy(ret.groups, function(group) { return group.namedisplay });
                        _.each(ret.groups, function (group) {
                            self.$('.js-grouplist').append('<option value="' + group.id + '"></option>');
                            self.$('.js-grouplist option:last').html(group.namedisplay);
                        })
                    }
                });

                tinyMCE.init({
                    selector: '#mailhtml',
                    plugins: 'link textcolor',
                    height: 300,
                    menubar: false,
                    elementpath: false,
                    toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright |  bullist numlist link | forecolor styleselect formatselect fontselect fontsizeselect | cut copy paste'
                });

                self.delegateEvents();
            });

            return(p);
        }
    });

    Iznik.Views.ModTools.Alert = Iznik.View.extend({
        tagName: 'li',

        className: 'row',

        template: 'modtools_support_alert',

        events: {
            'click .js-showstats': 'showStats'
        },

        showStats: function() {
            var self = this;

            // Get up to date stats.
            var mod = new Iznik.Models.Alert({
                id: this.model.get('id')
            });
            mod.fetch().then(function() {
                var v = new Iznik.Views.ModTools.Alert.Stats({
                    model: mod
                });
                v.render();
            })
        },

        render: function() {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                var mom = new moment(self.model.get('created'));
                self.$('.js-created').html(mom.format('MMMM Do YYYY, h:mm:ssa'));
                if (self.model.get('complete')) {
                    var mom = new moment(self.model.get('complete'));
                    self.$('.js-complete').html(mom.format('MMMM Do YYYY, h:mm:ssa'));
                }
            })

            return(p);
        }
    });

    Iznik.Views.ModTools.Alert.Stats = Iznik.Views.Modal.extend({
        template: 'modtools_support_alertstats',

        render: function() {
            var self = this;

            function apiLoaded() {
                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                _.defer(function () {

                    var colors = [
                        'green',
                        'orange'
                    ];

                    var stats = self.model.get('stats');
                    var data;
                    console.log("Stats", stats);

                    // First the group chart - this shows what happened on a per-group basis.
                    var reached = 0;
                    var total = 0;
                    var unreached = 0;
                    _.each(stats.responses.groups, function(group) {
                        total++;
                        _.each(group.summary, function(result) {
                            if (result.rsp == 'Reached') {
                                reached ++;;
                            }
                        })
                    });

                    data = new google.visualization.DataTable();
                    data.addColumn('string', 'Result');
                    data.addColumn('number', 'Count');
                    data.addRows([
                        [ 'Reached', reached ],
                        [ 'No Response', total - reached ]
                    ]);

                    self.groupchart = new google.visualization.PieChart(self.$('.js-groups').get()[0]);
                    chartOptions = {
                        title: "Groups",
                        chartArea: {'width': '80%', 'height': '80%'},
                        colors: colors,
                        slices2: {
                            1: {offset: 0.2},
                            2: {offset: 0.2}
                        }
                    };

                    self.groupchart.draw(data, chartOptions);

                    // Now the volunteers chart - this shows what happened on a per-volunteer basis.
                    data = new google.visualization.DataTable();
                    data.addColumn('string', 'Result');
                    data.addColumn('number', 'Count');
                    data.addRows([
                        [ 'Reached', stats.responses.mods.reached ],
                        [ 'No Response', stats.responses.mods.none ]
                    ]);

                    self.volschart = new google.visualization.PieChart(self.$('.js-mods').get()[0]);
                    chartOptions = {
                        title: "Volunteers",
                        chartArea: {'width': '80%', 'height': '80%'},
                        colors: colors,
                        slices2: {
                            1: {offset: 0.2},
                            2: {offset: 0.2}
                        }
                    };

                    self.volschart.draw(data, chartOptions);

                    // Now the owner address chart.
                    data = new google.visualization.DataTable();
                    data.addColumn('string', 'Result');
                    data.addColumn('number', 'Count');
                    data.addRows([
                        [ 'Reached', stats.responses.owner.reached ],
                        [ 'No Response', stats.responses.owner.none ]
                    ]);

                    self.ownchart = new google.visualization.PieChart(self.$('.js-owner').get()[0]);
                    chartOptions = {
                        title: '-owner Address',
                        chartArea: {'width': '80%', 'height': '80%'},
                        colors: colors,
                        slices2: {
                            1: {offset: 0.2},
                            2: {offset: 0.2}
                        }
                    };

                    self.ownchart.draw(data, chartOptions);
                });
            }

            // We have to load the chart after the modal is shown, otherwise odd things happen on the second such
            // modal we open.
            $('body').one('shown.bs.modal', '#alertstats', function(){
                google.load('visualization', '1.0', {
                    'packages':['corechart', 'annotationchart'],
                    'callback': apiLoaded
                });
            });

            this.open(this.template);

            return(this);
        }
    });

    // TODO This feels like an abuse of the memberships API just to use the search mechanism.  Should there be a user
    // search instead?
    Iznik.Views.ModTools.Member.SupportSearch = Iznik.View.extend({
        template: 'modtools_support_member',

        render: function () {
            var p = Iznik.View.prototype.render.call(this);
            p.then(function(self) {
                // Our user
                var v = new Iznik.Views.ModTools.User({
                    model: self.model
                });

                v.render().then(function (v) {
                    self.$('.js-user').html(v.el);

                    // We are not in the context of a specific group here, so the general remove/ban buttons don't make sense.
                    self.$('.js-ban, .js-remove').closest('li').remove();
                });
                
                // Add any emails
                self.$('.js-otheremails').empty();
                _.each(self.model.get('otheremails'), function (email) {
                    if (email.preferred) {
                        self.$('.js-email').append(email.email);
                    } else {
                        var mod = new Iznik.Model(email);
                        var v = new Iznik.Views.ModTools.Message.OtherEmail({
                            model: mod
                        });
                        v.render().then(function (v) {
                            self.$('.js-otheremails').html(v.el);
                        });
                    }
                });

                // Add any sessions.
                self.sessionCollection = new Iznik.Collection(self.model.get('sessions'));

                self.sessionCollectionView = new Backbone.CollectionView({
                    el: self.$('.js-sessions'),
                    modelView: Iznik.Views.ModTools.Member.Session,
                    collection: self.sessionCollection
                });

                self.sessionCollectionView.render();

                // Add any group memberships.
                self.$('.js-memberof').empty();
                _.each(self.model.get('memberof'), function (group) {
                    var mod = new Iznik.Model(group);
                    var v = new Iznik.Views.ModTools.Member.Of({
                        model: mod,
                        user: self.model
                    });
                    
                    v.render().then(function (v) {
                        self.$('.js-memberof').append(v.el);
                    });
                });

                self.$('.js-applied').empty();
                _.each(self.model.get('applied'), function (group) {
                    var mod = new Iznik.Model(group);
                    var v = new Iznik.Views.ModTools.Member.Applied({
                        model: mod
                    });
                    v.render().then(function (v) {
                        self.$('.js-applied').append(v.el);
                    });
                });

                // Add the default standard actions.
                self.model.set('fromname', self.model.get('displayname'));
                self.model.set('fromaddr', self.model.get('email'));
                self.model.set('fromuser', self.model);

                new Iznik.Views.ModTools.StdMessage.Button({
                    model: new Iznik.Model({
                        title: 'Mail',
                        action: 'Leave Approved Member',
                        member: self.model
                    })
                }).render().then(function (v) {
                    self.$('.js-stdmsgs').append(v.el);
                    self.$('.timeago').timeago();
                });
            });

            return (p);
        }
    });

    Iznik.Views.ModTools.Message.SupportSearchResult = Iznik.Views.ModTools.Message.Approved.extend({
    });

    Iznik.Views.ModTools.Member.Session = Iznik.View.Timeago.extend({
        template: 'modtools_support_session',
        
        events: {
            'click .js-play': 'play'
        },
        
        play: function() {
            var width = window.innerWidth * 0.66 ;
            var height = width * window.innerHeight / window.innerWidth ;
            window.open('/modtools/replay/' + this.model.get('sessionid'), 'Session Replay', 'width=' + width + ', height=' + height + ', top=' + ((window.innerHeight - height) / 2) + ', left=' + ((window.innerWidth - width) / 2));
        }
    });
});