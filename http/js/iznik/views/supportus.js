define([
    'jquery',
    'underscore',
    'backbone',
    'iznik/base',
    'typeahead',
    'iznik/models/donations',
    'iznik/views/postaladdress'
], function($, _, Backbone, Iznik) {
    Iznik.Views.SupportUs = Iznik.Views.Modal.extend({
        events: {
            'click .js-invite': 'doInvite',
            'click .js-clickdonate': 'clickDonate'
        },

        clickDonate: function() {
            ABTestAction('SupportUs', this.template);
        },

        doInvite: function() {
            var self = this;
            var email = self.$('.js-inviteemail').val();
            ABTestAction('SupportUs', 'invite');

            if (isValidEmailAddress(email)) {
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
                p = ABTestGetVariant('SupportUs', function(variant) {
                    self.template = variant.variant;

                    var p = Iznik.Views.Modal.prototype.render.call(self);
                    p.then(function() {
                        var w = new Iznik.Views.DonationThermometer();
                        w.render().then(function () {
                            Storage.set('donationlastask', now);
                            self.$('.js-thermometer').html(w.$el);
                        });
                    });

                    ABTestShown('SupportUs', self.template);
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
                self.donations.fetch().then(function() {

                    self.waitDOM(self, function() {
                        var valor1 = self.donations.get('raised');
                        var maxim = self.donations.get('target');
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
                        var target = 15000;
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
                                ctx.fillText('£' + i*val2 / 1000 + 'k', -radio*1.1 - 7, y);
                            };

                            // Escribe Valor
                            ctx.font = radio*0.8 + "px calibri";
                            ctx.fillStyle = '#000';
                            ctx.fillText(Math.round(100 * xx1 / maxim) + '%', 0, 0);
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
            ABTestAction('BusinessCards', 'more');
        },

        submit: function() {
            var self = this;
            var pafid = self.postalAddress.address();
            var to = self.postalAddress.to();

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

                            ABTestAction('BusinessCards', 'justafew');
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

                    ABTestShown('BusinessCards', 'justafew');
                    ABTestShown('BusinessCards', 'more');
                });
            });

            return (p);
        }
    });

    Iznik.Views.User.BusinessCards.Thankyou = Iznik.Views.Modal.extend({
        template: 'user_support_businesscardsthanks'
    });
});
