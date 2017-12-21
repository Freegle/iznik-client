define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'iznik/facebook',
    'iznik/views/pages/pages',
    'typeahead',
    'iznik/models/donations',
    'iznik/views/postaladdress'
], function($, _, Backbone, Iznik, FBLoad) {
    Iznik.Views.SupportUs = Iznik.Views.Modal.extend({
        events: {
            'click .js-invite': 'doInvite',
            'click .js-clickdonate': 'clickDonate'
        },

        clickDonate: function() {
            Iznik.ABTestAction('SupportUs', this.template);
        },

        doInvite: function() {
            var self = this;
            var email = self.$('.js-inviteemail').val();
            Iznik.ABTestAction('SupportUs', 'user_support_invite');

            if (Iznik.isValidEmailAddress(email)) {
                $.ajax({
                    url: API + 'invitation',
                    type: 'PUT',
                    data: {
                        email: email
                    },
                    complete: function() {
                        self.$('.js-inviteemail').val('');
                        self.$('.js-thanks').slideDown('slow');
                        _.delay(function() {
                            self.$('.js-thanks').slideUp('slow');
                        }, 30000);
                    }
                });
            }
        },

        render: function() {
            var self = this;

            var lastask = Storage.get('donationlastask');
            var now = (new Date()).getTime();
            var p;

            if (!lastask || (now - lastask > 7 * 24 * 60 * 60 * 1000)) {
                p = Iznik.ABTestGetVariant('SupportUs', function(variant) {
                    self.template = variant.variant;
                    var showglobal = false;

                    if (variant.variant.indexOf('group') !== -1) {
                        // Get home group to ask for per-group donation.
                        var homegroup = Storage.get('myhomegroup');
                        var group = Iznik.Session.getGroup(homegroup);

                        if (!group) {
                            var groups = Iznik.Session.get('groups');
                            if (groups.length > 0) {
                                homegroup = groups.at(0).get('id');
                                group = groups.at(0);
                            }
                        }

                        if (group) {
                            self.donations = new Iznik.Models.Donations();
                            self.donations.fetch({
                                data: {
                                    groupid: homegroup
                                }
                            }).then(function () {
                                group.set('donations', self.donations.attributes);

                                if (self.donations.attributes.raised < self.donations.attributes.target) {
                                    // Not reached the target - show the per-group appeal.
                                    self.model = group;
                                } else {
                                    // Reached the group target - show the global appeal
                                    homegroup = null;
                                    self.template = 'user_support_askdonation';
                                }

                                var p = Iznik.Views.Modal.prototype.render.call(self);
                                p.then(function () {
                                    var w = new Iznik.Views.DonationThermometer({
                                        groupid: homegroup
                                    });
                                    w.render().then(function () {
                                        Storage.set('donationlastask', now);
                                        self.$('.js-thermometer').html(w.$el);
                                    });
                                });
                            });
                        }
                    } else {
                        // Global thermometer.
                        var p = Iznik.Views.Modal.prototype.render.call(self);
                        p.then(function () {
                            var w = new Iznik.Views.DonationThermometer({
                                groupid: null
                            });
                            w.render().then(function () {
                                Storage.set('donationlastask', now);
                                self.$('.js-thermometer').html(w.$el);
                            });
                        });
                    }

                    Iznik.ABTestShown('SupportUs', self.template);
                });
            } else {
                // If we're not asking for a donation, offer business cards, unless the group forbids it.
                var homegroup = Storage.get('myhomegroup');
                var cardsallowed = true;
                if (homegroup) {
                    var settings = Iznik.Session.getSettings(homegroup);
                    if (settings.hasOwnProperty('businesscards')) {
                        cardsallowed = settings.businesscards;
                    }
                }

                if (cardsallowed) {
                    (new Iznik.Views.User.BusinessCards()).render();
                } else {
                    // Invite instead
                    self.template = 'user_support_invite';
                    p = Iznik.Views.Modal.prototype.render.call(self);
                }
            }

            return(p);
        }
    });

    Iznik.Views.DonationThermometer = Iznik.View.extend({
        template: "user_thermometer",

        render: function() {
            var self = this;

            var p = Iznik.View.prototype.render.call(this);
            p.then(function () {
                self.donations = new Iznik.Models.Donations();
                self.donations.fetch({
                    data: {
                        groupid: self.options.groupid
                    }
                }).then(function() {
                    self.waitDOM(self, function() {
                        var valor1 = self.donations.get('raised');
                        valor1 = valor1 ? valor1 : 0;
                        var maxim = self.donations.get('target');

                        // We might exceed the target.
                        maxim = Math.round(Math.max(valor1 * 1.2, maxim) / 10) * 10;

                        var canvas = document.getElementById("termome");
                        var valor = valor1 / maxim;
                        var ctx = canvas.getContext("2d");
                        var alto = canvas.height * 0.9;
                        var radio = canvas.width / 2;
                        var grad;
                        var lado;
                        ctx.translate(radio, parseInt(alto-radio));
                        radio = radio * 0.6;
                        alto = alto*0.75;
                        var ancho = radio / 2;
                        var ymin = radio * 1.2;
                        var ymax = alto + ancho;
                        var yinc = (ymax-ymin) / 10;
                        var xx1 = 0;
                        var xxinc = parseInt(valor1 / 50);
                        if (xxinc == 0) xxinc = 1;
                        var AA = setInterval(DibujaTermo, 40);
                        var target = maxim;
                        var thermlines = 15;

                        function DibujaTermo() {
                            valor = xx1 / maxim;
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(-alto*4, -alto*4, alto*8, alto*8);
                            DibujaTubo();
                            DibujaBola();
                            xx1 += xxinc;
                            if (xx1 > valor1) {
                                xx1 = valor1;
                                ctx.fillStyle = '#fff';
                                ctx.fillRect(-alto*4, -alto*4, alto*8, alto*8);
                                DibujaTubo();
                                DibujaBola();
                                clearInterval(AA);
                            };
                        };

                        function DibujaTubo(){
                            var y1 = -(ymin + (yinc*10*valor));

                            //Dibuja Tubo Lleno
                            grad = ctx.createLinearGradient(-ancho, 0, ancho, 0);
                            grad.addColorStop(0, '#85d841');
                            grad.addColorStop(0.5, '#61AE24');
                            grad.addColorStop(1,'#85d841');
                            ctx.fillStyle = grad;
                            ctx.fillRect(-ancho, -ancho, ancho*2, y1);

                            //Dibuja Tubo Vacio
                            grad = ctx.createLinearGradient(-ancho, 0, ancho, 0);
                            grad.addColorStop(0, '#ddd');
                            grad.addColorStop(0.5, '#fff');
                            grad.addColorStop(1,'#ddd');
                            ctx.fillStyle = grad;
                            ctx.fillRect(-ancho, y1, ancho*2, -(alto+ancho+y1));

                            //Dibuja Cupula
                            grad = ctx.createRadialGradient( ancho*0.1, -(alto + ancho*0.3), 0, ancho*0.1, -(alto + ancho*0.3), ancho*1.8 );
                            grad.addColorStop(0, '#fff');
                            grad.addColorStop(1, '#ddd');
                            ctx.fillStyle = grad;
                            ctx.beginPath();
                            ctx.arc(0, -(alto+ancho), ancho, Math.PI, 2*Math.PI);
                            ctx.fill();
                        };

                        function DibujaBola() {
                            grad = ctx.createRadialGradient(ancho*0.2, -ancho, 0, ancho*0.2, -ancho, radio*1.1);
                            grad.addColorStop(0, '#85d841');
                            grad.addColorStop(1, '#61AE24');
                            ctx.fillStyle = grad;
                            ctx.beginPath();
                            ctx.arc(0,0, radio, 0, 2*Math.PI);
                            ctx.fill();

                            // Borde del Termometro
                            ctx.strokeStyle = "#333";
                            ctx.strikeWidth = 4;
                            ctx.beginPath();
                            ctx.arc(0,0, radio*1.1, -0.31*Math.PI, 1.3*Math.PI);
                            ctx.lineTo(-ancho*1.2,-alto*1.05);
                            ctx.arc(0, -(ancho+alto), ancho*1.2, Math.PI, 2*Math.PI);
                            ctx.lineTo(ancho*1.2, -ancho*1.9);
                            ctx.closePath();
                            ctx.stroke();

                            // Marcas de Medición
                            var i = 0;
                            var val2 = maxim / thermlines;
                            var y = -ymin;
                            for (i=0; i<=thermlines; i++) {
                                y = -(ymin + (yinc * 10 / thermlines * i));
                                ctx.strokeStyle = '#333';
                                ctx.strikeWidth = 4;
                                ctx.beginPath();
                                ctx.moveTo(-ancho*1.4, y);
                                ctx.lineTo(0, y);
                                ctx.stroke();
                                ctx.font = radio*0.32 + "px calibri";
                                ctx.textBaseline="middle";
                                ctx.textAlign="center";
                                ctx.fillStyle = '#000';
                                var v = Math.round(i*val2);
                                v = target > 1000 ? (v / 1000 + 'k') : v;
                                ctx.fillText('£' + v, -radio*1.1 - 7, y);
                            };

                            // Escribe Valor
                            ctx.font = radio*0.8 + "px calibri";
                            ctx.fillStyle = '#000';
                            ctx.fillText(Math.round(100 * xx1 / self.donations.get('target')) + '%', 0, 0);
                            ctx.fillText("£" + Math.round(valor1), 0, radio*1.6);
                            ctx.fillText("Raised", 0, radio*2.4);
                        };
                    });
                });
            });

            return(p);
        }
    });

    Iznik.Views.User.BusinessCards = Iznik.Views.Modal.extend({
        template: 'user_support_businesscards',

        tagName: 'li',

        events: {
            'click .js-submit': 'submit',
            'click .js-justafew': 'justafew',
            'click .js-more': 'more'
        },

        justafew: function() {
            var self = this;
            self.$('.js-howmany').slideUp('slow');
            self.$('.js-more, .js-justafew').hide();
            self.$('.js-afew, .js-submit').fadeIn('slow');
        },

        more: function() {
            var self = this;
            self.$('.js-howmany').slideUp('slow');
            self.$('.js-more').fadeIn('slow');
            self.$('.js-afew, .js-submit').hide();
            Iznik.ABTestAction('BusinessCards', 'more');
        },

        submit: function() {
            var self = this;
            var pafid = self.postalAddress.pafaddress();
            var to = self.postalAddress.to();
            var instr = self.postalAddress.instructions();

            if (pafid) {
                $.ajax({
                    url: API + '/address',
                    type: 'PUT',
                    data: {
                        pafid: pafid
                    },
                    success: function(ret) {
                        if (ret.ret === 0) {
                            $.ajax({
                                url: API + '/request',
                                type: 'PUT',
                                data: {
                                    reqtype: 'BusinessCards',
                                    to: to,
                                    addressid: ret.id
                                },
                                success: function(ret) {
                                    if (ret.ret === 0) {
                                        self.close();
                                        var v = new Iznik.Views.User.BusinessCards.Thankyou();
                                        v.render();
                                    }
                                }
                            });

                            Iznik.ABTestAction('BusinessCards', 'justafew');
                        }
                    }
                });
            }
        },

        render: function() {
            var self = this;
            var p = Iznik.Views.Modal.prototype.render.call(self);
            p.then(function () {
                self.waitDOM(self, function() {
                    var me = Iznik.Session.get('me');
                    var settings = me.hasOwnProperty('settings') ? me.settings : null;
                    var location = settings ? (settings.hasOwnProperty('mylocation') ? settings.mylocation : null) : null;
                    var postcode = location ? location.name : null;

                    self.postalAddress = new Iznik.Views.PostalAddress({
                        postcode: postcode,
                        showTo: true,
                        to: me.displayname
                    });
                    self.postalAddress.render();
                    self.$('.js-postaladdress').append(self.postalAddress.$el);

                    Iznik.ABTestShown('BusinessCards', 'justafew');
                    Iznik.ABTestShown('BusinessCards', 'more');
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.BusinessCards.Thankyou = Iznik.Views.Modal.extend({
        template: 'user_support_businesscardsthanks',

        render: function() {
            var self = this;

            var p = Iznik.Views.Modal.prototype.render.call(this);
            p.then(function() {
                self.$('input[name="custom"]').val(Iznik.Session.get('me').id);
            });

            return(p);
        }
    });

    Iznik.Views.User.SupportGroup = Iznik.Views.Modal.extend({
        render: function() {
            var self = this;


            $.ajax({
                url: API + 'dashboard',
                data: {
                    group: self.model.get('id'),
                    start: '13 months ago',
                    grouptype: 'Freegle',
                    systemwide: self.options.id ? false : true
                },
                success: function (ret) {
                    // TODO: v is not defined, should it be?
                    //v.close(); 

                    if (ret.dashboard) {
                        self.$('.js-donations').html(ret.dashboard.donationsthisyear ? ret.dashboard.donationsthisyear : '0');
                    }
                }
            });
        }
    });

    Iznik.Views.User.SupportShare = Iznik.Views.Modal.extend({
        template: 'user_support_facebookshare',

        events: {
            'click .js-close': 'closeIt',
            'click .js-sharefb': 'shareFB'
        },

        closeIt: function() {
            var self = this;
            Iznik.ABTestAction('FacebookShare', 'Close');
            self.close();
        },

        shareFB: function() {
            var self = this;
            Iznik.ABTestAction('FacebookShare', 'Favour');

            var params = {
                method: 'share',
                href: window.location.protocol + '//' + window.location.host + '?src=pleaseshare',
            };

            FB.ui(params, function (response) {
                self.close();
            });
        },

        render: function() {
            var self = this;

            // Only do this if we know that they have a Facebook login.
            if (Iznik.Session.hasFacebook()) {
                // And only every month.
                var lastshow = Storage.get('lastpleaseshare');
                var show = !lastshow || (((new Date()).getTime() - (new Date(lastshow)).getTime()) > 31 * 24 * 60 * 60 * 1000);

                if (show) {
                    Storage.set('lastpleaseshare', (new Date()).getTime());
                    FBLoad().render();

                    Iznik.ABTestShown('FacebookShare', 'Favour');
                    Iznik.Views.Modal.prototype.render.call(self);
                }
            }
        }
    });

    Iznik.Views.User.eBay = Iznik.Views.Modal.extend({
        template: 'user_home_ebay',

        events: {
            'click .js-notagain': 'notagain',
            'click .js-cancel': 'cancel',
            'click .js-vote': 'vote'
        },

        vote: function() {
            Iznik.ABTestAction('ebay', 'Vote');
            this.close();
        },

        cancel: function() {
            Iznik.ABTestAction('ebay', 'Cancel');
            this.close();
        },

        notagain: function() {
            Iznik.ABTestAction('ebay', 'Not again');
            Storage.set('ebaynotagain', true);
            this.close();
        },

        render: function() {
            var self = this;

            Iznik.ABTestShown('ebay', 'Vote');

            if (!Storage.get('ebaynotagain')) {
                Iznik.Views.Modal.prototype.render.call(this);
            }
        }
    });

    Iznik.Views.Aviva = Iznik.Views.Page.extend({
        template: 'user_support_aviva',

        render: function () {
            var self = this;

            Iznik.ABTestShown('aviva', 'Show');

            var p = Iznik.Views.Page.prototype.render.call(this).then(function () {
                $.ajax({
                    url: API + 'dashboard',
                    data: {
                        start: '2 months ago',
                        grouptype: 'Freegle',
                        group: 1
                    },
                    success: function (ret) {
                        var d = ret.dashboard.aviva;

                        if (d) {
                            self.$('.js-position').html(d.ourposition);
                            self.$('.js-votes').html(d.ourvotes);

                            self.top20 = new Iznik.Collection(d.top20);
                            self.history = d.history;

                            self.top20CV = new Backbone.CollectionView({
                                el: self.$('.js-top20'),
                                modelView: Iznik.Views.Aviva.Top20,
                                collection: self.top20,
                                processKeyEvents: false
                            });

                            self.top20CV.render();

                            self.$('.js-howweredoing').fadeIn('slow');

                            function apiLoaded() {
                                // Defer so that it's in the DOM - google stuff doesn't work well otherwise.
                                _.defer(function () {
                                    var data = new google.visualization.DataTable();
                                    data.addColumn('date', 'Date');
                                    data.addColumn('number', 'Position');
                                    _.each(self.history, function (hist) {
                                        data.addRow([new Date(hist.timestamp), parseInt(hist.position, 10) ]);
                                    });

                                    var formatter = new google.visualization.DateFormat({formatType: 'yy-M-d H'});
                                    formatter.format(data, 1);

                                    self.chart = new google.visualization.LineChart(self.$('.js-positiongraph').get()[0]);
                                    self.data = data;
                                    self.chartOptions = {
                                        title: 'Aviva Voting Position',
                                        interpolateNulls: false,
                                        animation: {
                                            duration: 5000,
                                            easing: 'out',
                                            startup: true
                                        },
                                        legend: {position: 'none'},
                                        chartArea: {'width': '80%', 'height': '80%'},
                                        vAxis: {
                                            viewWindow: {min: 0},
                                            title: 'Position'
                                        },
                                        hAxis: {
                                            format: 'dd MMM'
                                        },
                                        series: {
                                            0: {color: 'darkgreen'}
                                        }
                                    };
                                    self.chart.draw(self.data, self.chartOptions);

                                    var data = new google.visualization.DataTable();
                                    data.addColumn('date', 'Date');
                                    data.addColumn('number', 'Votes');
                                    _.each(self.history, function (hist) {
                                        data.addRow([new Date(hist.timestamp), parseInt(hist.votes, 10) ]);
                                    });

                                    var formatter = new google.visualization.DateFormat({formatType: 'yy-M-d H'});
                                    formatter.format(data, 1);

                                    self.chart = new google.visualization.LineChart(self.$('.js-votesgraph').get()[0]);
                                    self.data = data;
                                    self.chartOptions = {
                                        title: 'Aviva Votes',
                                        interpolateNulls: false,
                                        animation: {
                                            duration: 5000,
                                            easing: 'out',
                                            startup: true
                                        },
                                        legend: {position: 'none'},
                                        chartArea: {'width': '80%', 'height': '80%'},
                                        vAxis: {
                                            viewWindow: {min: 0},
                                            title: 'Votes'
                                        },
                                        hAxis: {
                                            format: 'dd MMM'
                                        },
                                        series: {
                                            1: {color: 'darkblue'}
                                        }
                                    };
                                    self.chart.draw(self.data, self.chartOptions);
                                });
                            }

                            google.charts.load('current', {packages: ['corechart', 'annotationchart']});
                            google.charts.setOnLoadCallback(apiLoaded);
                        }
                    }
                });
            });

            return (p);
        }
    });

    Iznik.Views.Aviva.Top20 = Iznik.View.extend({
        tagName: 'li',

        template: 'user_support_avivatop20'
    });
});
